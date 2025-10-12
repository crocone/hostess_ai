<?php

namespace App\Services\SmsRu;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class SmsRuClient
{
    protected string $apiKey;
    protected string $baseUrl = 'https://sms.ru';

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? (string) config('sms_ru.api_key');

        if (empty($this->apiKey)) {
            throw new InvalidArgumentException('SMS_RU_API_KEY is not set.');
        }
    }

    /**
     * Отправка одного SMS.
     * $options поддерживает все параметры SMS.RU (например, 'from', 'time', 'ttl', 'test' => 1 и т.п.)
     */
    public function send(string $to, string $message, array $options = []): array
    {
        $to = $this->normalizePhone($to);

        $payload = array_merge([
            'api_id' => $this->apiKey,
            'to'     => $to,
            'msg'    => $message,
            'json'   => 1,
            'ip' => request()->ip()
        ], $options);

        $response = Http::asForm()
            ->baseUrl($this->baseUrl)
            ->get('/sms/send', $payload);

        return $this->parseResponse($response);
    }

    /**
     * Массовая отправка (через запятую).
     */
    public function sendMany(array $phones, string $message, array $options = []): array
    {
        $phones = array_map([$this, 'normalizePhone'], $phones);
        $payload = array_merge([
            'api_id' => $this->apiKey,
            'to'     => implode(',', $phones),
            'msg'    => $message,
            'json'   => 1,
        ], $options);

        $response = Http::asForm()
            ->baseUrl($this->baseUrl)
            ->get('/sms/send', $payload);

        return $this->parseResponse($response);
    }

    /**
     * Статус конкретного сообщения по uid (из send).
     */
    public function status(string $uid): array
    {
        $response = Http::asForm()
            ->baseUrl($this->baseUrl)
            ->get('/sms/status', [
                'api_id' => $this->apiKey,
                'uid'    => $uid,
                'json'   => 1,
            ]);

        return $this->parseResponse($response);
    }

    /**
     * Баланс аккаунта.
     */
    public function balance(): array
    {
        $response = Http::asForm()
            ->baseUrl($this->baseUrl)
            ->get('/my/balance', [
                'api_id' => $this->apiKey,
                'json'   => 1,
            ]);

        return $this->parseResponse($response);
    }

    /**
     * Нормализация телефона: оставляем цифры и опционально добавляем +7/8 не занимаемся.
     */
    protected function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone ?? '');
        if (!$digits) {
            throw new InvalidArgumentException('Invalid phone.');
        }
        return $digits;
    }

    protected function parseResponse(Response $response): array
    {
        if (!$response->ok()) {
            throw new \RuntimeException("SMS.RU HTTP error: {$response->status()}");
        }

        $json = $response->json();

        // У SMS.RU верхний ключ 'status' должен быть 'OK'
        if (!is_array($json) || ($json['status'] ?? null) !== 'OK') {
            // Если пришла ошибка, пробуем вытащить текст
            $err = $json['status_text'] ?? 'Unknown error';
            throw new \RuntimeException("SMS.RU API error: {$err}");
        }

        return $json;
    }
}
