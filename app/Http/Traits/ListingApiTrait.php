<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\Log;

trait ListingApiTrait
{
    /**
     * Validate the incoming request for listing parameters.
     *
     * @return bool
     */
    public function ListingValidation()
    {
        $this->validate(request(), [
            'search'        => 'nullable|string',
            'page'          => 'nullable|integer',
            'per_page'      => 'nullable|integer',
            'sort_field'    => 'nullable|string',
            'sort_order'    => 'nullable|required_with:sort_field|string|in:asc,desc',
            'is_active'     => 'nullable',
        ]);
        return true;
    }

    /**
     * Filter, sort, and paginate the query based on request parameters.
     *
     * @param object $query The database query.
     *
     * @return array Contains the modified query and the count of records.
     */
    public function filterSortPagination($query)
    {
        // Filter on is_active if provided in the request
        if (isset(request()->is_active)) {
            $query->where('is_active', request()->is_active);
        }

        // Get count of total records before pagination
        $count = $query->count();

        // Sort records based on provided parameters or default to 'id' in descending order
        if (request()->sort_field) {
            $query->orderBy(request()->sort_field, request()->sort_order);
        } else {
            $query->orderBy('id', 'desc');
        }

        // Paginate the query if 'page' and 'per_page' parameters are present
        if (request()->page && request()->per_page) {
            $page       = request()->page;
            $per_page   = request()->per_page;
            $query      = $query->skip($per_page * ($page - 1))->take($per_page);
        }

        // Return the modified query and total count of records
        return ['query' => $query, 'count' => $count];
    }
}
