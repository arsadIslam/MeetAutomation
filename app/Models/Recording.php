<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recording extends Model
{
    protected $fillable = [
    'meeting_id',
    'file_name',
    'drive_file_id',
    'mime_type',
    'file_size',
    'recorded_at',
    'processed',
];

}
