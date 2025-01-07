<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class MessageAttachment extends BaseModel
{
    use HasFactory;

    protected $primarykey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'message_attachments';

    public $fillable = ['message_id', 'file_path', 'file_name', 'is_audio_file'];
    protected $appends = ['timeAgo'];

    protected $casts = [
        'is_audio_file' => 'boolean',
    ];

    public function gettimeAgoAttribute()
    {
        return Carbon::parse($this->created_at)->diffForHumans();
    }

    // public function getFilePathAttribute($value)
    // {
    //     $baseUrl = env('APP_URL') . '/storage/';
    //     return $value ? $baseUrl . $value : null;
    // }
}
