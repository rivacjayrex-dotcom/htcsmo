<?php

namespace App\Support;

use InvalidArgumentException;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Throwable;

class MailFailure
{
    /**
     * Log only diagnostic categories, never provider bodies, HTTP debug traces,
     * reset links, recipients, or credentials.
     */
    public static function context(Throwable $exception): array
    {
        $context = [
            'mailer' => config('mail.default'),
            'exception_type' => $exception::class,
            'reason' => 'mail_transport_failed',
        ];

        if ($exception instanceof InvalidArgumentException) {
            $context['reason'] = config('mail.default') === 'brevo' && blank(config('mail.mailers.brevo.key'))
                ? 'missing_brevo_api_key' : 'invalid_mail_configuration';

            return $context;
        }

        $status = null;
        $code = null;
        // Laravel's notification sender flattens HTTP transport exceptions,
        // preserving the message but discarding the provider response.
        // Inspect that message only to select fixed categories; never log it.
        $message = strtolower($exception->getMessage());
        try {
            if ($exception instanceof HttpTransportException) {
                $response = $exception->getResponse();
                $status = $response->getStatusCode();
                $body = json_decode($response->getContent(false), true);
                $code = is_array($body) ? ($body['code'] ?? null) : null;
                $message = is_array($body) && is_string($body['message'] ?? null) ? strtolower($body['message']) : $message;
            } elseif (config('mail.default') === 'brevo' && preg_match('/\(code ([1-5][0-9]{2})\)\.$/', $message, $matches)) {
                $status = (int) $matches[1];
            }

            if ($status !== null) {
                $context['http_status'] = $status;
            }

            if (in_array($code, ['invalid_parameter', 'missing_parameter', 'unauthorized', 'permission_denied',
                'not_enough_credits', 'account_under_validation', 'out_of_range', 'duplicate_request'], true)) {
                $context['provider_code'] = $code;
            }

            $context['reason'] = match (true) {
                str_contains($message, 'ip address'), str_contains($message, 'unrecognised ip'),
                str_contains($message, 'unrecognized ip') => 'ip_authorization_required',
                $code === 'account_under_validation', str_contains($message, 'pending validation'),
                str_contains($message, 'under validation') => 'account_under_validation',
                str_contains($message, 'not activated'), str_contains($message, 'not enabled') => 'transactional_sending_not_enabled',
                str_contains($message, 'sender') => 'sender_configuration_rejected',
                $status === 401 => 'api_authentication_failed',
                $status === 403 => 'api_access_denied',
                $status === 402, $code === 'not_enough_credits' => 'credits_or_activation_required',
                $status === 429 => 'provider_rate_limited',
                $status === 400 => 'invalid_email_request',
                str_contains($message, 'could not reach'), str_contains($message, 'timed out'),
                str_contains($message, 'could not resolve'), str_contains($message, 'failed to connect') => 'provider_connection_failed',
                default => $status !== null ? 'email_provider_rejected_request' : 'mail_transport_failed',
            };
        } catch (Throwable) {
            $context['reason'] = 'provider_connection_failed';
        }

        return $context;
    }
}
