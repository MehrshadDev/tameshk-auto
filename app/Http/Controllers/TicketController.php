<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Ticket::class);

        return view('pages.tickets.index');
    }

    public function show(Ticket $ticket): View
    {
        Gate::authorize('view', $ticket);

        return view('pages.tickets.show', compact('ticket'));
    }
}
