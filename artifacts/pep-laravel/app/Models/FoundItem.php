<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoundItem extends Model
{
    use HasUuids, HasFactory;

    protected $table = 'found_items';

    protected $fillable = [
        'management_no',
        'status',
        'category',
        'sub_category',
        'features',
        'found_datetime',
        'found_location',
        'image_url',
        'storage_location',
        'finder_name',
        'finder_contact',
        'rights_waived',
        'returned_at',
        'returned_to',
        'returned_by',
        'identity_verified',
        'receipt_signed',
    ];

    protected $casts = [
        'rights_waived'     => 'boolean',
        'identity_verified' => 'boolean',
        'receipt_signed'    => 'boolean',
        'found_datetime'    => 'datetime',
        'returned_at'       => 'datetime',
    ];
}
