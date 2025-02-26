<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\MessageStatusUpdated;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Traits\ListingApiTrait;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Http\Traits\ManageFiles;
use App\Mail\SendHtmlAttachmentMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use App\Events\GroupMessageSent;
use App\Models\GroupMember;

use function PHPUnit\Framework\isNull;

class MessagesController extends Controller
{
    use ListingApiTrait, ManageFiles;


    /**
     * Receive messages
     * @param Request $request
     * @return JsonResponse
     */
    public function receiveMessages(Request $request)
    {
        $this->ListingValidation();

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $id = $request->user_id;

        $messages = Message::where(function ($query) use ($id) {
            $query->where('receiver_id', $id)->where('sender_id', auth()->id());
        })->orWhere(function ($query) use ($id) {
            $query->where('receiver_id', auth()->id())->where('sender_id', $id);
        })
            ->with(['sender', 'receiver', 'attachments']);
        $request['sort_field'] = 'created_at';
        $request['sort_order'] = 'desc';

        $messages = $this->filterSortPagination($messages);
        $count = $messages['count'];

        $totalPages = ceil($count / $request->per_page);

        $groupedMessages = $messages['query']->get()
            ->reverse()
            ->groupBy(function ($message) {
                return $message->created_at->format('d M Y');
            })
            ->map(function ($group) {
                return $group->values();
            });

        return ok(__('strings.message.list'), [
            'messages' => $groupedMessages,
            'count' => $count,
            'total_pages' => $totalPages
        ]);
    }

