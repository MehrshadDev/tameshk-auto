<?php

namespace App\Http\Controllers;

use App\Models\Credential;
use App\Workflow\Support\GoogleSheetsClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Throwable;

class GoogleSheetsOptionsController extends Controller
{
    public function spreadsheets(Request $request, Credential $credential): JsonResponse
    {
        Gate::authorize('view', $credential);

        return $this->respond(
            fn () => (new GoogleSheetsClient($credential))->listSpreadsheets()
        );
    }

    public function sheets(Request $request, Credential $credential, string $spreadsheet): JsonResponse
    {
        Gate::authorize('view', $credential);

        return $this->respond(
            fn () => (new GoogleSheetsClient($credential))->listSheets(
                GoogleSheetsClient::spreadsheetId($spreadsheet)
            )
        );
    }

    private function respond(callable $callback): JsonResponse
    {
        try {
            return response()->json(['data' => $callback()]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'data' => [],
                'message' => strip_tags($e->getMessage()),
            ], 422);
        }
    }
}
