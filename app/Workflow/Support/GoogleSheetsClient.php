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
    private const DRIVE_API = 'https://www.googleapis.com/drive/v3/files';

    public function __construct(private readonly Credential $credential)
    {
        if ($this->credential->type !== 'google') {
            throw NodeExecutionException::because('نوع اعتبارنامه انتخابی برای Google Sheets معتبر نیست.');
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

        if (preg_match('/^[a-zA-Z0-9-_]{15,}$/', $value)) {
            return $value;
        }

        throw NodeExecutionException::because('شناسه اسپردشیت نامعتبر است. یک سند از لیست انتخاب کنید یا لینک معتبر وارد نمایید.');
    }

    public function listSpreadsheets(int $limit = 100): array
    {
        $response = $this->client()->get(self::DRIVE_API, [
            'q'                         => "mimeType='application/vnd.google-apps.spreadsheet' and trashed=false",
            'fields'                    => 'files(id,name,modifiedTime)',
            'pageSize'                  => min(max($limit, 1), 200),
            'orderBy'                   => 'modifiedTime desc',
            'supportsAllDrives'         => 'true',
            'includeItemsFromAllDrives' => 'true',
        ]);

        $payload = $this->payload($response);
        $files = array_values((array) ($payload['files'] ?? []));
        $result = [];

        foreach ($files as $file) {
            $id = (string) ($file['id'] ?? '');

            if ($id === '') {
                continue;
            }

            $result[] = [
                'value' => $id,
                'label' => (string) ($file['name'] ?? $id),
            ];
        }

        return $result;
    }

    public function listSheets(string $spreadsheetId): array
    {
        $payload = $this->getSpreadsheet($spreadsheetId);
        $sheets = array_values((array) ($payload['sheets'] ?? []));
        $result = [];

        foreach ($sheets as $sheet) {
            $title = (string) data_get($sheet, 'properties.title', '');

            if ($title === '') {
                continue;
            }

            $result[] = [
                'value' => $title,
                'label' => $title,
            ];
        }

        return $result;
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

    public function appendValues(string $spreadsheetId, string $range, array $values, string $inputOption = 'USER_ENTERED'): array
    {
        $url = $this->valuesUrl($spreadsheetId, $range) . ':append?' . http_build_query([
                'valueInputOption' => $inputOption,
                'insertDataOption' => 'INSERT_ROWS',
            ]);

        $response = $this->client()->post($url, ['values' => array_values($values)]);

        return $this->payload($response);
    }

    public function updateValues(string $spreadsheetId, string $range, array $values, string $inputOption = 'USER_ENTERED'): array
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
        $message = (string) data_get($response->json(), 'error.message', '');

        if ($status === 403) {
            if (str_contains($message, 'Drive API') || str_contains($message, 'drive.googleapis.com')) {
                return 'سرویس Google Drive API در پروژه Google Cloud شما فعال نیست. در کنسول گوگل هر دو سرویس Google Sheets API و Google Drive API را فعال کنید.';
            }

            if (str_contains($message, 'Sheets API') || str_contains($message, 'sheets.googleapis.com')) {
                return 'سرویس Google Sheets API در پروژه Google Cloud شما فعال نیست.';
            }

            return 'دسترسی به Google Sheets یا Drive مجاز نیست. در بخش اعتبارنامه روی «اتصال مجدد» به گوگل کلیک کنید.';
        }

        if ($status === 404) {
            return 'اسپردشیت یا شیت مورد نظر پیدا نشد. سند و شیت انتخاب‌شده را بررسی کنید.';
        }

        if ($status === 401) {
            return 'نشست گوگل منقضی شده است. اتصال حساب گوگل را دوباره برقرار کنید.';
        }

        if ($message !== '') {
            return 'خطای Google Sheets: ' . $message;
        }

        return 'خطای ارتباط با Google Sheets: ' . strip_tags($response->body());
    }
}
