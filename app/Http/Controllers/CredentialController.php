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
                'openai'     => $this->openAiModels(),
                'anthropic'  => $this->anthropicModels(),
                'deepseek'   => $this->deepSeekModels(),
                'groq'       => $this->groqModels(),
                'openrouter' => $this->openRouterModels(),
                default      => [],
            },
        ]);
    }


    private function geminiModels(string $apiKey): array
    {
        $cacheKey = 'gemini_text_models_v3_' . md5($apiKey);

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

            return $this->catalog([
                ['gemini-4-argon', 'Gemini 4 Argon', 'جدیدترین و قدرتمندترین مدل پرچم‌دار گوگل برای استدلال پیچیده و ایجنت‌ها'],
                ['gemini-3.8-flash', 'Gemini 3.8 Flash', 'مدل فوق‌سریع، هوشمند و بهینه برای کارهای روزمره و ایجنت‌ها'],
                ['gemini-3.5-flash', 'Gemini 3.5 Flash', 'تعادل عالی سرعت و دقت برای پردازش‌های چندوجهی'],
                ['gemini-3.5-flash-lite', 'Gemini 3.5 Flash-Lite', 'سریع‌ترین و اقتصادی‌ترین مدل برای پردازش‌های سبک و پرتعداد'],
                ['gemini-3.1-pro-preview', 'Gemini 3.1 Pro', 'مدل پیشرفته برای استدلال عمیق، کدنویسی و تحلیل اسناد بزرگ'],
            ]);
        });
    }

    private function catalog(array $rows): array
    {
        return array_map(fn (array $row) => [
            'value'       => $row[0],
            'label'       => $row[1],
            'description' => $row[2],
        ], $rows);
    }

    private function openAiModels(): array
    {
        return $this->catalog([
            ['gpt-6-astra', 'GPT-6 Astra', 'قدرتمندترین مدل پرچم‌دار OpenAI برای استدلال پیچیده، کدنویسی و ایجنت‌ها'],
            ['gpt-6.1-sol', 'GPT-6.1 Sol', 'تعادل فوق‌العاده هوش و هزینه؛ بهترین انتخاب برای اکثر ورکفلوهای حرفه‌ای'],
            ['gpt-6.1-terra', 'GPT-6.1 Terra', 'مدل سریع و دقیق برای نگارش طبیعی، تحلیل بصری و اجرای ابزارها'],
            ['gpt-6-luna', 'GPT-6 Luna', 'سریع‌ترین و اقتصادی‌ترین مدل نسل ۶ برای پردازش‌های سبک و پرتعداد'],
            ['gpt-5.6-sol', 'GPT-5.6 Sol', 'مدل پایدار نسل ۵ با دقت بالا در استدلال و تحلیل داده'],
            ['gpt-5.5', 'GPT-5.5', 'مدل همه‌کاره و چندوجهی نسل ۵'],
        ]);
    }

    private function anthropicModels(): array
    {
        return $this->catalog([
            ['claude-fable-5-1', 'Claude Fable 5.1', 'پیشرفته‌ترین مدل Anthropic برای استدلال‌های بسیار سنگین و ایجنت‌های خودمختار'],
            ['claude-opus-5-5', 'Claude Opus 5.5', 'مدل قدرتمند برای کدنویسی پیشرفته، تحلیل عمیق و وظایف چندمرحله‌ای'],
            ['claude-sonnet-5-5', 'Claude Sonnet 5.5', 'بهترین ترکیب سرعت و هوش برای کار با ابزارها و ورکفلوهای عملیاتی'],
            ['claude-haiku-5-5', 'Claude Haiku 5.5', 'سریع‌ترین و کم‌هزینه‌ترین مدل Claude برای پاسخ‌دهی آنی'],
        ]);
    }

    private function deepSeekModels(): array
    {
        return $this->catalog([
            ['deepseek-flash', 'DeepSeek V4.1 Flash', 'مدل نسل جدید، فوق‌سریع و بسیار هوشمند برای ایجنت‌ها، کدنویسی و فراخوانی ابزار'],
            ['deepseek-chat', 'DeepSeek V4 Pro (Chat)', 'مدل پرچم‌دار DeepSeek برای مکالمه، کدنویسی و تحلیل‌های سنگین'],
            ['deepseek-reasoner', 'DeepSeek R1 / Reasoner', 'مدل استدلال‌گر عمیق برای حل مسائل پیچیده ریاضی و منطقی'],
        ]);
    }

    private function groqModels(): array
    {
        return $this->catalog([
            ['gpt-oss-120b', 'OpenAI GPT-OSS 120B', 'مدل متن‌باز قدرتمند با سرعت اجرای فوق‌العاده روی تراشه‌های LPU'],
            ['llama-3.3-70b-versatile', 'Llama 3.3 70B Versatile', 'مدل قدرتمند متا با سرعت بالا و پشتیبانی عالی از ابزارها'],
            ['qwen-qwq-32b', 'Qwen QwQ 32B', 'مدل استدلال‌گر و چندزبانه قدرتمند (عالی برای زبان فارسی)'],
            ['llama-3.1-8b-instant', 'Llama 3.1 8B Instant', 'پاسخ‌دهی آنی و فوق‌سریع برای پردازش‌های سبک'],
        ]);
    }

    private function openRouterModels(): array
    {
        return $this->catalog([
            ['openai/gpt-6.1-sol', 'OpenAI: GPT-6.1 Sol', 'بهترین تعادل هوش و قیمت از خانواده GPT-6'],
            ['openai/gpt-6-astra', 'OpenAI: GPT-6 Astra', 'مدل پرچم‌دار OpenAI از طریق OpenRouter'],
            ['openai/gpt-6-luna', 'OpenAI: GPT-6 Luna', 'مدل سریع و اقتصادی OpenAI'],
            ['anthropic/claude-sonnet-5.5', 'Anthropic: Claude Sonnet 5.5', 'مدل محبوب و متوازن Claude 5.5'],
            ['anthropic/claude-opus-5.5', 'Anthropic: Claude Opus 5.5', 'مدل قدرتمند کدنویسی و ایجنت Claude 5.5'],
            ['google/gemini-4-argon', 'Google: Gemini 4 Argon', 'مدل پرچم‌دار نسل ۴ گوگل از طریق OpenRouter'],
            ['google/gemini-3.8-flash', 'Google: Gemini 3.8 Flash', 'مدل سریع و بهینه گوگل'],
            ['deepseek/deepseek-v4.1-flash', 'DeepSeek: V4.1 Flash', 'مدل پرطرفدار و اقتصادی DeepSeek V4.1'],
        ]);
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
