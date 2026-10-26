<?php

namespace App\Workflow\Support;

use App\Workflow\Exceptions\NodeExecutionException;
use Illuminate\Support\Facades\Http;
use Throwable;

final class SafeHttpClient
{
    public function execute(
        string $method,
        string $url,
        array $headers = [],
        mixed $body = null,
        int $timeout = 30
    ): array {
        if (empty($url)) {
            throw NodeExecutionException::because('آدرس URL الزامی است.');
        }

        $this->assertSafeUrl($url);

        $method = strtoupper($method);

        try {
            $request = Http::withHeaders($headers)
                ->withOptions([
                    'allow_redirects' => false,
                    'verify' => true,
                ])
                ->timeout($timeout);

            $response = match ($method) {
                'GET' => $request->get($url),
                'POST' => $request->post($url, $body ?? []),
                'PUT' => $request->put($url, $body ?? []),
                'PATCH' => $request->patch($url, $body ?? []),
                'DELETE' => $request->delete($url, $body ?? []),
                default => throw NodeExecutionException::because("متد پشتیبانی نمی‌شود: $method"),
            };
        } catch (NodeExecutionException $e) {
            throw $e;
        } catch (Throwable) {
            throw NodeExecutionException::because('برقراری ارتباط با آدرس مقصد با خطا مواجه شد.');
        }

        if ($response->failed()) {
            throw NodeExecutionException::because("سرور مقصد با کد وضعیت {$response->status()} پاسخ داد.");
        }

        $contentType = $response->header('Content-Type') ?? '';
        $isHtml = str_contains($contentType, 'html');
        $parsedBody = $response->json() ?? $response->body();

        return [
            'status' => $response->status(),
            'headers' => $response->headers(),
            'body' => $parsedBody,
            'data' => $parsedBody,
            'content_type' => $contentType,
            'is_html' => $isHtml,
        ];
    }

    public function assertSafeUrl(string $url): void
    {
        $parsed = parse_url($url);

        if (! $parsed || ! isset($parsed['scheme'], $parsed['host'])) {
            throw NodeExecutionException::because('آدرس URL نامعتبر است.');
        }

        $scheme = strtolower($parsed['scheme']);

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw NodeExecutionException::because('فقط پروتکل‌های HTTP و HTTPS مجاز هستند.');
        }

        $host = $parsed['host'];

        if ($host === '') {
            throw NodeExecutionException::because('آدرس URL نامعتبر است.');
        }

        if (! config('workflow.http_block_private', true)) {
            return;
        }

        $ips = $this->resolveHostToIps($host);

        if (empty($ips)) {
            throw NodeExecutionException::because('آدرس مقصد قابل دسترسی نیست.');
        }

        foreach ($ips as $ip) {
            if ($this->isPrivateIp($ip)) {
                throw NodeExecutionException::because(
                    config('app.debug')
                        ? "دسترسی به آدرس‌های داخلی یا رزرو‌شده مجاز نیست. ({$host} → {$ip})"
                        : 'دسترسی به آدرس‌های داخلی یا رزرو‌شده مجاز نیست.'
                );
            }
        }
    }

    private function resolveHostToIps(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $ips = [];

        $ipv4 = @gethostbynamel($host);
        if (is_array($ipv4)) {
            $ips = array_merge($ips, $ipv4);
        }

        $records = @dns_get_record($host, DNS_AAAA);
        if (is_array($records)) {
            foreach ($records as $record) {
                if (isset($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        return array_values(array_unique($ips));
    }

    private function isPrivateIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $packed = @inet_pton($ip);
            if ($packed === false) {
                return true;
            }

            if ($ip === '::1' || $ip === '::') {
                return true;
            }

            $first = ord($packed[0]);
            $second = ord($packed[1]);

            if ($first === 0xfe && ($second & 0xc0) === 0x80) {
                return true;
            }

            if (($first & 0xfe) === 0xfc) {
                return true;
            }

            return false;
        }

        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return true;
        }

        $long = ip2long($ip);
        if ($long === false) {
            return true;
        }

        $ranges = [
            ['10.0.0.0', '10.255.255.255'],
            ['172.16.0.0', '172.31.255.255'],
            ['192.168.0.0', '192.168.255.255'],
            ['127.0.0.0', '127.255.255.255'],
            ['169.254.0.0', '169.254.255.255'],
            ['0.0.0.0', '0.255.255.255'],
            ['100.64.0.0', '100.127.255.255'],
            ['192.0.0.0', '192.0.0.255'],
            ['192.0.2.0', '192.0.2.255'],
            ['198.18.0.0', '198.19.255.255'],
            ['198.51.100.0', '198.51.100.255'],
            ['203.0.113.0', '203.0.113.255'],
            ['224.0.0.0', '255.255.255.255'],
        ];

        foreach ($ranges as [$min, $max]) {
            if ($long >= ip2long($min) && $long <= ip2long($max)) {
                return true;
            }
        }

        return false;
    }
}
