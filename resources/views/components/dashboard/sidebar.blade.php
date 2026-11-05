@php
    use App\Models\WorkflowRun;
    use App\Subscriptions\SubscriptionManager;

    $user = auth()->user();
    $subscriptions = app(SubscriptionManager::class);
    $saasEnabled = $subscriptions->isSaasEnabled();
    $summary = $user ? $subscriptions->usageSummary($user) : null;

    $planSlug = $summary ? $summary['plan']->slug : 'free';
    $isPro = $saasEnabled && $planSlug === 'pro';
    $isBusiness = $saasEnabled && $planSlug === 'business';

    $maxWorkflows = $summary ? $summary['max_total_workflows'] : (int) config('workflow.max_per_user', 10);
    $totalWorkflows = $summary ? $summary['total_workflows'] : 0;
    $activeWorkflows = $summary ? $summary['active_workflows'] : 0;
    $maxActiveWorkflows = $summary ? $summary['max_active_workflows'] : null;
    $totalCredentials = $user ? $user->credentials()->count() : 0;
    $totalRuns = $user
        ? WorkflowRun::query()->whereHas('workflow', fn ($q) => $q->where('user_id', $user->id))->count()
        : 0;
    $usagePercent = ($maxWorkflows && $maxWorkflows > 0)
        ? min(100, (int) round(($totalWorkflows / $maxWorkflows) * 100))
        : 0;
    $ticketsCount = $user
        ? ($user->isAdmin()
            ? \App\Models\Ticket::query()->whereIn('status', ['open', 'customer_reply'])->count()
            : $user->tickets()->count())
        : 0;
@endphp

<div x-show="sidebarOpen"
     x-cloak
     x-transition.opacity
     @click="sidebarOpen = false"
     class="fixed inset-0 z-30 bg-black/60 backdrop-blur-sm lg:hidden"></div>

<aside x-cloak
       :class="[
           sidebarOpen ? 'translate-x-0' : 'translate-x-full',
           collapsed ? 'lg:w-20' : 'lg:w-68'
       ]"
       class="fixed inset-y-0 right-0 z-40 flex w-68 shrink-0 flex-col border-l border-line-border bg-surface-raised/95 backdrop-blur-xl transition-[transform,width] duration-200 lg:static lg:translate-x-0">

    <div @pointerdown.prevent="startResize($event)"
         @dblclick="toggleSidebar()"
         title="برای باز یا بسته کردن بکشید"
         class="group/resize absolute inset-y-0 -left-1.5 z-50 hidden w-3 cursor-col-resize touch-none lg:block">
        <span :class="resizing ? 'bg-primary' : 'bg-transparent group-hover/resize:bg-primary/50'"
              class="absolute inset-y-0 left-1/2 w-0.5 -translate-x-1/2 transition-colors duration-150"></span>
        <span :class="resizing ? 'bg-primary opacity-100' : 'bg-content-muted/40 opacity-0 group-hover/resize:opacity-100'"
              class="absolute left-1/2 top-1/2 h-9 w-1 -translate-x-1/2 -translate-y-1/2 rounded-full transition-all duration-150"></span>
    </div>

    <div class="flex h-16 shrink-0 items-center justify-between border-b border-line-border/70 px-4"
         :class="collapsed && 'lg:justify-center lg:px-0'">
        <div class="flex items-center gap-2" :class="collapsed && 'lg:hidden'">
            <x-layouts.logo :href="route('app.workflows.index')" logoClasses="h-10 w-auto"/>
            @if ($isBusiness)
                <span class="rounded-lg border border-amber-500/30 bg-amber-500/15 px-2 py-0.5 font-mono text-[10px] font-extrabold text-amber-500">
                    BUSINESS
                </span>
            @elseif ($isPro)
                <span class="rounded-lg border border-primary/30 bg-primary/15 px-2 py-0.5 font-mono text-[10px] font-extrabold text-primary">
                    PRO
                </span>
            @endif
        </div>

        <a href="{{ route('app.workflows.index') }}"
           aria-label="{{ 'لوگو ' . config('app.site_name') }}"
           class="hidden items-center justify-center"
           :class="collapsed && 'lg:flex'">
            <img src="{{ asset('images/logo/favicon.png') }}"
                 alt="{{ config('app.name') }}"
                 class="size-9 rounded-xl object-contain">
        </a>

        <button type="button"
                @click="sidebarOpen = false"
                aria-label="بستن منو"
                class="flex size-8 items-center justify-center rounded-xl text-content-muted transition hover:bg-line-border/60 hover:text-content-main lg:hidden">
            <x-ui.icon name="x" class="size-4.5"/>
        </button>
    </div>

    <nav class="custom-scrollbar flex-1 space-y-1.5 overflow-y-auto overflow-x-hidden p-3">
        <div class="px-2 pb-1 pt-1.5" :class="collapsed && 'lg:hidden'">
            <span class="text-[11px] font-bold tracking-wide text-content-muted/70">اتوماسیون</span>
        </div>

        <x-dashboard.sidebar-link
            icon="hierarchy-2"
            :href="route('app.workflows.index')"
            :active="request()->routeIs('app.workflows.*')"
            :count="$totalWorkflows">
            ورکفلوها
        </x-dashboard.sidebar-link>

        <x-dashboard.sidebar-link
            icon="history"
            :href="route('app.runs.index')"
            :active="request()->routeIs('app.runs.*')"
            :count="$totalRuns">
            تاریخچه اجراها
        </x-dashboard.sidebar-link>

        <div class="my-3 border-t border-line-border/60"></div>

        <div class="px-2 pb-1" :class="collapsed && 'lg:hidden'">
            <span class="text-[11px] font-bold tracking-wide text-content-muted/70">زیرساخت و اتصالات</span>
        </div>

        <x-dashboard.sidebar-link
            icon="key"
            :href="route('app.credentials.index')"
            :active="request()->routeIs('app.credentials.*')"
            :count="$totalCredentials">
            اعتبارنامه‌ها
        </x-dashboard.sidebar-link>

        @if ($saasEnabled && $summary)
            <x-dashboard.sidebar-link
                icon="credit-card"
                :href="route('app.billing.index')"
                :active="request()->routeIs('app.billing.*')"
                :badge="$summary['plan']->name">
                اشتراک و صورتحساب
            </x-dashboard.sidebar-link>
        @endif

        <x-dashboard.sidebar-link
            icon="ticket"
            :href="route('app.tickets.index')"
            :active="request()->routeIs('app.tickets.*')"
            :count="$ticketsCount > 0 ? $ticketsCount : null">
            پشتیبانی و تیکت‌ها
        </x-dashboard.sidebar-link>

        <x-dashboard.sidebar-link
            icon="file-text"
            :href="route('docs.index')"
            :active="request()->routeIs('docs.*')">
            مستندات و آموزش
        </x-dashboard.sidebar-link>
    </nav>

    <div class="shrink-0 space-y-3 border-t border-line-border/70 p-3">
        <x-dashboard.sidebar-plan
            :saas-enabled="$saasEnabled"
            :summary="$summary"
            :is-business="$isBusiness"
            :is-pro="$isPro"
            :total-workflows="$totalWorkflows"
            :max-workflows="$maxWorkflows"
            :active-workflows="$activeWorkflows"
            :max-active-workflows="$maxActiveWorkflows"
            :total-credentials="$totalCredentials"
            :usage-percent="$usagePercent"
        />

        <x-dashboard.sidebar-user :user="$user" :is-business="$isBusiness" />
    </div>
</aside>
