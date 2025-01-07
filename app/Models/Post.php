<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Post extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $primarykey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'title',
        'content',
        'status',
        'visibility',
        'comment_control'
    ];

    protected $appends = ['likeCount', 'commentCount', 'hasLike', 'hasCommentAllowed'];


    public function attachments()
    {
        return $this->hasMany(PostAttachment::class, 'post_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship with Users who liked the comment
    public function likes()
    {
        return $this->belongsToMany(User::class, 'post_likes')
            ->withTimestamps();
    }

    public function getlikeCountAttribute()
    {
        return $this->likes()->count();
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_id', 'id');
    }

    public function getCommentCountAttribute()
    {
        return $this->comments()->count();
    }

    public function getHasLikeAttribute()
    {
        $userId = Auth::id();
        return $this->likes()->where('user_id', $userId)->exists();
    }

    public function getHasCommentAllowedAttribute()
    {
        // If public visibility, allow comments
        if ($this->comment_control == 'A') {
            return true;
        }

        if ($this->user_id == Auth::id()) {
            return true;
        }
        // If connected visibility, check connections
        if ($this->comment_control == 'C') {
            $user = Auth::user(); // Get the logged-in user

            // Check if the logged-in user is connected to the owner of this entity
            $isConnected = $this->user->connections()
                ->where('connection_id', $user->id)
                ->where('status', 'A') // Connection is approved
                ->exists();

            // Check if the logged-in user has received a connection request from the owner
            $hasReceivedRequest = $this->user->connectionRequestsReceived()
                ->where('user_id', $user->id)
                ->where('status', 'A') // Connection is approved
                ->exists();

            return $isConnected || $hasReceivedRequest;
        }

        return false; // Comments not allowed by default
    }
}
