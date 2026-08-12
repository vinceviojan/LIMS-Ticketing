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
        'assigned_staff_id',
        'ticket_no',
        'issue',
        'problem_category_id',
        'date_submitted',
        'status',
        'urgency',
        'description',
        'remarks',
        'rating',
        'feedback',
        'date_action',
        'date_closed',
        'resolution',
        'target_resolution_date',
        'final_remarks',
        'approved_by_id',
    ];

    protected function casts(): array
    {
        return [
            'date_submitted' => 'datetime',
            'date_action' => 'datetime',
            'date_closed' => 'datetime',
            'target_resolution_date' => 'date',
            'rating' => 'integer',
        ];
    }

    /**
     * Get the user that owns the ticket.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Get the staff member assigned to work on this ticket. */
    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
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

    /**
     * Get the attachments for the ticket.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    /**
     * Get the user who approved/closed the ticket.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }
}
