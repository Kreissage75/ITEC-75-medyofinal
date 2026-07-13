<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'subject', 'customer_name', 'priority', 'status', 'assigned_to', 'description',
    ];

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function priorityPillClass(): string
    {
        return match ($this->priority) {
            'High' => 'high',
            'Medium' => 'medium',
            'Low' => 'low',
            default => 'general',
        };
    }

    public function statusPillClass(): string
    {
        return match ($this->status) {
            'open' => 'open',
            'pending' => 'pending',
            'resolved' => 'resolved',
            default => 'general',
        };
    }

    public function ageMinutes(?Carbon $when = null): int
    {
        return ($when ?: now())->diffInMinutes($this->created_at);
    }

    public function responseTimeMinutes(): ?int
    {
        if ($this->status === 'open') {
            return null;
        }

        return $this->created_at->diffInMinutes($this->updated_at);
    }

    public function resolutionTimeMinutes(): ?int
    {
        if ($this->status !== 'resolved') {
            return null;
        }

        return $this->created_at->diffInMinutes($this->updated_at);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_name', 'name');
    }

    public function slaRule()
    {
        return $this->belongsTo(SlaRule::class, 'priority', 'priority');
    }
}
