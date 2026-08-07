<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Log;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ticket_no',
        'issue',
        'problem_category_id',
        'date_submitted',
        'status',
        'urgency',
        'upload_intralab',
        'upload_limsportal',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'date_submitted' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the ticket.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category for the ticket.
     */
    public function problemCategory(): BelongsTo
    {
        return $this->belongsTo(ProblemCategory::class, 'problem_category_id');
    }

    /**
     * Get the logs associated with the ticket.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(Log::class);
    }
}
