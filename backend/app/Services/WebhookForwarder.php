<?php

/**
 * Non-blocking HTTP forwarder for sending form data to an external webhook.
 *
 * After a successful database save, this service optionally forwards
 * the sanitized form data to the original n8n.cloud endpoint.
 * Controlled by the FORWARD_WEBHOOK_ENABLED environment variable.
 *
 * The forwarding does NOT include CSRF tokens or honeypot fields —
 * only the actual form data that was submitted by the user.
 */
class WebhookForwarder
{
    /**
     * Forward form data to the specified webhook URL via HTTP POST.
     *
     * Sends a JSON payload with a 10-second timeout. Logs success or failure
     * but never throws — a webhook failure must not break the user's submission.
     *
     * @param array  $data  Sanitized form data (no CSRF token, no honeypot)
     * @param string $url   Webhook endpoint URL
     */
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

        $response = static::executeCurl($ch);
        curl_close($ch);

        if ($response['code'] < 200 || $response['code'] >= 300) {
            SecurityLogger::log('webhook_failed', [
                'url'       => $url,
                'http_code' => $response['code'],
                'error'     => $response['error'] ?: substr($response['body'], 0, 200),
            ]);
        } else {
            SecurityLogger::log('webhook_success', ['url' => $url]);
        }
    }

    /**
     * Execute a cURL handle. Extracted for testability —
     * subclasses can override to simulate HTTP responses.
     *
     * @return array{body: string, code: int, error: string}
     */
    protected static function executeCurl(\CurlHandle $ch): array
    {
        $body  = curl_exec($ch);
        $code  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        return ['body' => $body ?: '', 'code' => $code, 'error' => $error];
    }
}
