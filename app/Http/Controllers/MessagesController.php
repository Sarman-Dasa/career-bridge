<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Traits\ListingApiTrait;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Http\Traits\ManageFiles;

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
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $id = $request->user_id;

        $messages = Message::where(function ($query) use ($id) {
            $query->where('receiver_id', $id)->where('sender_id', auth()->id());
        })->orWhere(function ($query) use ($id) {
            $query->where('receiver_id', auth()->id())->where('sender_id', $id);
        })
            ->with(['sender', 'receiver', 'attachments'])
            ->orderBy('created_at', 'asc')->get();


        return ok(__('strings.message.list'), [
            'messages' => $messages
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

        $message->delete();

        return ok(__('strings.message.delete'));
    }
}
