<?php

namespace App\Models;

use App\Tickets\Enums\TicketDepartment;
use App\Tickets\Enums\TicketPriority;
use App\Tickets\Enums\TicketStatus;
use App\Tickets\Support\TicketAttachmentStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'user_id',
        'subject',
        'department',
        'priority',
        'status',
        'last_reply_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'department' => TicketDepartment::class,
            'priority' => TicketPriority::class,
            'status' => TicketStatus::class,
            'last_reply_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket) {
            $ticket->public_id ??= Str::random(16);
            $ticket->last_reply_at ??= now();
        });

        static::deleting(function (Ticket $ticket) {
            TicketAttachmentStorage::deleteForTicket($ticket->id);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(TicketMessage::class)->latestOfMany();
    }

    public function isClosed(): bool
    {
        return $this->status === TicketStatus::Closed;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        return $query->when($term !== '', function (Builder $q) use ($term) {
            $cleanId = ltrim($term, '#');
            $like = '%' . addcslashes(mb_substr($term, 0, 100), '%_\\') . '%';

            $q->where(function (Builder $sub) use ($like, $cleanId) {
                if ($cleanId !== '' && ctype_digit($cleanId)) {
                    $sub->orWhere('id', (int) $cleanId);
                }

                $sub->orWhere('subject', 'like', $like)
                    ->orWhere('public_id', 'like', $like)
                    ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', $like)->orWhere('email', 'like', $like));
            });
        });
    }
}
