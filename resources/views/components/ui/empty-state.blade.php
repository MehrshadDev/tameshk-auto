@props(['icon' => 'inbox', 'title', 'description' => null])

<div {{ $attributes->class(['flex flex-col items-center justify-center px-6 py-16 text-center']) }}>
    <div class="mb-5 flex size-16 items-center justify-center rounded-2xl bg-primary/10 text-primary">
        <x-ui.icon :name="$icon" class="size-8"/>
    </div>

    <h3 class="text-base font-bold text-content-main">{{ $title }}</h3>

    @if ($description)
        <p class="mt-1.5 max-w-sm text-sm leading-6 text-content-muted">{{ $description }}</p>
    @endif

    @if ($slot->isNotEmpty())
        <div class="mt-6">{{ $slot }}</div>
    @endif
</div>
