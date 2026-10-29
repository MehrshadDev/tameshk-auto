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

        $apiKey = trim((string) $credential->get('api_key'));

        if ($apiKey === '') {
            return response()->json(['data' => []], 400);
        }

        return response()->json([
            'data' => match ($credential->type) {
                'gemini'     => $this->geminiModels($apiKey),
                'openai'     => $this->openAiModels($credential),
                'anthropic'  => $this->anthropicModels(),
                'deepseek'   => $this->deepSeekModels(),
                'groq'       => $this->groqModels($apiKey),
                'openrouter' => $this->openRouterModels(),
                default      => [],
            },
        ]);
    }

    private function geminiModels(string $apiKey): array
    {
        $cacheKey = 'gemini_text_models_v2_' . md5($apiKey);

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($apiKey) {
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
                ['value' => 'gemini-2.5-flash', 'label' => 'Gemini 2.5 Flash', 'description' => 'مدل سریع و هوشمند برای کارهای روزمره و ایجنت‌ها'],
                ['value' => 'gemini-2.5-pro', 'label' => 'Gemini 2.5 Pro', 'description' => 'مدل پیشرفته برای استدلال عمیق و کدنویسی'],
                ['value' => 'gemini-2.0-flash', 'label' => 'Gemini 2.0 Flash', 'description' => 'مدل پایدار و پرسرعت چندوجهی'],
            ];
        });
    }

    private function openAiModels(Credential $credential): array
    {
        return [
            ['value' => 'gpt-4.1', 'label' => 'GPT-4.1', 'description' => 'مدل پرچم‌دار OpenAI برای کدنویسی، دستورپذیری دقیق و کار با ابزارها'],
            ['value' => 'gpt-4.1-mini', 'label' => 'GPT-4.1 Mini', 'description' => 'سریع، هوشمند و مقرون‌به‌صرفه برای اکثر ورکفلوها'],
            ['value' => 'gpt-4o', 'label' => 'GPT-4o', 'description' => 'مدل همه‌کاره و چندوجهی OpenAI'],
            ['value' => 'gpt-4o-mini', 'label' => 'GPT-4o Mini', 'description' => 'مدل سبک، سریع و اقتصادی'],
            ['value' => 'o3-mini', 'label' => 'o3-mini (Reasoning)', 'description' => 'مدل استدلال‌گر برای مسائل ریاضی، منطقی و تحلیلی'],
        ];
    }

    private function anthropicModels(): array
    {
        return [
            ['value' => 'claude-sonnet-4-20250514', 'label' => 'Claude 4 Sonnet', 'description' => 'بهترین تعادل هوش، سرعت و دقت در اجرای ابزارها و کدنویسی'],
            ['value' => 'claude-opus-4-20250514', 'label' => 'Claude 4 Opus', 'description' => 'قدرتمندترین مدل Anthropic برای وظایف بسیار پیچیده'],
            ['value' => 'claude-3-7-sonnet-latest', 'label' => 'Claude 3.7 Sonnet', 'description' => 'مدل استدلال هیبریدی با دقت بسیار بالا'],
            ['value' => 'claude-3-5-haiku-latest', 'label' => 'Claude 3.5 Haiku', 'description' => 'سریع‌ترین و اقتصادی‌ترین مدل Claude'],
        ];
    }

    private function deepSeekModels(): array
    {
        return [
            ['value' => 'deepseek-chat', 'label' => 'DeepSeek V3 (Chat)', 'description' => 'مدل سریع و فوق‌العاده قوی برای مکالمه، کدنویسی و فراخوانی ابزارها'],
            ['value' => 'deepseek-reasoner', 'label' => 'DeepSeek R1 (Reasoner)', 'description' => 'مدل استدلال‌گر عمیق برای حل مسائل پیچیده'],
        ];
    }

    private function groqModels(string $apiKey): array
    {
        return [
            ['value' => 'llama-3.3-70b-versatile', 'label' => 'Llama 3.3 70B Versatile', 'description' => 'مدل قدرتمند متا با سرعت فوق‌العاده بالا و پشتیبانی از ابزارها'],
            ['value' => 'llama-3.1-8b-instant', 'label' => 'Llama 3.1 8B Instant', 'description' => 'پاسخ‌دهی آنی و فوق‌سریع برای پردازش‌های سبک'],
            ['value' => 'qwen-qwq-32b', 'label' => 'Qwen QwQ 32B', 'description' => 'مدل استدلال‌گر و چندزبانه قدرتمند (عالی برای زبان فارسی)'],
            ['value' => 'deepseek-r1-distill-llama-70b', 'label' => 'DeepSeek R1 Distill Llama 70B', 'description' => 'ترکیب قدرت استدلال DeepSeek R1 با سرعت Groq'],
        ];
    }

    private function openRouterModels(): array
    {
        return [
            ['value' => 'openai/gpt-4o-mini', 'label' => 'OpenAI: GPT-4o Mini', 'description' => 'سریع و اقتصادی از طریق OpenRouter'],
            ['value' => 'openai/gpt-4.1', 'label' => 'OpenAI: GPT-4.1', 'description' => 'مدل پرچم‌دار OpenAI'],
            ['value' => 'anthropic/claude-sonnet-4', 'label' => 'Anthropic: Claude 4 Sonnet', 'description' => 'مدل محبوب Claude 4 از طریق OpenRouter'],
            ['value' => 'google/gemini-2.5-flash', 'label' => 'Google: Gemini 2.5 Flash', 'description' => 'مدل سریع گوگل از طریق OpenRouter'],
            ['value' => 'deepseek/deepseek-chat-v3-0324', 'label' => 'DeepSeek: V3', 'description' => 'مدل DeepSeek V3 از طریق OpenRouter'],
            ['value' => 'meta-llama/llama-3.3-70b-instruct', 'label' => 'Meta: Llama 3.3 70B', 'description' => 'مدل متن‌باز متا'],
        ];
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
