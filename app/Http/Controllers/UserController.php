<?php

namespace App\Http\Controllers;

use App\Http\Traits\ListingApiTrait;
use App\Http\Traits\ManageFiles;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    use ListingApiTrait, ManageFiles;

    public function list(Request $request)
    {
        $this->ListingValidation();

        $user = User::query();

        $user = $this->filterSortPagination($user);

        return ok(__('strings.user.list'), [
            'users' => $user['query']->get(),
            'count' => $user['count'],
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user(); // Get the authenticated user
        $request->validate([
            'first_name'        =>  'required|string|min:3|max:50',
            'last_name'         =>  'required|string|min:3|max:50',
            'username'          =>  'required|string|min:3|max:50|unique:users,username,' . $user->id,
            'email'             =>  'required|email|unique:users,email,' . $user->id,
            'mobile'            =>  'nullable',
            'city'              =>  'nullable|string',
            'profile_image'     =>  'nullable|mimes:jpg,jpeg,png|max:10240',
            'cover_image'       =>  'nullable|mimes:jpg,jpeg,png|max:10240',
        ]);

        if ($request->hasFile('profile_image')) {

            if ($user->profile_image) {
                $this->deleteFromFirebase($user->profile_image);
            }

            $image_url = $this->uploadToFirebase($request->file('profile_image'), 'profile/' . auth()->user()->id);

            $user->update([
                'profile_image' => $image_url,
            ]);
        }

        $user->update($request->only('first_name', 'last_name', 'username', 'email', 'mobile', 'city'));

        return ok(__('strings.user.update_profile'), [
            'user' => $user,
        ]);
    }

    public function imageUpload()
    {
        return ok("ok");
    }

    public function userPostList(Request $request)
    {
        // Validate the request (ensure this method handles validation properly)
        $this->ListingValidation();

        // Retrieve the authenticated user's posts as a query
        $postsQuery = Auth::user()->posts()->with('attachments');
        $postsQuery->with('user:id,first_name,last_name,profile_image,role');

        // Apply status filter based on the request
        if ($request->filled('post_status') && in_array($request->post_status, ['D', 'P'])) {
            $postsQuery->where('status', $request->post_status);
        }

        // Apply additional filters, sorting, and pagination
        $posts = $this->filterSortPagination($postsQuery);

        // Return the result with posts and count
        return ok(__('strings.post.user_post_list'), [
            'posts' => $posts['query']->get(),
            'count' => $posts['count'],
        ]);
    }

    /**
     * Get chat user list with last message
     * @param Request $request
     * @return JsonResponse
     */
    public function chatUserList(Request $request)
    {
        $usersQuery = User::where('id', '!=', auth()->id());

        if (!empty($request->search)) {
            $search = $request->search;
            $usersQuery->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            });
        }

        // Clone the filtered query before applying message-related conditions
        $usersWithMessagesQuery = clone $usersQuery;

        // Get users who have messages
        $usersWithMessages = $usersWithMessagesQuery
            ->where(function ($query) {
                $query->whereHas('messages', function ($subQuery) {
                    $subQuery->where('sender_id', auth()->id())
                        ->orWhere('receiver_id', auth()->id());
                })->orWhereHas('receiverMessages', function ($subQuery) {
                    $subQuery->where('sender_id', auth()->id())
                        ->orWhere('receiver_id', auth()->id());
                });
            })
            ->select('id', 'first_name', 'last_name', 'profile_image', 'email')
            ->get()
            ->map(function ($user) {
                $lastMessage = $user->lastMessageWith(auth()->id());

                // Count unread messages for the logged-in user from this user
                $unreadMessagesCount = $user->messages()
                    ->where('receiver_id', auth()->id())  // Messages received by logged-in user
                    ->where('is_seen', false)  // Unread messages
                    ->count();

                $user->last_message = [
                    'message' => $lastMessage->message ?? null,
                    'created_at' => $lastMessage->created_at ?? null,
                    'attachments' => $lastMessage->attachments ?? null,
                    'unread_messages' => $unreadMessagesCount
                ];
                return $user;
            })
            ->sortByDesc(fn($user) => $user->last_message['created_at'])
            ->values();

        $userIds = $usersWithMessages->pluck('id')->toArray();

        // Get users without messages
        $usersWithoutMessages = $usersQuery->whereNotIn('id', $userIds)
            ->select('id', 'first_name', 'last_name', 'profile_image', 'email')
            ->get();

        return ok(__('strings.user.list'), [
            'chat_users' => $usersWithMessages,
            'chat_users_count' => $usersWithMessages->count(),
            'contact_users' => $usersWithoutMessages,
            'contact_users_count' => $usersWithoutMessages->count(),
        ]);
    }
}
