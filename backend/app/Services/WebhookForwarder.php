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
