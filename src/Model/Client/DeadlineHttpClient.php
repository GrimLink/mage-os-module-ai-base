<?php

declare(strict_types=1);

namespace MageOS\AiBase\Model\Client;

use Symfony\Component\HttpClient\DecoratorTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Caps every provider request at the execution time PHP has left, minus a margin to answer in.
 *
 * The bridges set no timeout of their own, so a slow provider (a thinking model, an overloaded
 * region) keeps the request open until PHP's max_execution_time kills the process. That is a fatal
 * error nothing can catch: an admin AJAX endpoint answers with PHP's HTML error page instead of its
 * JSON, and the usage row is never written. Running out of time inside the HTTP client instead
 * raises a TransportException, which the client wraps and every caller already handles.
 *
 * The deadline is read per request rather than fixed at construction, because one client can serve
 * several calls within the same request. CLI and cron run without a limit and are left alone.
 */
class DeadlineHttpClient implements HttpClientInterface
{
    use DecoratorTrait;

    /**
     * Seconds kept back for turning the failure into a response and recording the usage row.
     */
    private const MARGIN_SECONDS = 3.0;

    /**
     * Floor for the cap, so a request started with no budget left fails fast rather than never.
     */
    private const MINIMUM_SECONDS = 1.0;

    /**
     * Send the request with max_duration lowered to the remaining execution budget.
     *
     * @param string $method
     * @param string $url
     * @param array<string,mixed> $options
     * @return ResponseInterface
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $remaining = $this->remainingSeconds();
        if ($remaining !== null) {
            $requested = $options['max_duration'] ?? null;
            $options['max_duration'] = is_numeric($requested) && (float) $requested > 0
                ? min((float) $requested, $remaining)
                : $remaining;
        }

        return $this->client->request($method, $url, $options);
    }

    /**
     * Seconds a request may still take, or null when PHP imposes no execution time limit.
     *
     * @return float|null
     */
    private function remainingSeconds(): ?float
    {
        $limit = (int) ini_get('max_execution_time');
        if ($limit <= 0) {
            return null;
        }

        // phpcs:ignore Magento2.Security.Superglobal
        $startedAt = $_SERVER['REQUEST_TIME_FLOAT'] ?? null;
        $elapsed = is_float($startedAt) ? microtime(true) - $startedAt : 0.0;

        return max(self::MINIMUM_SECONDS, $limit - $elapsed - self::MARGIN_SECONDS);
    }
}
