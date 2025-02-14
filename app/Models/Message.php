<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'messages';

    protected $fillable = ['sender_id', 'receiver_id', 'message', 'is_read', 'is_edited', 'is_seen', 'is_sent', 'is_delivered', 'sent_at', 'seen_at', 'delivered_at'];

    protected $appends = ['timeAgo'];
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id')->select('id', 'first_name', 'last_name', 'username', 'email', 'profile_image');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id')->select('id', 'first_name', 'last_name', 'username', 'email', 'profile_image');
    }

    public function attachments()
    {
        return $this->hasMany(MessageAttachment::class, 'message_id');
    }

    public function gettimeAgoAttribute()
    {
        return Carbon::parse($this->created_at)->format('h:i A');
    }
}
