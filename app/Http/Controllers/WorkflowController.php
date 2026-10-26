<?php

namespace App\Http\Controllers;

use App\Models\Workflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WorkflowController extends Controller
{
    public function index(): View
    {
        return view('pages.workflows.index');
    }

    public function show(Workflow $workflow): View
    {
        Gate::authorize('view', $workflow);

        return view('pages.workflows.editor', compact('workflow'));
    }

    public function rename(Request $request, Workflow $workflow): JsonResponse
    {
        Gate::authorize('update', $workflow);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $name = trim(strip_tags($data['name']));

        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'نام ورکفلو نامعتبر است.']);
        }

        $workflow->update(['name' => $name]);

        return response()->json(['workflow' => $workflow->editorSummary()]);
    }
}