    /**
     * Send message
     * @param Request $request
     * @return JsonResponse
     */
    public function sendMessage(Request $request)
    {

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'message' => 'required_without_all:files,audio|nullable|string|max:1000',
            'files' => 'required_without_all:message,audio|nullable|array',
            'files.*' => 'nullable|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx',
            'audio' => 'required_without_all:message,files|nullable',
            'parent_id' => 'nullable|exists:messages,id'
        ]);

        $message = Message::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $request->user_id,
            'message' => $request->message,
            'is_sent' => true,
        ]);

        $newAttachment = [];
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $newAttachment[] = [
                    'file_path' => $this->uploadToFirebase($file, 'chat_attachments/' . auth()->id()),
                    'file_name' => $file->getClientOriginalName(),
                ];
            }
        }

        if ($request->hasFile('audio')) {
            $file = $request->file('audio');
            $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $uniqueFileName = $fileName . Str::random(3) . '.mp3';
            $newAttachment[] = [
                'file_path' => $this->uploadToFirebase($file, 'chat_audio/' . auth()->id(), true),
                'file_name' => $uniqueFileName,
                'is_audio_file' => true
            ];
        }

        $message->attachments()->createMany($newAttachment);

        broadcast(new MessageSent($message, 'sent'))->toOthers();
        return ok(__('strings.message.sent'), [
            'message' => $message->load('attachments')
        ]);
    }

    /**
     * Update message
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        $message = Message::findOrFail($id);
        $request['is_edited'] = 1;

        $message->update($request->only('message', 'is_edited'));

        if (!isset($message->group_id))
            broadcast(new MessageSent($message, 'updated'))->toOthers();
        else
            broadcast(new GroupMessageSent($message, 'updated'))->toOthers();

        return ok(__('strings.message.update'), [
            'message' => $message
        ]);
    }

    /**
     * Delete message
     */
    public function delete($id)
    {
        $message = Message::findOrFail($id);

        foreach ($message->attachments as $attachment) {
            $this->deleteFromFirebase($attachment->file_path);
            $attachment->delete();
        }



        if (!isset($message->group_id))
            broadcast(new MessageSent($message, 'deleted'))->toOthers();
        else
            broadcast(new GroupMessageSent($message, 'deleted'))->toOthers();

        $message->delete();


        return ok(__('strings.message.delete'));
    }

    /**
     * Mark messages as delivered
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsDelivered(Request $request)
    {
        $request->validate([
            'message_ids' => 'required|array',
            'message_ids.*' => 'exists:messages,id'
        ]);

        $messages = Message::whereIn('id', $request->message_ids)
            ->where('receiver_id', auth()->id());

        $senderId = $messages->pluck('sender_id')->unique()->first();

        $messages = $messages->update([
            'is_delivered' => true,
            'delivered_at' => now()
        ]);



        broadcast(new MessageStatusUpdated($request->message_ids, 'delivered', $senderId));

        return ok('Messages marked as delivered');
    }

    /**
     * Mark messages as seen
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsSeen(Request $request)
    {
        $request->validate([
            'message_ids' => 'required|array',
            'message_ids.*' => 'exists:messages,id'
        ]);

        $messages = Message::whereIn('id', $request->message_ids)
            ->where('receiver_id', auth()->id());

        $senderId = $messages->pluck('sender_id')->unique()->first();

        $messages = $messages->update([
            'is_seen' => true,
            'seen_at' => now()
        ]);

        broadcast(new MessageStatusUpdated($request->message_ids, 'seen', $senderId));

        return ok('Messages marked as seen');
    }

    /**
     * Mark all undelivered messages as delivered for the authenticated user
     */
    public function markAllAsDelivered()
    {
        // Get undelivered message IDs and senders in one query
        $messages = Message::where('receiver_id', auth()->id())
            ->where('is_delivered', false)
            ->select('id', 'sender_id')
            ->get()
            ->groupBy('sender_id');

        // Bulk update all messages at once
        Message::where('receiver_id', auth()->id())
            ->where('is_delivered', false)
            ->update([
                'is_delivered' => true,
                'delivered_at' => now()
            ]);

        // Broadcast updates per sender
        foreach ($messages as $senderId => $senderMessages) {
            broadcast(new MessageStatusUpdated(
                $senderMessages->pluck('id')->toArray(),
                'delivered',
                $senderId
            ));
        }

        return ok('All messages marked as delivered');
    }

    /**
     * Delete message attachment
     * @param string $messageId
     * @param string $attachmentId
     * @return JsonResponse
     */
    public function deleteMessageAttachment($messageId, $attachmentId)
    {
        $message = Message::findOrFail($messageId);
        $file = $message->attachments()->where('id', $attachmentId)->first();

        if ($file) {
            $this->deleteFromFirebase($file->file_path);
            $file->delete();
        }

        // Delete message if no attachments left
        $count = $message->attachments()->count();
        if ($count == 0) {
            $message->delete();
            broadcast(new MessageSent($message, 'deleted'));
        } else {
            broadcast(new MessageSent($message, 'updated'));
        }

        return response()->json([
            'message' => $message->load('attachments')
        ]);
    }

    public function sendFile(Request $request)
    {
        $user = User::where('email', 'dasa007@gmail.com')->first();

        $origin = $request->header('Origin');
        // Log::log('message', [
        //     'Req' => $origin
        // ]);
        Log::info('sdfsd', [
            'Req' => $origin
        ]);
        // User data
        $userData = [
            'name' => $user->first_name,
            'email' => $user->last_email,
            'phone' => $user->mobile,
            'username' => $user->username,
        ];

        $imageSrc = $this->getBase64Image('chat-icon.png');

        $svgImg = $this->getBase64Image('file-pdf-box.svg');

        // Generate the PDF from the Blade view
        $pdf = Pdf::loadView('emails.user_data', ['user' => $userData, 'imageSrc' => $imageSrc, 'svg' => $svgImg])
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true])
            ->setPaper('a4', 'portrait');
        // return $pdf->download('user_data.pdf');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="my_filename_test.pdf"'
        ]);
    }

    public function sendGroupMessage(Request $request)
    {
        $request->validate([
            'group_id' => 'required|exists:groups,id',
            'message' => 'required_without_all:files,audio|nullable|string|max:1000',
            'files' => 'required_without_all:message,audio|nullable|array',
            'files.*' => 'nullable|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx',
            'audio' => 'required_without_all:message,files|nullable',
        ]);

        // Check if user is member of the group
        $isMember = GroupMember::where('group_id', $request->group_id)
            ->where('user_id', auth()->id())
            ->exists();

        if (!$isMember) {
            return error('You are not a member of this group', 403);
        }

        $message = Message::create([
            'sender_id' => auth()->id(),
            'group_id' => $request->group_id,
            'message' => $request->message,
            'is_sent' => true,
        ]);

        // Handle attachments like before
        $newAttachment = [];
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $newAttachment[] = [
                    'file_path' => $this->uploadToFirebase($file, 'group_chat_attachments/' . $request->group_id),
                    'file_name' => $file->getClientOriginalName(),
                ];
            }
        }

        if ($request->hasFile('audio')) {
            $file = $request->file('audio');
            $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $uniqueFileName = $fileName . Str::random(3) . '.mp3';
            $newAttachment[] = [
                'file_path' => $this->uploadToFirebase($file, 'group_chat_audio/' . $request->group_id, true),
                'file_name' => $uniqueFileName,
                'is_audio_file' => true
            ];
        }

        $message->attachments()->createMany($newAttachment);

        broadcast(new GroupMessageSent($message, 'sent'))->toOthers();

        return ok(__('strings.message.sent'), [
            'message' => $message->load(['attachments', 'sender'])
        ]);
    }

    public function getGroupMessages(Request $request)
    {
        $this->ListingValidation();

        $messages = Message::where('group_id', $request->group_id)
            ->with(['sender', 'attachments']);

        $request['sort_field'] = 'created_at';
        $request['sort_order'] = 'desc';

        $messages = $this->filterSortPagination($messages);
        $count = $messages['count'];



        $totalPages = ceil($count / $request->per_page);


        $groupedMessages = $messages['query']->get()
            ->reverse()
            ->groupBy(function ($message) {
                return $message->created_at->format('d M Y');
            })
            ->map(function ($group) {
                return $group->values();
            });

        return ok(__('strings.message.list'), [
            'messages' => $groupedMessages,
            'count' => $count,
            'total_pages' => $totalPages
        ]);
    }
}
