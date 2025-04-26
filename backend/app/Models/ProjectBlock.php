<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'order',
    ];

    /**
     * Get the project that owns the block
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the items for this block
     */
    public function items(): HasMany
    {
        return $this->hasMany(BlockItem::class)->orderBy('order');
    }
}