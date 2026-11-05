<x-layouts.dashboard
    :title="'تیکت #' . $ticket->id . ' - ' . config('app.name')"
    :heading="'تیکت #' . $ticket->id"
    :subheading="$ticket->subject">
    <livewire:tickets.ticket-thread :ticket="$ticket" />
</x-layouts.dashboard>
