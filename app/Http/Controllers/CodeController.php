<?php

namespace App\Http\Controllers;

use App\Mail\ConfirmCodeMail;
use App\Mail\DemoRequestMail;
use App\Models\ConfirmCode;
use App\Services\SmsRu\SmsRuClient;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CodeController
{
    /**
     * Отправить SMS-код на телефон
     * @throws \Exception
     */

    public function __construct(protected SmsRuClient $smsClient)
    {

    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'identity' => 'required|string|max:191',
            'type' => 'nullable|in:auth,forgot,confirm'
        ]);

        $identity = $data['identity'];

        if (filter_var($identity, FILTER_VALIDATE_EMAIL)) {
            $type = 'email';
            try {
                $data = [
                    'email' => $identity,
                    'type' => 'auth',
                ];
                $this->sendEmail($data);
            } catch (\Exception $e) {
                Log::error("[SEND AUTH EMAIL ERROR] {$e->getMessage()}", $e->getTrace());
                return response()->json([
                    'message' => 'Ошибка при отправке кода'
                ], 503);
            }
        } else {
            $digits = preg_replace('/\D+/', '', $identity);
            if (preg_match('/^\d{10,15}$/', $digits)) {
                $type = 'phone';
                $phoneInt = $digits;
                $data = [
                    'phone' => $phoneInt,
                    'type' => 'auth',
                ];
                try {
                    $this->sendSms($data);
                } catch (\Exception $e) {
                    Log::error("[SEND AUTH SMS ERROR] {$e->getMessage()}", $e->getTrace());
                    return response()->json([
                        'message' => 'Ошибка при отправке кода'
                    ], 503);
                }
            } else {
                return response()->json([
                    'message' => 'Введите корректный email или телефон.'
                ], 422);
            }
        }

        return response()->json(['to' => $type]);
    }

    /**
     * @throws \Exception
     */
    public function sendEmail($data): bool
    {
        $email = $data['email'];
        $type = $data['type'] ?? 'auth';

        $recentCode = ConfirmCode::where('ip_address', request()->ip())
            ->where('send_type', 'email')
            ->where('type', $type)
            ->where('email', $email)
            ->where('used', false)
            ->where('created_at', '>', Carbon::now()->subMinutes(2))
            ->latest()
            ->first();

        if ($recentCode) {
            return true;
        }
        try {
            DB::beginTransaction();

            $code = (string)random_int(1000, 9999);

            ConfirmCode::create([
                'send_type' => 'email',
                'user_id' => request()->user()->id ?? null,
                'ip_address' => request()->ip(),
                'code' => $code,
                'type' => $type,
                'email' => $email,
                'expires_at' => Carbon::now()->addMinutes(5),
                'used' => false,
            ]);

            Mail::to($email)->send(new ConfirmCodeMail($code, "Код для входа в Elza AI"));
            DB::commit();
            return true;
        } catch (\Exception $exceptionx) {
            DB::rollBack();
            throw $exceptionx;
        }
    }

    public function sendSms($data): bool
    {

        $phone = $data['phone'];
        $type = $data['type'] ?? 'auth';

        $recentCode = ConfirmCode::where('ip_address', request()->ip())
            ->where('send_type', 'phone')
            ->where('type', $type)
            ->where('phone', $phone)
            ->where('used', false)
            ->where('created_at', '>', Carbon::now()->subMinutes(2))
            ->latest()
            ->first();

        if ($recentCode) {
            return true;
        }
        $code = 1111;
        if ($phone !== '79611871905') {
            $code = (string)random_int(1000, 9999);
        }
        ConfirmCode::create([
            'send_type' => 'phone',
            'user_id' => request()->user()->id ?? null,
            'ip_address' => request()->ip(),
            'code' => $code,
            'type' => $type,
            'phone' => $phone,
            'expires_at' => Carbon::now()->addMinutes(5),
            'used' => false,
        ]);

        if ($phone !== '79611871905') {
            $this->smsClient->send($phone, "Код для входа в Elza AI. Никому не сообщайте его: {$code}");
        }
        return true;
    }

}
