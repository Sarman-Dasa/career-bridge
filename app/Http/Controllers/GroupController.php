<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMember;
use Illuminate\Http\Request;
use App\Http\Traits\ManageFiles;
use App\Http\Traits\ListingApiTrait;
use Illuminate\Support\Facades\Lang;

class GroupController extends Controller
{
    use ListingApiTrait, ManageFiles;

    /**
     * Get groups list
     */
    public function list(Request $request)
    {
        $this->ListingValidation();

        $groups = Group::with('lastMessage')->withCount('members AS group_members')
            ->whereHas('members', function ($query) {
                $query->where('user_id', auth()->id());
            });

        $groups = $this->filterSortPagination($groups);


        return ok(__('strings.group.list'), [
            'groups' => $groups['query']->get(),
            'count' => $groups['count']
        ]);
    }

    /**
     * Create new group
     */
    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'members' => 'required|array|min:1',
            'members.*' => 'exists:users,id'
        ]);

        $groupData = $request->only(['name', 'description']);
        $groupData['created_by'] = auth()->id();

        if ($request->hasFile('image')) {
            $groupData['image'] = $this->uploadToFirebase(
                $request->file('image'),
                'group_images'
            );
        }

        $group = Group::create($groupData);

        // Add creator as admin
        $group->members()->create([
            'user_id' => auth()->id(),
            'role' => 'admin'
        ]);

        // Add other members
        foreach ($request->members as $memberId) {
            if ($memberId !== auth()->id()) {
                $group->members()->create([
                    'user_id' => $memberId,
                    'role' => 'member'
                ]);
            }
        }

        return ok(__('strings.group.created'), [
            'group' => $group->load(['members.user', 'creator'])
        ]);
    }

    /**
     * Update group
     */
    public function update(Request $request, $id)
    {
        $group = Group::findOrFail($id);

        // Check if user is admin
        $isAdmin = $group->members()
            ->where('user_id', auth()->id())
            ->where('role', 'admin')
            ->exists();

        if (!$isAdmin) {
            return error(__('strings.group.not_admin'), 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        $groupData = $request->only(['name', 'description']);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($group->image) {
                $this->deleteFromFirebase($group->image);
            }

            $groupData['image'] = $this->uploadToFirebase(
                $request->file('image'),
                'group_images'
            );
        }

        $group->update($groupData);

        return ok(__('strings.group.updated'), [
            'group' => $group->load(['members.user', 'creator'])
        ]);
    }

    /**
     * Delete group
     */
    public function delete($id)
    {
        $group = Group::findOrFail($id);

        // Check if user is admin
        $isAdmin = $group->members()
            ->where('user_id', auth()->id())
            ->where('role', 'admin')
            ->exists();

        if (!$isAdmin) {
            return error(__('strings.group.not_admin'), 403);
        }

        // Delete group image if exists
        if ($group->image) {
            $this->deleteFromFirebase($group->image);
        }

        $group->delete();

        return ok(__('strings.group.deleted'));
    }

    /**
     * group view 
     */

    public function view($id)
    {

        $group = Group::findOrFail($id);

        $group = Group::with(['members.user', 'creator', 'lastMessage'])
            ->whereHas('members', function ($query) {
                $query->where('user_id', auth()->id());
            });


        return ok(__('strings.group.view'), [
            'groups' => $group->get(),
        ]);
    }

    /**
     * Add members to group
     */
    public function addMembers(Request $request, $groupId)
    {
        $request->validate([
            'members' => 'required|array|min:1',
            'members.*' => 'exists:users,id'
        ]);

        $group = Group::findOrFail($groupId);

        // Check if user is admin
        $isAdmin = $group->members()
            ->where('user_id', auth()->id())
            ->where('role', 'admin')
            ->exists();

        if (!$isAdmin) {
            return error(__('strings.group.not_admin'), 403);
        }

        // Add new members
        foreach ($request->members as $memberId) {
            $group->members()->firstOrCreate(
                ['user_id' => $memberId],
                ['role' => 'member']
            );
        }

        return ok(__('strings.group.members_added'), [
            'group' => $group->load(['members.user', 'creator'])
        ]);
    }

    /**
     * Remove member from group
     */
    public function removeMember($groupId, $memberId)
    {
        $group = Group::findOrFail($groupId);

        // Check if user is admin or removing themselves
        $isAdmin = $group->members()
            ->where('user_id', auth()->id())
            ->where('role', 'admin')
            ->exists();

        if (!$isAdmin && auth()->id() !== $memberId) {
            return error(__('strings.group.not_admin'), 403);
        }

        // Prevent removing the last admin
        if ($isAdmin && $memberId === auth()->id()) {
            $adminCount = $group->members()->where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return error(__('strings.group.last_admin'), 403);
            }
        }

        $group->members()->where('user_id', $memberId)->delete();

        return ok(__('strings.group.member_removed'), [
            'group' => $group->load(['members.user', 'creator'])
        ]);
    }

    /**
     * Update member role
     */
    public function updateMemberRole(Request $request, $groupId, $memberId)
    {
        $request->validate([
            'role' => 'required|in:admin,member'
        ]);

        $group = Group::findOrFail($groupId);

        // Check if user is admin
        $isAdmin = $group->members()
            ->where('user_id', auth()->id())
            ->where('role', 'admin')
            ->exists();

        if (!$isAdmin) {
            return error(__('strings.group.not_admin'), 403);
        }

        // Update role
        $group->members()
            ->where('user_id', $memberId)
            ->update(['role' => $request->role]);

        return ok(__('strings.group.role_updated'), [
            'group' => $group->load(['members.user', 'creator'])
        ]);
    }
}
