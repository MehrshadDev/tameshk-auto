<?php

namespace App\Workflow\Support;

use App\Models\Credential;
use App\Workflow\Exceptions\NodeExecutionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

final class GeminiClient
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct(private readonly Credential $credential)
    {
        if ($this->credential->type !== 'gemini') {
            throw NodeExecutionException::because('نوع اعتبارنامه انتخابی برای Google Gemini معتبر نیست.');
        }

        $this->apiKey = (string) $this->credential->get('api_key');
        if (empty($this->apiKey)) {
            throw NodeExecutionException::because('کلید API در اعتبارنامه Gemini خالی است.');
        }

        $this->baseUrl = rtrim((string) config('services.gemini.base_url', 'https://generativelanguage.googleapis.com'), '/');
    }

    public function generateContent(
        string $model,
        array $contents,
        array $generationConfig = [],
        ?string $systemInstruction = null,
        array $tools = []
    ): array {
        $payload = ['contents' => $contents];

        if (! empty($generationConfig)) {
            $payload['generationConfig'] = $generationConfig;
        }

        if (! empty($systemInstruction)) {
            $payload['system_instruction'] = ['parts' => [['text' => $systemInstruction]]];
        }

        if (! empty($tools)) {
            $payload['tools'] = $tools;
        }

        return $this->call('POST', "/models/$model:generateContent", $payload, 120);
    }

    public function generateImages(string $prompt, array $options = []): array
    {
        $model = (string) ($options['model'] ?? 'imagen-3.0-generate-002');

        return $this->call('POST', "/models/$model:predict", [
            'instances'  => [['prompt' => $prompt]],
            'parameters' => [
                'sampleCount'   => max(1, min(4, (int) ($options['number_of_images'] ?? 1))),
                'aspectRatio'   => (string) ($options['aspect_ratio'] ?? '1:1'),
                'outputOptions' => ['mimeType' => (string) ($options['output_format'] ?? 'image/jpeg')],
            ],
        ], 120);
    }

    public function uploadMedia(string $content, string $mimeType, ?string $displayName = null): array
    {
        $uploadUrl = "https://generativelanguage.googleapis.com/upload/v1beta/files?key=$this->apiKey";

        $displayName = $displayName ?: 'file_' . uniqid();

        $metadata = json_encode([
            'file' => [
                'display_name' => $displayName,
            ],
        ]);

        $boundary = '-------' . md5((string) microtime(true));

        $body = "--$boundary\r\n"
            . "Content-Type: application/json; charset=UTF-8\r\n\r\n"
            . "$metadata\r\n"
            . "--$boundary\r\n"
            . "Content-Type: $mimeType\r\n\r\n"
            . "$content\r\n"
            . "--$boundary--\r\n";

        $response = Http::withHeaders([
            'X-Goog-Upload-Protocol' => 'multipart',
            'Content-Type' => "multipart/related; boundary=$boundary",
        ])->timeout(180)->withBody($body, "multipart/related; boundary=$boundary")->post($uploadUrl);

        $this->checkResponse($response);

        return (array) data_get($response->json(), 'file', []);
    }

    public function getFile(string $name): array
    {
        return $this->call('GET', '/' . $this->prefixed($name, 'files'));
    }

    public function deleteFile(string $name): bool
    {
        $this->call('DELETE', '/' . $this->prefixed($name, 'files'));

        return true;
    }

    public function createCorpus(string $displayName): array
    {
        return $this->call('POST', '/corpora', ['displayName' => $displayName]);
    }

    public function listCorpora(int $pageSize = 20): array
    {
        return (array) data_get(
            $this->call('GET', '/corpora', ['pageSize' => max(1, min(100, $pageSize))]),
            'corpora',
            [],
        );
    }

    public function deleteCorpus(string $name, bool $force = true): bool
    {
        $this->call('DELETE', '/' . $this->prefixed($name, 'corpora'), ['force' => $force]);

        return true;
    }

    public function uploadFileToCorpus(string $corpusName, string $title, string $content): array
    {
        $corpus = $this->prefixed($corpusName, 'corpora');
        $document = $this->call('POST', "/$corpus/documents", ['displayName' => $title]);
        $docName = (string) data_get($document, 'name');

        $chunk = $this->call('POST', "/$docName/chunks", [
            'data' => ['stringValue' => $content],
        ]);

        return ['document' => $document, 'chunk' => $chunk];
    }

    public function resolveInputPart(string $rawInput, string $defaultMime = 'image/jpeg'): array
    {
        $raw = trim($rawInput);

        if (str_starts_with($raw, 'https://generativelanguage.googleapis.com/') || str_starts_with($raw, 'files/')) {
            $uri = str_starts_with($raw, 'files/')
                ? "https://generativelanguage.googleapis.com/v1beta/$raw"
                : $raw;

            return [
                'fileData' => [
                    'mimeType' => $defaultMime,
                    'fileUri' => $uri,
                ],
            ];
        }

        if (preg_match('/^data:([a-zA-Z0-9_\-.\/]+);base64,(.+)$/', $raw, $matches)) {
            return [
                'inlineData' => [
                    'mimeType' => $matches[1],
                    'data' => $matches[2],
                ],
            ];
        }

        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
            try {
                $fileResponse = Http::timeout(60)->get($raw);
                if ($fileResponse->failed()) {
                    throw NodeExecutionException::because("دریافت فایل از آدرس $raw با خطا مواجه شد.");
                }

                $detectedMime = $fileResponse->header('Content-Type') ?: $defaultMime;
                $detectedMime = explode(';', $detectedMime)[0];

                return [
                    'inlineData' => [
                        'mimeType' => trim($detectedMime),
                        'data' => base64_encode($fileResponse->body()),
                    ],
                ];
            } catch (Throwable $e) {
                throw NodeExecutionException::because('خطا در دانلود فایل ورودی: ' . $e->getMessage());
            }
        }

        return [
            'inlineData' => [
                'mimeType' => $defaultMime,
                'data' => $raw,
            ],
        ];
    }

    public function downloadUrl(string $url): string
    {
        try {
            $response = Http::timeout(90)->get($url);
            if ($response->failed()) {
                throw NodeExecutionException::because("خطای دانلود با کد {$response->status()}");
            }
            return base64_encode($response->body());
        } catch (Throwable $e) {
            throw NodeExecutionException::because('عدم امکان دانلود فایل: ' . $e->getMessage());
        }
    }

    private function buildEndpoint(string $path): string
    {
        $cleanPath = '/' . ltrim($path, '/');

        if (str_ends_with($this->baseUrl, '/v1beta')) {
            return $this->baseUrl . $cleanPath;
        }

        return "$this->baseUrl/v1beta$cleanPath";
    }

    private function checkResponse(Response $response): void
    {
        if ($response->failed()) {
            throw NodeExecutionException::because(GeminiError::describe($response));
        }
    }

    public static function text(array $response): string
    {
        return (string) data_get($response, 'candidates.0.content.parts.0.text', '');
    }

    public function ask(string $model, array $parts, array $generationConfig = [], ?string $systemInstruction = null): array
    {
        $raw = $this->generateContent(
            $model,
            [['role' => 'user', 'parts' => $parts]],
            $generationConfig,
            $systemInstruction,
        );

        return ['text' => self::text($raw), 'raw' => $raw];
    }

    private function call(string $method, string $path, array $data = [], int $timeout = 30): array
    {
        $response = Http::withHeaders(['x-goog-api-key' => $this->apiKey])
            ->timeout($timeout)
            ->{strtolower($method)}($this->buildEndpoint($path), $data);

        $this->checkResponse($response);

        return (array) $response->json();
    }

    private function prefixed(string $name, string $prefix): string
    {
        return str_starts_with($name, "$prefix/") ? $name : "$prefix/$name";
    }
}
