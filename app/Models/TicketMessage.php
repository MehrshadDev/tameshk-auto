<?php

namespace App\Models;

use App\Tickets\Support\TicketAttachmentStorage;
use App\Tickets\Support\TicketMarkdown;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'is_admin_reply',
        'body',
        'attachments',
    ];

    protected function casts(): array
    {
        return [
            'is_admin_reply' => 'boolean',
            'attachments' => 'array',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachmentUrls(): array
    {
        $paths = is_array($this->attachments) ? $this->attachments : [];

        return array_values(array_filter(array_map(
            fn ($path) => is_string($path) ? TicketAttachmentStorage::url($path) : null,
            $paths
        )));
    }

    public function formattedBody(): string
    {
        return TicketMarkdown::render((string) $this->body);
    }
}
