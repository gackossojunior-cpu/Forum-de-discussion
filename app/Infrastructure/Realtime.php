<?php
namespace Forum\Infrastructure;

final class Realtime
{
    public static function socketToken(int $userId): ?array
    {
        $secret = getenv('REALTIME_SECRET');
        if (!$secret || $userId < 1) {
            return null;
        }

        $expiresAt = time() + 43200;
        $signature = hash_hmac('sha256', $userId . ':' . $expiresAt, $secret);
        return [
            'userId' => $userId,
            'expiresAt' => $expiresAt,
            'signature' => $signature,
        ];
    }

    public static function emit(array $event): void
    {
        $secret = getenv('REALTIME_SECRET');
        if (!$secret) {
            error_log('Forum realtime event was not sent: REALTIME_SECRET is not configured.');
            return;
        }

        $url = getenv('REALTIME_INTERNAL_URL') ?: 'http://realtime:3001/events';
        $payload = json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            error_log('Forum realtime event was not sent: event could not be encoded.');
            return;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer {$secret}\r\n",
                'content' => $payload,
                'timeout' => 1,
                'ignore_errors' => true,
            ],
        ]);
        $response = @file_get_contents($url, false, $context);
        $status = isset($http_response_header[0]) ? (int)substr($http_response_header[0], 9, 3) : 0;

        if ($response === false || $status !== 204) {
            $detail = $response === false ? (error_get_last()['message'] ?? 'No response') : "HTTP {$status}";
            error_log('Forum realtime event delivery failed: ' . $detail);
        }
    }
}
