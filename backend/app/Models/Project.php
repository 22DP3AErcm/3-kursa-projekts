<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
    ];

    /**
     * Get the user that owns the project (creator)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the users that are members of this project
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'project_user')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    /**
     * Get the blocks for this project
     */
    public function blocks(): HasMany
    {
        return $this->hasMany(ProjectBlock::class)->orderBy('order');
    }

    /**
     * Get invitations for this project
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }
}