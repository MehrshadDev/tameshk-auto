<?php

namespace App\Http\Controllers;

use App\Credentials\Exceptions\InvalidCredentialException;
use App\Credentials\Registry\CredentialRegistry;
use App\Http\Requests\StoreCredentialRequest;
use App\Http\Requests\UpdateCredentialRequest;
use App\Http\Resources\CredentialResource;
use App\Models\Credential;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Throwable;

class CredentialController extends Controller
{
    public function __construct(
        private readonly CredentialRegistry $registry,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Credential::class);
        $q = $request->user()->credentials()->latest();
        if ($request->filled('type')) {
            $q->where('type', $request->string('type')->toString());
        }
        $credentials = $q->get();
        return response()->json([
            'data' => CredentialResource::collection($credentials),
            'meta' => [
                'types' => $this->registry->definitionsAsArray(),
            ],
        ]);
    }

    public function store(StoreCredentialRequest $request): JsonResponse
    {
        $type = $request->string('type')->toString();
        $data = $request->array('data');

        try {
            $this->registry->make($type)->validate($data);
        } catch (InvalidCredentialException $e) {
            throw ValidationException::withMessages([
                'data' => [$e->getMessage()],
            ]);
        }

        $credential = $request->user()->credentials()->create([
            'type' => $type,
            'name' => $request->string('name')->toString(),
            'data' => $data,
        ]);

        return response()->json([
            'data' => new CredentialResource($credential),
        ], 201);
    }

    public function show(Credential $credential): JsonResponse
    {
        Gate::authorize('view', $credential);

        return response()->json([
            'data' => new CredentialResource($credential),
        ]);
    }

    public function update(UpdateCredentialRequest $request, Credential $credential): JsonResponse
    {
        $payload = [];

        if ($request->has('name')) {
            $payload['name'] = $request->string('name')->toString();
        }

        if ($request->has('data')) {
            $data = $request->array('data');

            try {
                $this->registry->make($credential->type)->validate($data);
            } catch (InvalidCredentialException $e) {
                throw ValidationException::withMessages([
                    'data' => [$e->getMessage()],
                ]);
            }

            $payload['data'] = $data;
        }

        $credential->update($payload);

        return response()->json([
            'data' => new CredentialResource($credential->fresh()),
        ]);
    }

    public function destroy(Credential $credential): JsonResponse
    {
        Gate::authorize('delete', $credential);

        $credential->delete();

        return response()->json(null, 204);
    }

    public function models(Credential $credential): JsonResponse
    {
        Gate::authorize('view', $credential);

        if ($credential->type !== 'gemini') {
            return response()->json(['data' => []]);
        }

        $apiKey = $credential->get('api_key');

        if (! $apiKey) {
            return response()->json(['data' => []], 400);
        }

        $cacheKey = 'gemini_text_models_v2_' . md5($apiKey);

        $models = Cache::remember($cacheKey, now()->addHours(6), function () use ($apiKey) {
            try {
                $baseUrl = rtrim(config('services.gemini.base_url', 'https://generativelanguage.googleapis.com'), '/');
                $endpoint = str_ends_with($baseUrl, '/v1beta')
                    ? "$baseUrl/models"
                    : "$baseUrl/v1beta/models";

                $response = Http::withHeaders(['x-goog-api-key' => $apiKey])->timeout(10)->get($endpoint);

                if ($response->successful()) {
                    $rawModels = data_get($response->json(), 'models', []);

                    $models = collect($rawModels)
                        ->filter(fn ($m) => $this->isTextModel($m))
                        ->sortBy(fn ($m) => str_contains($m['name'] ?? '', 'preview') ? 1 : 0)
                        ->unique(fn ($m) => $this->shortLabel($m['displayName'] ?? ''))
                        ->map(function ($m) {
                            $id = str_replace('models/', '', $m['name'] ?? '');

                            return [
                                'value'       => $id,
                                'label'       => $this->shortLabel($m['displayName'] ?? $id),
                                'description' => $m['description'] ?? '',
                            ];
                        })
                        ->sortByDesc('value')
                        ->values()
                        ->all();

                    if (! empty($models)) {
                        return $models;
                    }
                }
            } catch (Throwable $e) {
                report($e);
            }

            return [
                [
                    'value' => 'gemini-3.8-flash',
                    'label' => 'Gemini 3.8 Flash',
                    'description' => 'مدل سریع و قدرتمند برای کارهای عمومی، کدنویسی و پردازش چندمرحله‌ای',
                ],
                [
                    'value' => 'gemini-4-argon',
                    'label' => 'Gemini 4 Argon',
                    'description' => 'مدل پرچم‌دار گوگل برای استدلال عمیق، کدنویسی پیچیده و حل مسائل دشوار',
                ],
                [
                    'value' => 'gemini-3.1-pro-preview',
                    'label' => 'Gemini 3.1 Pro',
                    'description' => 'مدل پیشرفته برای استدلال، تحلیل و توسعه نرم‌افزار',
                ],
                [
                    'value' => 'gemini-3.6-flash',
                    'label' => 'Gemini 3.6 Flash',
                    'description' => 'مدل پایدار نسل قبل برای وظایف عمومی و پردازش چندوجهی',
                ],
                [
                    'value' => 'gemini-3.5-flash-lite',
                    'label' => 'Gemini 3.5 Flash-Lite',
                    'description' => 'گزینه اقتصادی و سریع برای پردازش تعداد زیادی درخواست',
                ],
            ];
        });

        return response()->json(['data' => $models]);
    }

    private function isTextModel(array $model): bool
    {
        if (! in_array('generateContent', $model['supportedGenerationMethods'] ?? [], true)) {
            return false;
        }

        $id = str_replace('models/', '', $model['name'] ?? '');

        if (! str_starts_with($id, 'gemini-')) {
            return false;
        }

        return ! preg_match(
            '/image|nano-banana|tts|transcribe|lyria|embed|aqa|deep-research|antigravity|robotics|computer-use|omni|customtools|live|audio/i',
            $id
        );
    }

    private function shortLabel(string $name): string
    {
        $name = preg_replace('/\s*\(.*?\)\s*/', ' ', $name);
        $name = preg_replace('/\s+(\d{2}-\d{4}|\d{2}-\d{2}|\d{3})$/', '', trim($name));
        $name = preg_replace('/\s+Preview$/i', '', trim($name));

        return trim($name);
    }
}
