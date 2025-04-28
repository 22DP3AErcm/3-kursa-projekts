<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'start_time',
        'end_time',
        'description'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function reminders()
    {
        return $this->hasMany(Reminder::class);
    }
}