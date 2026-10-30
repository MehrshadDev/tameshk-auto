<?php

namespace App\Workflow\Support;

use App\Models\Credential;
use App\Workflow\Exceptions\NodeExecutionException;
use Illuminate\Support\Facades\Http;

final class GmailClient
{
    private const BASE_API = 'https://gmail.googleapis.com/gmail/v1/users/me';

    public function __construct(private readonly Credential $credential)
    {
        if ($this->credential->type !== 'gmail') {
            throw NodeExecutionException::because('نوع اعتبارنامه انتخابی نامعتبر است.');
        }
    }

    public function getValidAccessToken(): string
    {
        return (new GoogleTokenProvider($this->credential))->getValidAccessToken();
    }

    private function request(string $method, string $path, array $data = [], int $timeout = 30): array
    {
        $response = Http::withToken($this->getValidAccessToken())
            ->timeout($timeout)
            ->{$method}(self::BASE_API . $path, $data);

        if ($response->failed()) {
            throw NodeExecutionException::because($this->describeError($response->json(), $response->body()));
        }

        return (array) $response->json();
    }

    public function sendMessage(
        string $to,
        string $subject,
        string $body,
        bool $isHtml = false,
        ?string $threadId = null,
        ?string $inReplyTo = null,
        ?string $references = null,
        array $cc = [],
        array $bcc = []
    ): array {
        $payload = [
            'raw' => $this->base64UrlEncode($this->buildMimeMessage(
                to: $to,
                subject: $subject,
                body: $body,
                isHtml: $isHtml,
                inReplyTo: $inReplyTo,
                references: $references,
                cc: $cc,
                bcc: $bcc
            )),
        ];

        if (! empty($threadId)) {
            $payload['threadId'] = $threadId;
        }

        return $this->request('post', '/messages/send', $payload);
    }

    public function getMessage(string $messageId, string $format = 'full'): array
    {
        return $this->parseMessage($this->request('get', "/messages/$messageId", ['format' => $format]));
    }

    public function listMessages(string $query = '', int $maxResults = 10, ?string $pageToken = null): array
    {
        $params = ['maxResults' => min(max($maxResults, 1), 100)];

        if (trim($query) !== '') {
            $params['q'] = trim($query);
        }

        if (! empty($pageToken)) {
            $params['pageToken'] = $pageToken;
        }

        return $this->request('get', '/messages', $params);
    }

    public function modifyMessage(string $messageId, array $addLabelIds = [], array $removeLabelIds = []): array
    {
        return $this->request('post', "/messages/$messageId/modify", [
            'addLabelIds'    => array_values($addLabelIds),
            'removeLabelIds' => array_values($removeLabelIds),
        ], 20);
    }

    public function trashMessage(string $messageId): array
    {
        return $this->request('post', "/messages/$messageId/trash", [], 20);
    }

    public function deleteMessage(string $messageId): bool
    {
        $this->request('delete', "/messages/$messageId", [], 20);

        return true;
    }

    public function getProfile(): array
    {
        return $this->request('get', '/profile', [], 15);
    }

    private function buildMimeMessage(
        string $to,
        string $subject,
        string $body,
        bool $isHtml,
        ?string $inReplyTo,
        ?string $references,
        array $cc,
        array $bcc
    ): string {
        $headers = [];
        $headers[] = 'To: ' . trim($to);

        if (! empty($cc)) {
            $headers[] = 'Cc: ' . implode(', ', array_map('trim', $cc));
        }

        if (! empty($bcc)) {
            $headers[] = 'Bcc: ' . implode(', ', array_map('trim', $bcc));
        }

        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $headers[] = 'Subject: ' . $encodedSubject;

        if (! empty($inReplyTo)) {
            $headers[] = 'In-Reply-To: ' . trim($inReplyTo);
        }

        if (! empty($references)) {
            $headers[] = 'References: ' . trim($references);
        }

        $headers[] = 'MIME-Version: 1.0';
        $headers[] = $isHtml
            ? 'Content-Type: text/html; charset=UTF-8'
            : 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: base64';

        return implode("\r\n", $headers) . "\r\n\r\n" . base64_encode($body);
    }

    private function parseMessage(array $raw): array
    {
        $payload = (array) ($raw['payload'] ?? []);
        $headers = collect((array) ($payload['headers'] ?? []));

        $getHeader = fn ($name) => $headers->first(
            fn ($h) => strcasecmp($h['name'] ?? '', $name) === 0
        )['value'] ?? '';

        $textBody = '';
        $htmlBody = '';

        $this->extractParts($payload, $textBody, $htmlBody);

        return [
            'id' => $raw['id'] ?? '',
            'threadId' => $raw['threadId'] ?? '',
            'labelIds' => $raw['labelIds'] ?? [],
            'snippet' => $raw['snippet'] ?? '',
            'historyId' => $raw['historyId'] ?? '',
            'internalDate' => $raw['internalDate'] ?? '',
            'from' => $getHeader('From'),
            'to' => $getHeader('To'),
            'cc' => $getHeader('Cc'),
            'subject' => $getHeader('Subject'),
            'date' => $getHeader('Date'),
            'messageId' => $getHeader('Message-ID'),
            'inReplyTo' => $getHeader('In-Reply-To'),
            'references' => $getHeader('References'),
            'text' => $textBody ?: ($raw['snippet'] ?? ''),
            'html' => $htmlBody,
            'raw' => $raw,
        ];
    }

    private function extractParts(array $part, string &$textBody, string &$htmlBody): void
    {
        $mimeType = $part['mimeType'] ?? '';
        $data = $part['body']['data'] ?? null;

        if ($data) {
            $decoded = $this->base64UrlDecode($data);
            if ($mimeType === 'text/plain' && empty($textBody)) {
                $textBody = $decoded;
            } elseif ($mimeType === 'text/html' && empty($htmlBody)) {
                $htmlBody = $decoded;
            }
        }

        foreach ((array) ($part['parts'] ?? []) as $subPart) {
            $this->extractParts($subPart, $textBody, $htmlBody);
        }
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return (string) base64_decode(strtr($data, '-_', '+/'));
    }

    private function describeError(?array $json, string $fallback): string
    {
        $msg = data_get($json, 'error.message');
        if ($msg) {
            return 'خطای جیمیل: ' . $msg;
        }

        return 'خطای ارتباط با جیمیل: ' . strip_tags($fallback);
    }
}
