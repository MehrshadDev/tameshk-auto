<x-layouts.editor :title="$workflow->name . ' | ' . config('app.name')">
    <div id="canvas-root"
         data-workflow-id="{{ $workflow->id }}"
         data-back-url="{{ route('app.workflows.index') }}"
         dir="ltr"
         class="h-full w-full"></div>
</x-layouts.editor>
