<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LostReport extends Model
{
    use HasUuids, HasFactory;

    protected $table = 'lost_reports';

    protected $fillable = [
        'status',
        'owner_name',
        'owner_contact',
        'lost_datetime_from',
        'lost_datetime_to',
        'lost_location_estimated',
        'category',
        'features',
    ];

    protected $casts = [
        'lost_datetime_from' => 'datetime',
        'lost_datetime_to'   => 'datetime',
    ];
}
