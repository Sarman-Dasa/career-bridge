<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'messages';

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'group_id',
        'message',
        'is_sent',
        'is_delivered',
        'is_seen',
        'is_edited',
        'delivered_at',
        'seen_at'
    ];

    protected $casts = [
        'is_sent' => 'boolean',
        'is_delivered' => 'boolean',
        'is_seen' => 'boolean',
        'is_edited' => 'boolean',
        'delivered_at' => 'datetime',
        'seen_at' => 'datetime'
    ];

    protected $appends = ['timeAgo'];
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id')->select('id', 'first_name', 'last_name', 'username', 'email', 'profile_image');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id')->select('id', 'first_name', 'last_name', 'username', 'email', 'profile_image');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
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
