<?php

namespace App\Services;

use App\Enums\CompareSolutions;
use App\Enums\Employments;
use App\Enums\EmploymentTypes;
use App\Enums\Experiences;
use App\Enums\InterviewResultStatus;
use App\Enums\InterviewSessionStatus;
use App\Enums\MessageSenders;
use App\Enums\WorkLocations;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Resources\LanguageResource;
use App\Models\Candidate;
use App\Models\CandidateVacancy;
use App\Models\CandidateVacancyMessage;
use App\Models\Company;
use App\Models\InterviewSession;
use App\Models\Vacancy;
use App\Modules\HhIntegrationModule\Models\HhToken;
use App\Modules\HhIntegrationModule\Services\HhService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    protected SanitizeService $sanitizeService;

    public function __construct()
    {
        $this->sanitizeService = new SanitizeService();
    }


    public function promptGen(array $data, string $type, string $lang, int $role = 1)
    {
        $i = 1;
        $postData = [];
        // Начинаем с запроса, который будет multipart
        $httpClient = Http::asMultipart()->connectTimeout(10)->timeout(0);

        foreach ($data['attachments'] as $attachment) {
            /** @var \Illuminate\Http\UploadedFile $file */
            $file = $attachment['file'];

            $httpClient = $httpClient->attach(
                "file_$i",
                fopen($file->getRealPath(), 'r'),
                $file->getClientOriginalName()
            );
            $postData["comment_$i"] = $attachment['name'];
            $i++;
        }
        $postData["type"] = $type;
        $postData["role"] = $role;
        $response = $httpClient->post('https://elzaai.online:5000/upload/' . $lang, $postData);

        return $response->json();
    }

    public function getSessionResult(InterviewSession $session)
    {
        try {
            $lang = $session->vacancyCandidate->vacancy->language;
            $token = $session->token;
            $avatarExist = (bool)$session->vacancyCandidate->candidate->getFirstMedia('avatar');
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->connectTimeout(10)->timeout(0)
                ->post("https://elzaai.online:5000/get_result/$lang/$token", [
                    'vacancy_description' => $session->vacancyCandidate->vacancy->description,
                    'candidate_description' => $session->vacancyCandidate->candidate->clean_description,
                    'candidate_name' => $session->vacancyCandidate->candidate->name,
                    'mandatory_skills' => $session->vacancyCandidate->vacancy->hardSkills()->pluck('skill')->all(),
                    'nice_to_have_skills' => $session->vacancyCandidate->vacancy->niceToHaveSkills()->pluck('skill')->all(),
                    'avatar_exists' => $avatarExist,
                ]);
            return $response->json();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }


    public function getSessionSummary(InterviewSession $session)
    {
        try {
            $lang = $session->vacancyCandidate->vacancy->language;
            $token = $session->token;
            $avatarExist = (bool)$session->vacancyCandidate->candidate->getFirstMedia('avatar');
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->connectTimeout(10)->timeout(0)
                ->post("https://elzaai.online:5000/get_chat_history_summary/$lang/$token", [
                    'vacancy_description' => $session->vacancyCandidate->vacancy->description,
                    'candidate_description' => $session->vacancyCandidate->candidate->clean_description,
                    'candidate_name' => $session->vacancyCandidate->candidate->name,
                    'avatar_exists' => $avatarExist,
                ]);
            return $response->json();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }


    public function getSessionAnswers(InterviewSession $session)
    {
        try {
            $lang = $session->vacancyCandidate->vacancy->language;
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->connectTimeout(10)->timeout(0)
                ->post("https://elzaai.online:5000/get_answers/$lang", [
                    'vacancy_description' => $session->vacancyCandidate->vacancy->description,
                    'dialog_id' => $session->token,
                ]);
            return $response->json();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }

    public function getSkillsResult(InterviewSession $session)
    {
        try {
            $lang = $session->vacancyCandidate->vacancy->language;
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->connectTimeout(10)->timeout(0)
                ->post("https://elzaai.online:5000/get_language_and_skills/$lang", [
                    'vacancy_description' => $session->vacancyCandidate->vacancy->description,
                    'candidate_description' => $session->vacancyCandidate->candidate->clean_description,
                    'chat_history' => $session->chat_history,
                    'answers_result' => $session->answers_result,
                ]);
            return $response->json();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }

    public function getVacancyKeywords(Vacancy $vacancy)
    {
        try {
            $lang = $vacancy->language;
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->connectTimeout(10)->timeout(0)
                ->post("https://elzaai.online:5000/get_vacancy_keywords/$lang", [
                    'vacancy' => $vacancy->title,
                    'description' => $vacancy->description,
                ]);
            Log::info("result", $response->json());
            return $response->json('result');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }

    public function getProfessionalRoleForHH(Vacancy $vacancy)
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->connectTimeout(10)->timeout(0)
                ->post("https://elzaai.online:5000/get_hh_professional_role/", [
                    'description' => $vacancy->description,
                    'title' => $vacancy->title,
                ]);
            if (!$response->ok()) {
                Log::error('Get prof role error', $response->json());
                throw new \Exception('Get prof role error');
            }
            return json_decode($response->json('result'), true);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }


    public function getBestHHCandidates(Vacancy $vacancy, array $candidates)
    {

        try {
            $vacancyDesc = Cache::remember("vacancy_description:{$vacancy->id}", now()->addDay(), function () use ($vacancy) {
                if ($vacancy->salary_from < $vacancy->salary_to) {
                    $salary = "from " . $vacancy->salary_from . " to " . $vacancy->salary_to . " " . $vacancy->salary_currency;
                } else {
                    $salary = $vacancy->salary_from . " " . $vacancy->salary_currency;
                }
                $languages = "";
                foreach ($vacancy->languages as $language) {
                    $name = $language->name;
                    $level = $language->pivot->level;
                    $languages .= "Language: $name - level: $level";
                }
                $vacancyDesc = "Title: {$vacancy->title}";
                $vacancyItems = [
                    'Description' => $vacancy->description,
                    'Salary' => $salary,
                    'Employment' => $vacancy->employment,
                    'Employment type' => $vacancy->employment_type,
                    'Location' => $vacancy->location,
                    'City' => $vacancy->area->name,
                    'Need languages' => $languages,
                    'Need experience' => $vacancy->experience,
                ];
                foreach ($vacancyItems as $key => $val) {
                    $vacancyDesc .= "\n\n{$key}: $val";
                }
                return $vacancyDesc;
            });
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->connectTimeout(10)->timeout(0)
                ->post("https://elzaai.online:5000/get_best_candidates", [
                    'vacancy' => $vacancyDesc,
                    'candidates' => self::formatCandidateData($candidates),
                ]);
            return json_decode($response->json('result'), true);
        } catch (\Exception $e) {
            Log::error($e->getMessage(), $e->getTrace());
            return [];
        }
    }

    private static function formatCandidateData($data)
    {
        $result = [];

        foreach ($data as $entry) {
            // Заголовок и возраст
            $entry_str = "<CANDIDATE_{$entry['id']}>
            ID: {$entry['id']}
            Желаемая должность: " . $entry['title'] . ", Возраст: " . $entry['age'] . " лет";

            // Образование
            $education_str = [];
            if (isset($entry['education']['primary'])) {
                foreach ($entry['education']['primary'] as $edu) {
                    $education_str[] = $edu['name'] . " (" . $edu['year'] . ") — " . $edu['result'];
                }
            }
            $entry_str .= "\nОбразование: " . (count($education_str) ? implode(', ', $education_str) : 'Не указано');

            // Опыт
            $experience_str = [];
            if (isset($entry['experience'])) {
                foreach ($entry['experience'] as $exp) {
                    $position = $exp['position'] ?? 'Не указана';
                    $company = $exp['company'] ?? 'Не указана';
                    $start = $exp['start'] ?? 'Не указана';
                    $end = $exp['end'] ?? 'настоящее время';
                    $experience_str[] = "$position в $company с $start по $end";
                }
            }
            $entry_str .= "\nОпыт работы: " . (count($experience_str) ? implode(', ', $experience_str) : 'Не указан');

            // Зарплата (если есть)
            $salary_str = isset($entry['salary']) ? "Зарплата: " . $entry['salary']['amount'] . " " . $entry['salary']['currency'] : "Зарплата: Не указана";
            $entry_str .= "\n" . $salary_str . "</CANDIDATE_{$entry['id']}>";
            $result[] = $entry_str;
        }

        return implode("\n\n", $result);
    }


    public function getRecommendation(InterviewSession $session)
    {
        try {
            $lang = $session->vacancyCandidate->vacancy->language;
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->connectTimeout(10)->timeout(0)
                ->post("https://elzaai.online:5000/get_recommendation/$lang", [
                    'vacancy_description' => $session->vacancyCandidate->vacancy->description,
                    'candidate_description' => $session->vacancyCandidate->candidate->clean_description,
                    'chat_history' => $session->chat_history,
                    'answers_result' => $session->answers_result,
                ]);
            return $response->json();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }

    public function getCandidateQuestions(InterviewSession $session)
    {
        try {
            $token = $session->token;
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->connectTimeout(10)
                ->timeout(840)
                ->retry(3, 2000)
                ->post("https://elzaai.online:5000/get_candidate_questions/ru", [
                    'dialog_id' => $token
                ])
                ->throw();                 // пусть кидает исключение на 4xx/5xx

            return $response->json();

        } catch (\Throwable $e) {
            Log::error('getCandidateQuestions failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getRisks(InterviewSession $session)
    {
        try {
            $lang = $session->vacancyCandidate->vacancy->language;
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->connectTimeout(10)->timeout(0)
                ->post("https://elzaai.online:5000/get_risks/$lang/{$session->token}", [
                    'vacancy_description' => $session->vacancyCandidate->vacancy->description,
                    'candidate_description' => $session->vacancyCandidate->candidate->clean_description,
                    'chat_history' => $session->chat_history,
                    'answers_result' => $session->answers_result,
                ]);
            return $response->json();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }

    public static function getEmploymentText($employment)
    {
        return match ($employment) {
            Employments::FULL_TIME->value => 'Полная',
            Employments::CASUAL->value => 'временная',
            Employments::SHIFT->value => 'Разъездная',
            Employments::PART_TIME->value => 'Частичная',
            default => 'Не указана',
        };
    }

    public static function getEmploymentTypeText($employment)
    {
        return match ($employment) {
            EmploymentTypes::LABOR->value => 'Трудовой договор',
            EmploymentTypes::SELF_EMPLOYER->value => 'Договор с ИП или самозанятым',
            EmploymentTypes::CIVIL->value => 'ГПП',
            default => 'Не указана'
        };
    }

    public static function getLocationText($location)
    {
        return match ($location) {
            WorkLocations::REMOTE->value => 'Удаленная',
            WorkLocations::HYBRID->value => 'Гибрид',
            WorkLocations::ON_SITE->value => 'В офисе',
            WorkLocations::FIELD->value => 'Вахтовая',
            default => 'Не указана',
        };
    }

    public static function getExperienceText($experience)
    {
        return match ($experience) {
            Experiences::NO_EXPERIENCE->value => 'Можно без опыта',
            Experiences::MORE_THAN->value => 'Больше 6 лет',
            Experiences::BET_1_3->value => 'От года',
            Experiences::BET_3_6->value => 'От трех лет',
            default => 'Не указана'
        };
    }

    public function compare($lang, Vacancy $vacancy, CandidateVacancy|string $candidate)
    {
        try {
            $uri = "https://elzaai.online:5000/compare_v4/$lang";
            if ($vacancy->salary_from < $vacancy->salary_to) {
                $salary = "from " . $vacancy->salary_from . " to " . $vacancy->salary_to . " " . $vacancy->salary_currency;
            } else {
                $salary = $vacancy->salary_from . " " . $vacancy->salary_currency;
            }
            $languages = "";
            foreach ($vacancy->languages as $language) {
                $name = $language->name;
                $level = $language->pivot->level;
                $languages .= "Language: $name - level: $level";
            }
            $data = [
                'vacancy_description' => $vacancy->description,
                'vacancy_salary' => $salary,
                'vacancy_employment' => self::getEmploymentText($vacancy->employment),
                'vacancy_employment_type' => self::getEmploymentTypeText($vacancy->employment_type),
                'vacancy_location' => self::getLocationText($vacancy->location),
                'vacancy_country' => $vacancy->area?->name ?? '',
                'vacancy_languages' => $languages,
                'mandatory_skills' => $vacancy->hardSkills()->pluck('skill')->all(),
                'nice_to_have_skills' => $vacancy->niceToHaveSkills()->pluck('skill')->all(),
                'vacancy_experience' => self::getExperienceText($vacancy->experience),
            ];

            if (is_string($candidate)) {
                $data['candidate_description'] = $candidate;
            } else {
                if (empty($candidate->candidate->clean_description)) {
                    if (empty($candidate->candidate->description)) {
                        throw new \Exception("Candidate description is empty");
                    }
                    $sanitaze = new SanitizeService();
                    $candidate->candidate->clean_description = $sanitaze->getSanitizeCv($candidate->candidate->description)['clean_text'];
                    $candidate->candidate->save();
                }
                $data['candidate_description'] = $candidate->candidate->clean_description;
                if ($candidate->before_interview_answers) {
                    $data['before_interview_answers'] = $candidate->before_interview_answers;
                    $data['previews_score'] = $candidate->resume_percentage;
                } else if ($candidate->hh_resume_id) {
                    $token = HhToken::where('company_id', $candidate->company_id)->where('user_id', $candidate->vacancy->user_id)->first();
                    if ($token) {
                        try {
                            $hhService = new HhService();
                            $hhService->syncHhMessagesTail($token, $candidate);
                            $messages = $candidate->messages;
                            $data['cover_letter'] = "";
                            foreach ($messages as $message) {
                                $data['cover_letter'] .= $this->messageFormatter($message);
                            }
                        } catch (\Exception $e) {
                            Log::error("Sync messages error: {$e->getMessage()}", $e->getTrace());
                        }
                    }
                }
            }
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->connectTimeout(10)->timeout(600)->post($uri, $data);
            if (is_array($response->json())) {
                return $response->json();
            }
            return json_decode($response->json(), true);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }

    public function companyDescription(string $lang, Company $company): string
    {
        try {
            $uri = "https://elzaai.online:5000/get_company_desc/$lang?name=$company->name&country=$company->county";
            if ($company->website) {
                $uri .= $uri . "&website=" . $company->website;
            }
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->get($uri);

            return $response->json('result');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }

    public function skillList(Vacancy $vacancy)
    {
        try {
            $uri = "https://elzaai.online:5000/skills/{$vacancy->language}";
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->connectTimeout(10)->timeout(0)->post($uri, [
                'vacancy_title' => $vacancy->title,
                'vacancy_description' => $vacancy->description,
                'hard_skills' => $vacancy->allHardSkills()->pluck('skill')->implode(', '),
                'soft_skills' => $vacancy->softSkills()->pluck('skill')->implode(', '),
            ]);
            if (!$response->ok()) {
                throw  new \Exception('Response error ' . $response->json());
            }
            return $response->json();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }

    public function generateVacancy(string $lang, Company $company, string $title)
    {
        try {
            $uri = "https://elzaai.online:5000/generate_vacancy/$lang";
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->connectTimeout(10)->timeout(0)->post($uri, [
                'company_name' => $company->name,
                'company_area' => $company->areas()->pluck('area')->all(),
                'company_bonuses' => $company->bonuses()->pluck('bonus')->all(),
                'company_country' => $company->country,
                'company_website' => $company->website,
                'company_description' => $company->description,
                'vacancy_title' => $title,
                'app_name' => strtolower(config('app.name'))
            ]);
            if (!$response->ok()) {
                Log::error("VAC GEN ERROR: {$response->status()}", $response->json());
                throw new \Exception("Vacancy generate error");
            }
            return $response->json();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }

    public function vacancyDescription(Vacancy $vacancy): string
    {
        try {
            $company = $vacancy->company;
            $uri = "https://elzaai.online:5000/get_vacancy_desc/$vacancy->language";
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->connectTimeout(10)->timeout(0)->post($uri, [
                'name' => $vacancy->title,
                'description' => $vacancy->description,
                'company_name' => $company->name,
                'website' => $company->website,
                'languages' => LanguageResource::collection($vacancy->languages),
                'hard_skills' => $vacancy->hardSkills()->pluck('skill')->all(),
                'nice_to_have' => $vacancy->niceToHaveSkills()->pluck('skill')->all(),
                'soft_skills' => $vacancy->softSkills()->pluck('skill')->all(),
                'country' => $vacancy->country,
                'work_location' => $vacancy->location,
                'employment' => $vacancy->employment,
                'employment_type' => $vacancy->employment_type,
                'bonuses' => $vacancy->bonuses()->pluck('bonus')->all(),
                'experience' => $vacancy->experience,
                'salary_from' => $vacancy->salary_from,
                'salary_to' => $vacancy->salary_to,
                'salary_currency' => $vacancy->salary_currency,
                'company_description' => $company->description,
            ]);

            return $response->json('result');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }

    public function vacancySkills(Vacancy $vacancy)
    {
        try {
            $uri = "https://elzaai.online:5000/get_vacancy_skills/$vacancy->language";
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->connectTimeout(10)->timeout(0)->post($uri, [
                'description' => $vacancy->description,
                'style' => ServiceController::STYLES[$vacancy->mode],
            ]);
            return $response->json();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }

    public function candidateSkills(Candidate $candidate)
    {
        try {
            $uri = "https://elzaai.online:5000/get_candidate_skills/ru";
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->connectTimeout(10)->timeout(0)->post($uri, [
                'description' => $candidate->clean_description
            ]);
            return $response->json();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }

    public function checkTask(string $taskId)
    {
        try {
            $uri = "https://elzaai.online:5000/tasks/{$taskId}";
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->get($uri);
            return $response->json();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }

    public function resumeRecommendation(Candidate $candidate)
    {
        try {
            $uri = "https://elzaai.online:5000/get_resume_recommendation/ru";
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->connectTimeout(10)->timeout(0)->post($uri, [
                'description' => $candidate->clean_description,
                'position' => $candidate->position,
            ]);
            return $response->json();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }

    public function improve(Vacancy $vacancy, array $data): string
    {
        try {
            $vacancyParam = $vacancy->{$data['param']};
            $improve = $data['text'];
            $uri = "https://elzaai.online:5000/get_improved/$vacancy->language";

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->connectTimeout(10)->timeout(0)->post($uri, [
                'description' => $vacancyParam,
                'type' => $data['param'],
                'vacancy_description' => $vacancy->description,
                'bonuses' => $vacancy->bonuses()->pluck('bonus')->all(),
                'salary_from' => $vacancy->salary_from,
                'salary_to' => $vacancy->salary_to,
                'salary_currency' => $vacancy->salary_currency,
                'country' => $vacancy->country,
                'employment' => $vacancy->employment,
                'experience' => $vacancy->experience,
                'employment_type' => $vacancy->employment_type,
                'location' => $vacancy->location,
                'hard_skills' => $vacancy->hardSkills()->pluck('skill')->all(),
                'nice_to_have' => $vacancy->niceToHaveSkills()->pluck('skill')->all(),
                'soft_skills' => $vacancy->softSkills()->pluck('skill')->all(),
                'languages' => LanguageResource::collection($vacancy->languages),
                'company_description' => $vacancy->company->description,
                'improve' => $improve,
            ]);

            return $response->json('result');
        } catch (\Exception $e) {
            Log::error($e->getMessage(), $e->getTrace());
            return false;
        }
    }

    public function getQuestions(Vacancy $vacancy)
    {
        try {
            $languages = "";
            foreach ($vacancy->languages as $language) {
                $languages .= "{$language->name} - {$language->pivot->level}\n";
            }
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->connectTimeout(10)->timeout(0)->post("https://elzaai.online:5000/get_questions/$vacancy->language", [
                'vacancy_description' => $vacancy->description,
                'style' => $vacancy->style,
                'hard_skills' => $vacancy->allHardSkills()->pluck('skill')->all(),
                'soft_skills' => $vacancy->softSkills()->pluck('skill')->all(),
                'languages' => $languages,
            ]);
            if (!$response->ok()) {
                throw new \Exception($response->getBody());
            }
            return $response->json('response', false);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }

    public function generateResume($resumeText, $lang = 'ru')
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->connectTimeout(10)->timeout(0)->post("https://elzaai.online:5000/get_resume/$lang", [
                'text' => $resumeText,
            ]);
            if (!$response->ok()) {
                throw new \Exception($response->getBody());
            }
            return $response->json('result', false);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }

    public static function getInterviewStatus(CandidateVacancy $candidateVacancy): string
    {
        if (empty($candidateVacancy->result)) {
            if ($candidateVacancy->compare_solution === CompareSolutions::INTERVIEW) {
                return match ($candidateVacancy->session->status) {
                    InterviewSessionStatus::CREATED, InterviewSessionStatus::OPENED => 'приглашен на собеседование, но еще не начал его прохождение',
                    InterviewSessionStatus::STARTED => 'Начал прохождение собеседования',
                    InterviewSessionStatus::ENDED => 'Закончил прохождение собеседования',
                    InterviewSessionStatus::EXITED => "Начал прохождение, но покинул до завершения",
                    InterviewSessionStatus::ERROR => "Начал прохождение, но завершилось с ошибкой",
                };
            }
        } else {
            return match ($candidateVacancy->result) {
                InterviewResultStatus::HIRE_NOW->value,
                InterviewResultStatus::RECOMMEND->value,
                InterviewResultStatus::CONSIDER->value => 'прошел собеседование успешно',
                default => 'получил отказ по результатам собеседования'
            };
        }

        return "";
    }

    public function getAnswer(CandidateVacancy $candidateVacancy, $lang = 'ru', $inTg = false, $customTask = null)
    {
        try {
            $vacancy = $candidateVacancy->vacancy;
            if ($vacancy->salary_from < $vacancy->salary_to) {
                $salary = "from " . $vacancy->salary_from . " to " . $vacancy->salary_to . " " . $vacancy->salary_currency;
            } else {
                $salary = $vacancy->salary_from . " " . $vacancy->salary_currency;
            }

            $languages = "";
            foreach ($vacancy->languages as $language) {
                $name = $language->name;
                $level = $language->pivot->level;
                $languages .= "Language: $name - level: $level";
            }
            $messages = "";
            foreach ($candidateVacancy->messages as $message) {
                $messages .= $this->messageFormatter($message);
            }
            $data = [
                'vacancy' => $vacancy->description,
                'candidate_name' => $candidateVacancy->candidate->name,
                'candidate' => $candidateVacancy->candidate->clean_description,
                'messages' => $messages,
                'last_message' => $candidateVacancy->message ?? null,
                'vacancy_salary' => $salary,
                'vacancy_employment' => self::getEmploymentText($vacancy->employment),
                'vacancy_employment_type' => self::getEmploymentTypeText($vacancy->employment_type),
                'vacancy_location' => self::getLocationText($vacancy->location),
                'vacancy_country' => $vacancy->area->name,
                'vacancy_languages' => $languages,
                'vacancy_experience' => self::getExperienceText($vacancy->experience),
            ];
            if (!$candidateVacancy->messages) {
                $data['need_greeting'] = 'true';
            }
            $service = 'agent';
            if ($candidateVacancy->compare_solution === CompareSolutions::INTERVIEW && $candidateVacancy->session) {
                $data['candidate_status'] = self::getInterviewStatus($candidateVacancy);
            } elseif ($candidateVacancy->compare_solution === CompareSolutions::REFUSAL) {
                $data['candidate_status'] = 'получил отказ после собеседования';
            } elseif ($candidateVacancy->compare_solution === CompareSolutions::NEED_MORE_INFO && !empty($candidateVacancy->before_interview)) {
                $service = 'screener';
                $data['task'] = $candidateVacancy->before_interview[0];
            }
            if ($customTask) {
                $data['task'] = $customTask;
            }
            if ($inTg) {
                $data['in_tg'] = 'true';
            }
//            Log::info('Data', $data);
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->connectTimeout(10)->timeout(0)->post("https://elzaai.online:5000/{$service}/$lang", $data);
            if (!$response->ok()) {
                throw new \Exception($response->getBody());
            }
            if (is_array($response->json('result'))) {
                $result = $response->json('result');
            } else {
                $result['reply'] = $response->json('result');
                $result['status'] = 'ANSWER';
            }
            $result['service'] = $service;
            return $result;
        } catch (\Exception $e) {
            Log::error($e->getMessage(), $e->getTrace());
            return false;
        }

    }


    public function messageFormatter(CandidateVacancyMessage $message)
    {
        $text = $this->sanitizeService->getSanitizeCv($message->message);
        return "<MSG role='{$message->sender->value}' ts='{$message->created_at}'>{$text['clean_text']}</MSG>\n\n";
    }

    public function getInviteText(CandidateVacancy $candidateVacancy, $link, $lang = 'ru', $messages = [])
    {
        try {
            $data = [
                'vacancy' => $candidateVacancy->vacancy->title,
                'candidate' => $candidateVacancy->candidate->name,
                'messages' => $messages,
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->connectTimeout(10)->timeout(0)->post("https://elzaai.online:5000/invite_text/$lang", $data);
            if (!$response->ok()) {
                throw new \Exception($response->getBody());
            }
            $message = $response->json('result');
            $message = str_replace("{{INTERVIEW_LINK}}.", " $link ", $message);
            return str_replace("{{INTERVIEW_LINK}}", " $link ", $message);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }

    }

    public function getRejectText(CandidateVacancy $candidateVacancy, $rejectBy = null, $lang = 'ru')
    {
        try {
            $data = [
                'vacancy' => $candidateVacancy->vacancy->title,
                'candidate' => $candidateVacancy->candidate->name,
            ];
            if ($candidateVacancy->session && $candidateVacancy->session->status === InterviewSessionStatus::ENDED) {
                $data['interviewed'] = 'true';
            }
            if ($rejectBy) {
                $data['reject_by'] = $rejectBy;
            }
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->connectTimeout(10)->timeout(0)->post("https://elzaai.online:5000/reject_text/$lang", $data);
            if (!$response->ok()) {
                throw new \Exception($response->getBody());
            }
            return $response->json('result');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }

    }

    public function getSuccefullText(CandidateVacancy $candidateVacancy, $lang = 'ru')
    {
        try {
            $data = [
                'vacancy' => $candidateVacancy->vacancy->title,
                'candidate' => $candidateVacancy->candidate->name,
            ];
            if ($candidateVacancy->session && $candidateVacancy->session->status === InterviewSessionStatus::ENDED) {
                $data['interviewed'] = 'true';
            }
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->connectTimeout(10)->timeout(0)->post("https://elzaai.online:5000/succefull_text/$lang", $data);
            if (!$response->ok()) {
                throw new \Exception($response->getBody());
            }
            return $response->json('result');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }

    }

    public function messagesHistory(array $messages): string
    {
        $result = "";
        foreach ($messages as $message) {
            $time = Carbon::parse($message['created_at'])->toDateTimeLocalString();
            $from = $message['author']['participant_type'] === 'applicant' ? "Кандидата" : "Менеджера";
            $result .= "\n\nОт {$from} в {$time}: {$message['text']}";
        }

        return $result;
    }


    public function exportMedia($sessionId)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->connectTimeout(10)->timeout(0)->post("https://elzaai.online:5066/export/session/{$sessionId}");
        if (!$response->accepted()) {
            throw new \Exception($response->getBody());
        }
        return $response->json();
    }

    public function exportStatus($task)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->connectTimeout(10)->timeout(0)->get("https://elzaai.online:5066/export/status/{$task['task_id']}");
        if ($response->status() === 500) {
            throw new \Exception($response->getBody());
        }

        if ($response->status() === 102) {
            return false;
        }

        if ($response->status() === 200) {
            return true;
        }

        return null;
    }

}
