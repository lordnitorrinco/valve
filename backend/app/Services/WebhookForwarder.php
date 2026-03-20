<?php

class WebhookForwarder
{
    public static function forward(array $data, string $url): void
    {
        if (empty($url)) return;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $result   = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            SecurityLogger::log('webhook_failed', [
                'url'       => $url,
                'http_code' => $httpCode,
                'error'     => $error ?: substr($result, 0, 200),
            ]);
        } else {
            SecurityLogger::log('webhook_success', ['url' => $url]);
        }
    }
}
