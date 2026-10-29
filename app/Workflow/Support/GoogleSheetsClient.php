<?php

namespace App\Workflow\Support;

use App\Models\Credential;
use App\Workflow\Exceptions\NodeExecutionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class GoogleSheetsClient
{
    private const BASE_API = 'https://sheets.googleapis.com/v4/spreadsheets';

    public function __construct(private readonly Credential $credential)
    {
        if ($this->credential->type !== 'google') {
            throw NodeExecutionException::because('نوع اعتبارنامه انتخابی نامعتبر است.');
        }
    }

    public function getValidAccessToken(): string
    {
        return (new GoogleTokenProvider($this->credential))->getValidAccessToken();
    }

    public static function spreadsheetId(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            throw NodeExecutionException::because('شناسه یا آدرس اسپردشیت الزامی است.');
        }

        if (preg_match('#/spreadsheets/d/([a-zA-Z0-9-_]+)#', $value, $matches)) {
            return $matches[1];
        }

        if (preg_match('/^[a-zA-Z0-9-_]{20,}$/', $value)) {
            return $value;
        }

        throw NodeExecutionException::because('شناسه اسپردشیت نامعتبر است. آدرس کامل شیت یا شناسه آن را وارد کنید.');
    }

    public function getSpreadsheet(string $spreadsheetId): array
    {
        $response = $this->client()->get(self::BASE_API . "/{$spreadsheetId}", [
            'fields' => 'spreadsheetId,properties.title,sheets.properties',
        ]);

        return $this->payload($response);
    }

    public function getValues(string $spreadsheetId, string $range): array
    {
        $response = $this->client()->get($this->valuesUrl($spreadsheetId, $range));

        return $this->payload($response);
    }

    public function appendValues(string $spreadsheetId, string $range, array $values, string $inputOption = 'RAW'): array
    {
        $url = $this->valuesUrl($spreadsheetId, $range) . ':append?' . http_build_query([
            'valueInputOption' => $inputOption,
            'insertDataOption' => 'INSERT_ROWS',
        ]);

        $response = $this->client()->post($url, ['values' => array_values($values)]);

        return $this->payload($response);
    }

    public function updateValues(string $spreadsheetId, string $range, array $values, string $inputOption = 'RAW'): array
    {
        $url = $this->valuesUrl($spreadsheetId, $range) . '?' . http_build_query([
            'valueInputOption' => $inputOption,
        ]);

        $response = $this->client()->put($url, ['values' => array_values($values)]);

        return $this->payload($response);
    }

    public function clearValues(string $spreadsheetId, string $range): array
    {
        $response = $this->client()->post($this->valuesUrl($spreadsheetId, $range) . ':clear');

        return $this->payload($response);
    }

    private function client(): PendingRequest
    {
        return Http::withToken($this->getValidAccessToken())
            ->acceptJson()
            ->timeout(30);
    }

    private function valuesUrl(string $spreadsheetId, string $range): string
    {
        return self::BASE_API . "/{$spreadsheetId}/values/" . rawurlencode($range);
    }

    private function payload(Response $response): array
    {
        if ($response->failed()) {
            throw NodeExecutionException::because($this->describeError($response));
        }

        return (array) $response->json();
    }

    private function describeError(Response $response): string
    {
        $status = $response->status();
        $message = data_get($response->json(), 'error.message');

        if ($status === 403) {
            return 'دسترسی به Google Sheets مجاز نیست. اعتبارنامه حساب گوگل را دوباره متصل کنید تا دسترسی Sheets اعطا شود.';
        }

        if ($status === 404) {
            return 'اسپردشیت یا شیت مورد نظر پیدا نشد. شناسه اسپردشیت و نام شیت را بررسی کنید.';
        }

        if ($status === 401) {
            return 'نشست گوگل منقضی شده است. اتصال حساب گوگل را دوباره برقرار کنید.';
        }

        if ($message) {
            return 'خطای Google Sheets: ' . $message;
        }

        return 'خطای ارتباط با Google Sheets: ' . strip_tags($response->body());
    }
}
