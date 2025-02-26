<?php

namespace App\Http\Controllers;

use App\Http\Traits\ListingApiTrait;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConnectionsController extends Controller
{

    use ListingApiTrait;
    public function list(Request $request)
    {
        $user = Auth::user();

        $this->ListingValidation();

        $sentConnectionsQuery = $user->connections()
            ->wherePivotIn('status', ['P', 'A'])->select('id', 'first_name', 'last_name', 'role', 'profile_image', 'mobile');

        $sentConnections = $this->filterSortPagination($sentConnectionsQuery);

        // Fetch the user's received connections (Pending or Accepted)
        $receivedConnectionsQuery = $user->connectionRequestsReceived()
            ->wherePivotIn('status', ['P', 'A'])->select('id', 'first_name', 'last_name', 'role', 'profile_image', 'mobile');

        $receivedConnections = $this->filterSortPagination($receivedConnectionsQuery);

        return ok("connections list", [
            'sentConnections'     => $sentConnections['query']->get(),
            'receivedConnections' => $receivedConnections['query']->get(),
            'count'               => [
                'sent'     => $sentConnections['count'],
                'received' => $receivedConnections['count'],
            ],
        ]);
    }
    public function create(Request $request)
    {

        $request->validate([
            'connection_id' => 'required|string|min:3|max:50|exists:users,id',
        ]);

        // Check if the user is trying to send a request to themselves
        if ($request->connection_id == Auth::id()) {
            return response()->json(['error' => 'You cannot send a connection request to yourself.'], 400);
        }

        $user = Auth::user();

        // Check if a request already exists with this connection_id and status 'P' (Pending)
        if ($user->connections()->where('connection_id', $request->connection_id)->wherePivot('status', 'P')->exists()) {
            return response()->json(['error' => 'Connection request already sent.'], 400);
        }

        // Check if a connection already exists in either direction
        $existingConnection = $user->connections()
            ->where(function ($query) use ($request) {
                $query->where('connection_id', $request->connection_id);
            })
            ->orWhere(function ($query) use ($user, $request) {
                $query->where('connection_id', $user->id)
                    ->where('user_id', $request->connection_id);
            })
            ->exists();

        if ($existingConnection) {
            return response()->json(['error' => 'A connection request already exists between these users.'], 400);
        }

        // Send the connection request
        $user->connections()->attach($request->connection_id, [
            'status'            => 'P', // Pending
            'request_send_date' => now(),
        ]);

        return ok(__('strings.connection.create'));
    }

    //  Accept or reject a connection request
    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:A,R', // Accept (A) or Reject (R)
        ]);

        $user = Auth::user();

        $connection = $user->connectionRequestsReceived()->where('user_id', $id)->first();

        // Check if the sender is trying to accept their own request
        if ($connection && $connection->pivot->user_id == $user->id) {
            return error(__('strings.connection.self_connection_error'));
        }

        if (!$connection) {
            return error(__('strings.connection.not_found'));
        }

        //dd($connection);
        $connection->pivot->status = $request->status;
        if ($request->status == 'A') {
            $connection->pivot->request_accepted_date = now();
        }
        $connection->pivot->save();

        return ok(__('strings.connection.update'));
    }

    // Delete a connection
    public function delete($id)
    {
        $user = Auth::user();

        // Check if the connection exists in the pivot table for the authenticated user
        $connection = $user->connections()->wherePivot('connection_id', $id)->first();

        if (!$connection) {
            return error(__('strings.connection.not_found'));
        }

        // If the connection exists, proceed to detach it
        $user->connections()->detach($id);

        return ok(__('strings.connection.delete'));
    }

    // View pending connection requests for the authenticated user
    public function pendingRequests()
    {
        $user            = Auth::user();
        $pendingRequests = $user->connectionRequestsReceived()->get();

        // Check if there are no pending requests
        if ($pendingRequests->isEmpty()) {
            return ok(__('strings.connection.no_pending_requests'));
        }

        return ok(__('strings.connection.pending_requests'), [
            'requests' => $pendingRequests,
            'count'    => $pendingRequests->count(),
        ]);
    }

    public function suggestConnections()
    {
        $user = Auth::user();

        // Get all connected user IDs (both sent and received, with Pending or Accepted status)
        $connectedUserIds = $user->connections()
            ->wherePivotIn('status', ['P', 'A'])
            ->pluck('connection_id')
            ->merge(
                $user->connectionRequestsReceived()
                    ->wherePivotIn('status', ['P', 'A'])
                    ->pluck('user_id')
            )->unique();

        // Add the current user ID to exclude them from suggestions
        $connectedUserIds->push($user->id);

        // Query suggested connections who are not in the connected user list
        $suggestedConnections = User::whereNotIn('id', $connectedUserIds)
            ->withCount(['connections as mutual_connections_count' => function ($query) use ($connectedUserIds) {
                // Count mutual connections by checking users in connectedUserIds
                $query->whereIn('connection_id', $connectedUserIds);
            }]);

        $suggestedConnections = $this->filterSortPagination($suggestedConnections);

        return ok(__('strings.connection.suggested_connections'), [
            'suggestedConnections'  => $suggestedConnections['query']->get(),
            'count'                 => $suggestedConnections['count']
        ]);
    }


    // public function ConnectedUserList(Request $request)
    // {
    //     $userId = Auth::id();

    //     $sortBy    = $request->input('sort_by', 'first_name'); // Default sorting column
    //     $sortOrder = $request->input('sort_order', 'ASC');     // Default sorting order

    //     // Raw SQL query
    //     $query = "WITH SentConnections AS (
    //         SELECT
    //             u.id,
    //             u.first_name,
    //             u.last_name,
    //             CONCAT(u.first_name, ' ', u.last_name) AS full_name, 
    //             u.profile_image,
    //             u.mobile
    //         FROM users u
    //         JOIN connections c ON u.id = c.connection_id
    //         WHERE c.user_id = ?
    //         AND c.status = 'A'
    //         AND (u.first_name LIKE '%' || ? || '%' OR u.last_name LIKE '%' || ? || '%' OR CONCAT(u.first_name, ' ', u.last_name) LIKE '%' || ? || '%')
    //     ),

    //     ReceivedConnections AS (
    //         SELECT
    //             u.id,
    //             u.first_name,
    //             u.last_name,
    //             CONCAT(u.first_name, ' ', u.last_name) AS full_name, 
    //             u.profile_image,
    //             u.mobile
    //         FROM users u
    //         JOIN connections c ON u.id = c.user_id
    //         WHERE c.connection_id = ?
    //         AND c.status = 'A'
    //         AND (u.first_name LIKE '%' || ? || '%' OR u.last_name LIKE '%' || ? || '%' OR CONCAT(u.first_name, ' ', u.last_name) LIKE '%' || ? || '%')
    //     )

    //     SELECT * FROM SentConnections
    //     UNION
    //     SELECT * FROM ReceivedConnections
    //     ORDER BY $sortBy $sortOrder
    //     LIMIT ? OFFSET ? ";

    //     // Execute the query
    //     $connections = DB::select($query, [$userId, $request->search, $request->search, $request->search, $userId,  $request->search, $request->search, $request->search, $request->per_page, $request->per_page * ($request->page - 1)]);

    //     return ok(__('strings.user.list'), [
    //         'connections' => $connections,
    //         'count'       => count($connections),
    //     ]);
    // }

    public function ConnectedUserList(Request $request)
    {
        $userId = Auth::id();

        $searchTerm = trim($request->input('search', ''));
        $sortBy     = $request->input('sort_by', 'first_name');
        $sortOrder  = strtoupper($request->input('sort_order', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        $perPage    = (int) $request->input('per_page', 10);
        $offset     = $perPage * ((int) $request->input('page', 1) - 1);

        $searchCondition = $searchTerm ? "AND (LOWER(u.first_name) LIKE LOWER(?) OR LOWER(u.last_name) LIKE LOWER(?) OR LOWER(CONCAT(u.first_name, ' ', u.last_name)) LIKE LOWER(?))" : "";

        $query = "
        WITH SentConnections AS (
            SELECT u.id, u.first_name, u.last_name, CONCAT(u.first_name, ' ', u.last_name) AS full_name, 
                   u.profile_image, u.mobile
            FROM users u
            JOIN connections c ON u.id = c.connection_id
            WHERE c.user_id = ? AND c.status = 'A' $searchCondition
        ),
        ReceivedConnections AS (
            SELECT u.id, u.first_name, u.last_name, CONCAT(u.first_name, ' ', u.last_name) AS full_name, 
                   u.profile_image, u.mobile
            FROM users u
            JOIN connections c ON u.id = c.user_id
            WHERE c.connection_id = ? AND c.status = 'A' $searchCondition
        )
     
        SELECT * FROM SentConnections
        UNION
        SELECT * FROM ReceivedConnections
        ORDER BY $sortBy $sortOrder
        LIMIT ? OFFSET ? ";

        $bindings = [$userId];

        if ($searchTerm) {
            array_push($bindings, "%$searchTerm%", "%$searchTerm%", "%$searchTerm%");
            array_push($bindings, $userId, "%$searchTerm%", "%$searchTerm%", "%$searchTerm%");
        } else {
            array_push($bindings, $userId);
        }

        array_push($bindings, $perPage, $offset);

        $connections = DB::select($query, $bindings);

        return ok(__('strings.user.list'), [
            'connections' => $connections,
            'count'       => count($connections),
        ]);
    }
}
