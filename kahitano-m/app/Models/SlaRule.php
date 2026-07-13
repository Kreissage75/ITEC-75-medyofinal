<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlaRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'priority', 'name', 'description',
        'response_hours', 'response_minutes',
        'resolution_hours', 'resolution_minutes',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function pillClass(): string
    {
        return match ($this->priority) {
            'High' => 'high',
            'Medium' => 'medium',
            'Low' => 'low',
            default => 'general',
        };
    }

    public function formatted(string $prefix): string
    {
        $hours = $this->{$prefix . '_hours'};
        $minutes = $this->{$prefix . '_minutes'};

        return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
    }

    public function responseTargetMinutes(): int
    {
        return ($this->response_hours * 60) + $this->response_minutes;
    }

    public function resolutionTargetMinutes(): int
    {
        return ($this->resolution_hours * 60) + $this->resolution_minutes;
    }
}
