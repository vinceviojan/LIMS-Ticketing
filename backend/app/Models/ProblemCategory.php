<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProblemCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'categories',
    ];

    /**
     * Get the tickets for the problem category.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'problem_category_id');
    }
}
