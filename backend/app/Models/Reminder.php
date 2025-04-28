<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'event_id',
        'type',
        'remind_at',
        'minutes_before',
        'sent'
    ];
    
    protected $casts = [
        'remind_at' => 'datetime',
        'sent' => 'boolean',
    ];
    
    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}