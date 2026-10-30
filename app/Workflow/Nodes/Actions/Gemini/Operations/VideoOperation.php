<?php

namespace App\Workflow\Nodes\Actions\Gemini\Operations;

use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Nodes\Actions\Gemini\GeminiOperation;
use App\Workflow\Support\GeminiClient;

final class VideoOperation extends GeminiOperation
{
    public function handle(NodeContext $context, GeminiClient $client): NodeResult
    {
        return match ($this->str($context, 'operation_video', 'analyze_video')) {
            'download_video' => $this->download($context, $client),
            'generate_video' => $this->generate($context, $client),
            default          => $this->analyze($context, $client),
        };
    }

    private function download(NodeContext $context, GeminiClient $client): NodeResult
    {
        $url = $this->ensure($this->str($context, 'video_input'), 'آدرس ویدیو الزامی است.');

        $context->log("دانلود فایل ویدیویی از $url");

        $base64 = $client->downloadUrl($url);

        return NodeResult::of([
            'success'   => true,
            'data_url'  => "data:video/mp4;base64,$base64",
            'base64'    => $base64,
            'video_url' => $url,
        ]);
    }

    private function generate(NodeContext $context, GeminiClient $client): NodeResult
    {
        $prompt = $this->ensure($this->str($context, 'prompt'), 'پرامپت برای تولید ویدیو الزامی است.');
        $model = $this->str($context, 'model_multimodal', 'gemini-2.5-flash');

        $context->log("تولید سناریو و ساختار ویدیویی با Gemini: $prompt");

        $system = 'شما یک کارگردان و تهیه‌کننده متخصص تولید ویدیو و هوش مصنوعی هستید. برای پرامپت ارائه‌شده، یک استوری‌بورد و پرامپت تولید صحنه‌های ویدیویی دقیق با زمان‌بندی، زاویه دوربین، حرکت و نورپردازی تولید کنید.';

        $answer = $client->ask($model, [['text' => "پرامپت ویدیو:\n$prompt"]], ['temperature' => 0.7], $system);

        return NodeResult::of([
            'prompt'     => $prompt,
            'storyboard' => $answer['text'],
            'text'       => $answer['text'],
            'response'   => $answer['text'],
            'status'     => 'ready',
        ]);
    }

    private function analyze(NodeContext $context, GeminiClient $client): NodeResult
    {
        $video = $this->ensure($this->str($context, 'video_input'), 'آدرس ویدیو الزامی است.');
        $model = $this->model($context, 'model_multimodal');
        $prompt = $this->str($context, 'prompt', 'این ویدیو را تحلیل و اتفاقات رخ‌داده در آن را تشریح کن.');

        $context->log("تحلیل ویدیو در Gemini ($model)");

        $part = $client->resolveInputPart($video, $this->str($context, 'video_mime_type', 'video/mp4'));

        return $this->textResult($client->ask($model, [$part, ['text' => $prompt]], ['temperature' => 0.4]), $model);
    }
}
