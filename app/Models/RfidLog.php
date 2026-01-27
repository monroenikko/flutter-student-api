<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RfidLog extends Model
{
    use HasFactory;

    protected $table="rfid_logs";

    protected $fillable = [
        'rfid_information_id',
        'msg',
        'status',
        'sent_status'
    ];

}