<?php

namespace App\Services;

use App\Exceptions\WhishApiException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhishPaymentService
{
    private function baseUrl(): string
    {
        $env = config('whish.env', 'testing');
        $urls = config('whish.base_urls', []);
        $base = $urls[$env] ?? ($urls['testing'] ?? '');
        return rtrim($base, '/');
    }

    private function debugEnabled(): bool
    {
        return (bool) config('whish.debug', false);
    }

    private function headers(): array
    {
        $channel = config('whish.channel');
        $secret = config('whish.secret');
        $website = config('whish.website_url', config('app.url'));

        if (empty($channel) || empty($secret)) {
            throw new WhishApiException('Whish API credentials are not configured (channel/secret).');
        }

        return [
            'channel' => $channel,
            'secret' => $secret,
            'websiteurl' => $website,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    private function mask(string $value): string
    {
        if ($value === '') return '';
        $len = strlen($value);
        if ($len <= 4) return str_repeat('*', $len);
        return str_repeat('*', max(0, $len - 4)) . substr($value, -4);
    }

    private function sanitizedHeaders(): array
    {
        try {
            $h = $this->headers();
        } catch (\Throwable $e) {
            // If headers() throws due to missing creds, return minimal info
            return [
                'channel' => '<missing>',
                'secret' => '<missing>',
                'websiteurl' => config('whish.website_url', config('app.url')),
            ];
        }
        $h['channel'] = $this->mask((string)($h['channel'] ?? ''));
        $h['secret'] = $this->mask((string)($h['secret'] ?? ''));
        // keep websiteurl as-is for debugging domain mismatches
        unset($h['Accept'], $h['Content-Type']);
        return $h;
    }

    private function http()
    {
        $timeout = (int) config('whish.timeout', 15);
        return Http::withHeaders($this->headers())
            ->baseUrl($this->baseUrl())
            ->timeout($timeout)
            ->acceptJson()
            ->asJson();
    }

    /**
     * Get account balance (LBP shown in docs; API may evolve).
     */
    public function getBalance(): float
    {
        $response = $this->http()->get('/payment/account/balance');
        if (!$response->ok()) {
            throw new WhishApiException('HTTP error from Whish: ' . $response->status(), $response->status());
        }
        $json = $response->json();
        if (!($json['status'] ?? false)) {
            $code = (int) ($json['code'] ?? 0);
            $msg = is_array($json['dialog'] ?? null) ? (($json['dialog']['message'] ?? 'Whish balance failed')) : (($json['dialog'] ?? 'Whish balance failed'));
            throw new WhishApiException($msg, $code);
        }
        $balance = $json['data']['balanceDetails']['balance'] ?? null;
        if ($balance === null) {
            throw new WhishApiException('Unexpected balance response format.');
        }
        return (float) $balance;
    }

    /**
     * Get current rate/fees for a given amount/currency.
     */
    public function getRate(float $amount, ?string $currency = null): float
    {
        $endpoint = '/payment/whish/rate';
        $payload = [
            'amount' => $amount,
            'currency' => $currency ?: config('whish.default_currency', 'USD'),
        ];
        if ($this->debugEnabled()) {
            Log::info('[Whish] Request getRate', [
                'method' => 'POST',
                'url' => $this->baseUrl() . $endpoint,
                'headers' => $this->sanitizedHeaders(),
                'payload' => $payload,
            ]);
        }
        $response = $this->http()->post($endpoint, $payload);
        if (!$response->ok()) {
            Log::error('[Whish] HTTP error getRate', [
                'status' => $response->status(),
                'url' => $this->baseUrl() . $endpoint,
                'body' => $response->body(),
            ]);
            throw new WhishApiException('HTTP error from Whish: ' . $response->status(), $response->status());
        }
        $json = $response->json();
        if ($this->debugEnabled()) {
            Log::info('[Whish] Response getRate', [
                'status' => $response->status(),
                'json' => $json,
            ]);
        }
        if (!($json['status'] ?? false)) {
            $code = (int) ($json['code'] ?? 0);
            $msg = is_array($json['dialog'] ?? null) ? (($json['dialog']['message'] ?? 'Whish rate failed')) : (($json['dialog'] ?? 'Whish rate failed'));
            Log::error('[Whish] Business error getRate', [
                'code' => $code,
                'dialog' => $json['dialog'] ?? null,
                'json' => $json,
            ]);
            throw new WhishApiException($msg, $code);
        }
        $rate = $json['data']['rate'] ?? null;
        if ($rate === null) {
            Log::error('[Whish] Unexpected rate response format', [ 'json' => $json ]);
            throw new WhishApiException('Unexpected rate response format.');
        }
        return (float) $rate;
    }

    /**
     * Create a Whish payment link (W2W) and return the payment URL.
     *
     * @param float $amount
     * @param string|null $currency Supported currencies: LBP/USD/AED
     * @param string $invoice Human-readable description
     * @param int|string $externalId Third-party ID
     * @param string|null $successCallbackUrl GET callback for success (server-to-server)
     * @param string|null $failureCallbackUrl GET callback for failure (server-to-server)
     * @param string|null $successRedirectUrl GET redirect for success (user-browser)
     * @param string|null $failureRedirectUrl GET redirect for failure (user-browser)
     */
    public function createPaymentLink(
        float $amount,
        ?string $currency,
        string $invoice,
        $externalId,
        ?string $successCallbackUrl = null,
        ?string $failureCallbackUrl = null,
        ?string $successRedirectUrl = null,
        ?string $failureRedirectUrl = null
    ): string {
        $endpoint = '/payment/whish';
        $payload = [
            'amount' => $amount,
            'currency' => $currency ?: config('whish.default_currency', 'USD'),
            'invoice' => $invoice,
            'externalId' => is_numeric($externalId) ? (int)$externalId : (string)$externalId,
            'successCallbackUrl' => $successCallbackUrl ?: config('whish.default_success_callback_url'),
            'failureCallbackUrl' => $failureCallbackUrl ?: config('whish.default_failure_callback_url'),
            'successRedirectUrl' => $successRedirectUrl ?: config('whish.default_success_redirect_url'),
            'failureRedirectUrl' => $failureRedirectUrl ?: config('whish.default_failure_redirect_url'),
        ];
        if ($this->debugEnabled()) {
            Log::info('[Whish] Request createPaymentLink', [
                'method' => 'POST',
                'url' => $this->baseUrl() . $endpoint,
                'headers' => $this->sanitizedHeaders(),
                'payload' => $payload,
            ]);
        }
        $response = $this->http()->post($endpoint, $payload);
        if (!$response->ok()) {
            Log::error('[Whish] HTTP error createPaymentLink', [
                'status' => $response->status(),
                'url' => $this->baseUrl() . $endpoint,
                'body' => $response->body(),
            ]);
            throw new WhishApiException('HTTP error from Whish: ' . $response->status(), $response->status());
        }
        $json = $response->json();
        if ($this->debugEnabled()) {
            Log::info('[Whish] Response createPaymentLink', [
                'status' => $response->status(),
                'json' => $json,
            ]);
        }
        if (!($json['status'] ?? false)) {
            $code = (int) ($json['code'] ?? 0);
            $msg = is_array($json['dialog'] ?? null) ? (($json['dialog']['message'] ?? 'Whish payment link failed')) : (($json['dialog'] ?? 'Whish payment link failed'));
            Log::error('[Whish] Business error createPaymentLink', [
                'code' => $code,
                'dialog' => $json['dialog'] ?? null,
                'json' => $json,
            ]);
            throw new WhishApiException($msg, $code);
        }
        // Try multiple possible keys for the payment URL
        $url = $json['data']['whishUrl'] 
            ?? $json['data']['collectUrl'] 
            ?? $json['data']['paymentUrl'] 
            ?? $json['data']['url'] 
            ?? null;
            
        if (!$url || !is_string($url)) {
            Log::error('[Whish] Unexpected payment link response format', [ 'json' => $json ]);
            throw new WhishApiException('Unexpected payment link response format.');
        }
        
        // Ensure URL has protocol (some Whish responses may omit https://)
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $url = 'https://' . $url;
        }
        
        return $url;
    }

    /**
     * Get collect status by externalId.
     * Returns 'success', 'failed', or 'pending'.
     */
    public function getCollectStatus(string $currency, $externalId): string
    {
        $endpoint = '/payment/collect/status';
        $payload = [
            'currency' => $currency ?: config('whish.default_currency', 'USD'),
            'externalId' => is_numeric($externalId) ? (int)$externalId : (string)$externalId,
        ];
        if ($this->debugEnabled()) {
            Log::info('[Whish] Request getCollectStatus', [
                'method' => 'POST',
                'url' => $this->baseUrl() . $endpoint,
                'headers' => $this->sanitizedHeaders(),
                'payload' => $payload,
            ]);
        }
        $response = $this->http()->post($endpoint, $payload);
        if (!$response->ok()) {
            Log::error('[Whish] HTTP error getCollectStatus', [
                'status' => $response->status(),
                'url' => $this->baseUrl() . $endpoint,
                'body' => $response->body(),
            ]);
            throw new WhishApiException('HTTP error from Whish: ' . $response->status(), $response->status());
        }
        $json = $response->json();
        if ($this->debugEnabled()) {
            Log::info('[Whish] Response getCollectStatus', [
                'status' => $response->status(),
                'json' => $json,
            ]);
        }
        if (!($json['status'] ?? false)) {
            $code = (int) ($json['code'] ?? 0);
            $msg = is_array($json['dialog'] ?? null) ? (($json['dialog']['message'] ?? 'Whish collect status failed')) : (($json['dialog'] ?? 'Whish collect status failed'));
            Log::error('[Whish] Business error getCollectStatus', [
                'code' => $code,
                'dialog' => $json['dialog'] ?? null,
                'json' => $json,
            ]);
            throw new WhishApiException($msg, $code);
        }
        // Some docs may present spacing inconsistencies; check both possibilities.
        $status = $json['data']['collectStatus']
            ?? ($json['data'][' collectStatus'] ?? null);
        if (!$status || !is_string($status)) {
            Log::error('[Whish] Unexpected collect status response format', [ 'json' => $json ]);
            throw new WhishApiException('Unexpected collect status response format.');
        }
        return $status;
    }
}
