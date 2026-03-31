<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'creditor_requested_at',
        'creditor_status',
        'creditor_registration_number',
        'communication_sent_at',
        'contact_notes',
    ];

    protected $casts = [
        'creditor_requested_at' => 'date',
        'communication_sent_at' => 'date',
    ];
}
