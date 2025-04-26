<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_block_id',
        'title',
        'description',
        'order',
        'due_date',
    ];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    /**
     * Get the block that owns the item
     */
    public function block(): BelongsTo
    {
        return $this->belongsTo(ProjectBlock::class, 'project_block_id');
    }
}