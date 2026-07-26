<?php

namespace App\Http\Controllers;

use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Http\Requests\AssignLeadRequest;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Http\Resources\LeadResource;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Lead::class);

        $query = Lead::query()->with('assignedRep');

        if ($request->user()->role === UserRole::Rep) {
            $query->where('assigned_to', $request->user()->id);
        } elseif ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->integer('assigned_to'));
        }

        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('source'), fn ($q) => $q->where('source', $request->string('source')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('company', 'like', "%{$search}%");
                });
            });

        $sortBy = in_array($request->get('sort_by'), ['created_at', 'expected_value']) ? $request->get('sort_by') : 'created_at';
        $sortDir = $request->get('sort_dir') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        $perPage = max(1, min($request->integer('per_page', 15), 100));
        $leads = $query->paginate($perPage);

        return $this->successResponse([
            'leads' => LeadResource::collection($leads),
            'pagination' => [
                'current_page' => $leads->currentPage(),
                'last_page' => $leads->lastPage(),
                'per_page' => $leads->perPage(),
                'total' => $leads->total(),
            ],
        ], 'Leads retrieved successfully.');
    }

    public function store(StoreLeadRequest $request)
    {
        $lead = Lead::create([
            ...$request->validated(),
            'status' => $request->validated('status') ?? LeadStatus::New->value,
        ]);

        return $this->successResponse(new LeadResource($lead), 'Lead created successfully.', 201);
    }

    public function show(Lead $lead)
    {
        $this->authorize('view', $lead);

        $lead->load(['assignedRep', 'activities.user']);

        return $this->successResponse(new LeadResource($lead), 'Lead retrieved successfully.');
    }

    public function update(UpdateLeadRequest $request, Lead $lead)
    {
        $validated = $request->validated();

        $this->ensureCanCloseStatus($lead, $validated);

        $lead->update($validated);

        return $this->successResponse(new LeadResource($lead->fresh('assignedRep')), 'Lead updated successfully.');
    }

    public function assign(AssignLeadRequest $request, Lead $lead)
    {
        $lead->update(['assigned_to' => $request->validated('assigned_to')]);

        return $this->successResponse(new LeadResource($lead->fresh('assignedRep')), 'Lead assigned successfully.');
    }

    /**
     * A lead cannot be marked Won or Lost unless it already has at least one activity.
     */
    private function ensureCanCloseStatus(Lead $lead, array $validated): void
    {
        $closingStatuses = [LeadStatus::Won->value, LeadStatus::Lost->value];

        if (! isset($validated['status']) || ! in_array($validated['status'], $closingStatuses)) {
            return;
        }

        if ($lead->activities()->doesntExist()) {
            throw ValidationException::withMessages([
                'status' => 'A lead must have at least one activity before it can be marked as Won or Lost.',
            ]);
        }
    }
}
