<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $type;  // 'sent' or 'updated'
    public $isSheduleMsg;
    /**
     * Create a new event instance.
     */
    public function __construct(public Message $message, string $type, $isSheduleMsg = false)
    {
        $this->type = $type;
        $this->isSheduleMsg = $isSheduleMsg;
        Log::info('MessageSent event constructor');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel("chat.{$this->message->receiver_id}")
        ];

        if ($this->isSheduleMsg) {
            $channels[] = new PrivateChannel("chat.{$this->message->sender_id}");
        }

        Log::info('MessageSent event broadcastOn', ['channels' => $channels]);
        return $channels;
    }


    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        Log::info('MessageSent event broadcastWith');
        $messageArray = $this->message->toArray();
        $messageArray['attachments'] = $this->message->attachments()->get()->toArray();
        Log::info('MessageSent event broadcastWith', ['message' => $messageArray]);
        return [
            'message' => $messageArray,
            'type' => $this->type,
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        Log::info('MessageSent event broadcastAs');
        // If you customize the broadcast name using the broadcastAs method, you should make sure to register your listener with a leading . character
        return 'MessageEvent';
    }
}
