<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $type;

    public function __construct(public Message $message, string $type)
    {
        $this->type = $type;
    }

    public function broadcastOn(): array
    {
        return [
            // new PresenceChannel("group.{$this->message->group_id}")
            new PrivateChannel("group.messages") // Single channel for all group messages
        ];
    }

    public function broadcastWith(): array
    {
        $messageArray = $this->message->toArray();
        $messageArray['sender'] = $this->message->sender;
        $messageArray['attachments'] = $this->message->attachments;

        return [
            'message' => $messageArray,
            'type' => $this->type,
        ];
    }

    public function broadcastAs(): string
    {
        return 'GroupMessageEvent';
    }
}
