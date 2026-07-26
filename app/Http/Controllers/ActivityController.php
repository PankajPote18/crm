<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActivityRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Lead;

class ActivityController extends Controller
{
    public function store(StoreActivityRequest $request, Lead $lead)
    {
        $activity = $lead->activities()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
            'occurred_at' => $request->validated('occurred_at') ?? now(),
        ]);

        return $this->successResponse(new ActivityResource($activity->load('user')), 'Activity added successfully.', 201);
    }
}
