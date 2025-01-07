<?php

namespace App\Http\Controllers;

use App\Http\Traits\ListingApiTrait;
use App\Http\Traits\ManageFiles;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{

    use ManageFiles, ListingApiTrait;

    public function list(Request $request)
    {
        // Validate request parameters
        $this->ListingValidation();

        // Fetch posts with status 'P'
        $posts = Post::query()
            ->where('status', 'P')->whereNot('user_id', Auth::id()) // Only fetch posts with status 'P'
            ->where(function ($query) {
                $query->where('visibility', 'P') // Public visibility
                    ->orWhere(function ($query) {
                        $query->where('visibility', 'C') // Connected visibility
                            ->where(function ($query) {
                                // Check if the logged-in user is connected via connections
                                $query->whereHas('user.connections', function ($query) {
                                    $query->where('connection_id', Auth::id()) // Ensure logged-in user is connected
                                        ->where('status', 'A'); // Connection is approved
                                })
                                    // Check if the logged-in user has received a connection request
                                    ->orWhereHas('user.connectionRequestsReceived', function ($query) {
                                        $query->where('user_id', Auth::id()) // Ensure logged-in user is connected
                                            ->where('status', 'A'); // Connection is approved
                                    });
                            });
                    });
            })
            ->with(['attachments', 'user:id,first_name,last_name,profile_image,role']); // Eager load related models

        // Apply sorting, filtering, and pagination
        $posts = $this->filterSortPagination($posts);

        // Return the paginated results
        return ok(__('strings.post.user_post_list'), [
            'posts' => $posts['query']->get(), // Fetch the posts after applying filters
            'count' => $posts['count'], // Include the total count
        ]);
    }



    public function create(Request $request)
    {

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'status' => 'in:D,P',
            'visibility' => 'in:P,C',
            'comment_control' => 'in:A,C,N',
            'files.*' => 'nullable|mimes:jpg,jpeg,png,pdf',
        ]);

        $request['user_id'] = Auth::id();

        $post = Post::create($request->only('user_id', 'title', 'content', 'status', 'visibility', 'comment_control'));

        $newAttachment = [];
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $newAttachment[] = [
                    'file_path' => $this->uploadToFirebase($file, 'post_attachments/' . $post->id),
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' =>  $file->getClientOriginalExtension(),
                ];
            }
        }

        $post->attachments()->createMany($newAttachment);

        return ok(__('strings.post.create'));
    }

    public function update(Request $request, $id)
    {
        $post = Post::findOrFail($id);

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'status' => 'sometimes|in:D,P',
            'visibility' => 'in:P,C',
            'comment_control' => 'in:A,C,N',
        ]);


        $post->update($request->only('title', 'content', 'status', 'visibility', 'comment_control'));

        return ok(__('strings.post.update'));
    }

    public function delete($id)
    {
        $post = Post::findOrFail($id);

        foreach ($post->attachments as $attachment) {
            $this->deleteFromFirebase($attachment->file_path);
            $attachment->delete();
        }

        $post->delete();

        return ok(__('strings.post.delete'));
    }


    public function view($id)
    {
        $post =  Post::findOrFail($id);

        return ok(__('strings.post.user_post_list'), [
            'post' => $post->load('attachments', 'user'),
        ]);
    }

    public function postLike($id)
    {

        $user = Auth::user();
        $post = Post::findOrFail($id);
        // Check if the user has already liked the post
        if (!$user->likedPosts()->where('post_id', $id)->exists()) {
            $user->likedPosts()->attach($post->id);
            return ok(__('strings.post.like_post'));
        } else {
            $user->likedPosts()->detach($post->id);
            return ok(__('strings.post.dislike_post'));
        }
    }
}
