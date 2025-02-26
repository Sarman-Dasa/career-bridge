<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;


    protected $primarykey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'mobile',
        'city',
        'role',
        'password',
        'email_verify_token',
        'is_active',
        'email_verified_at',
        'profile_image',
        'cover_image',
    ];

    // Define the role names
    protected const ROLE_NAMES = [
        'A' => 'Admin',
        'C' => 'Candidate',
        'E' => 'Employer',
    ];

    protected $appends  = ["full_name", "role_name", "connection_count"];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'updated_at',
        'email_verify_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid();
            }
        });
    }

    // connection 
    public function connections()
    {
        return $this->belongsToMany(User::class, 'connections', 'user_id', 'connection_id')
            ->withPivot('status', 'request_send_date', 'request_accepted_date');
    }

    // Define relationship for connection requests received by this user
    public function connectionRequestsReceived()
    {
        return $this->belongsToMany(User::class, 'connections', 'connection_id', 'user_id')
            ->wherePivotIn('status', ['P', 'A']) // Only Pending and Accepted requests
            ->withPivot('status', 'request_send_date', 'request_accepted_date');
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    // Relationship with Comment Likes
    public function likedPosts()
    {
        return $this->belongsToMany(Post::class, 'post_likes')
            ->withTimestamps();
    }

    // Relationship with Comment Likes
    public function likedComments()
    {
        return $this->belongsToMany(Comment::class, 'comment_likes')
            ->withTimestamps();
    }

    public function getfullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    // Accessor for full role name
    public function getRoleNameAttribute(): string
    {
        return self::ROLE_NAMES[$this->role] ?? 'Unknown';
    }

    public function getconnectionCountAttribute()
    {
        // Get the count of sent connections (Pending or Accepted)
        $sentCount = $this->connections()->wherePivot('status', 'A')->count();

        // Get the count of received connections (Pending or Accepted)
        $receivedCount = $this->connectionRequestsReceived()->wherePivot('status',  'A')->count();

        // Combine both counts
        return $sentCount + $receivedCount;
    }

    // public function getProfileImageAttribute($value)
    // {
    //     $baseUrl = env('APP_URL') . '/storage/';
    //     return $value ? $baseUrl . $value : null; // Handle cases where profile_image is null
    // }

    public function messages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receiverMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function unreadMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id')->where('is_seen', false);
    }

    public function lastMessageWith($userId)
    {
        $message = Message::where(function ($query) use ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->where('sender_id', $this->id)
                    ->where('receiver_id', $userId);
            })->orWhere(function ($q) use ($userId) {
                $q->where('sender_id', $userId)
                    ->where('receiver_id', $this->id);
            });
        })
            ->with('attachments')
            ->latest()
            ->first();

        return $message;
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_members', 'user_id', 'group_id');
    }

    // public function withLastMessage()
    // {
    //     return $this->hasOne(Message::class, 'sender_id')
    //         ->orderBy('created_at', 'desc');
    // }

    // public function withLastMessage()
    // {
    //     return $this->hasOne(Message::class, 'sender_id')
    //         ->where(function ($query) {
    //             $query->where('sender_id', $this->id)
    //                 ->orWhere('receiver_id', $this->id);
    //         })
    //         ->orderBy('created_at', 'desc');
    // }
}
