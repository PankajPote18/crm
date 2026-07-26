<?php

namespace App\Http\Controllers;

use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Models\Activity;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function repPerformance(Request $request)
    {
        $user = $request->user();

        $repIds = $user->role === UserRole::Manager
            ? User::query()->where('role', UserRole::Rep->value)->pluck('id')
            : collect([$user->id]);

        $leadTotals = Lead::query()
            ->select('assigned_to')
            ->selectRaw('COUNT(*) as total_leads')
            ->selectRaw('SUM(expected_value) as total_expected_value')
            ->selectRaw("SUM(CASE WHEN status = 'won' THEN expected_value ELSE 0 END) as won_expected_value")
            ->whereIn('assigned_to', $repIds)
            ->groupBy('assigned_to')
            ->get()
            ->keyBy('assigned_to');

        $statusCounts = Lead::query()
            ->select('assigned_to', 'status')
            ->selectRaw('COUNT(*) as count')
            ->whereIn('assigned_to', $repIds)
            ->groupBy('assigned_to', 'status')
            ->get()
            ->groupBy('assigned_to');

        $activityCounts = Activity::query()
            ->select('user_id')
            ->selectRaw('COUNT(*) as count')
            ->whereIn('user_id', $repIds)
            ->groupBy('user_id')
            ->pluck('count', 'user_id');

        $reps = User::query()->whereIn('id', $repIds)->get(['id', 'name']);

        $report = $reps->map(function (User $rep) use ($leadTotals, $statusCounts, $activityCounts) {
            $totals = $leadTotals->get($rep->id);

            $leadsByStatus = collect(LeadStatus::cases())->mapWithKeys(fn (LeadStatus $status) => [$status->value => 0]);
            foreach ($statusCounts->get($rep->id, collect()) as $row) {
                $leadsByStatus[$row->status->value] = (int) $row->count;
            }

            return [
                'rep_id' => $rep->id,
                'rep_name' => $rep->name,
                'total_leads' => (int) ($totals->total_leads ?? 0),
                'leads_by_status' => $leadsByStatus,
                'total_expected_value' => (float) ($totals->total_expected_value ?? 0),
                'won_expected_value' => (float) ($totals->won_expected_value ?? 0),
                'activity_count' => (int) ($activityCounts->get($rep->id) ?? 0),
            ];
        });

        return $this->successResponse($report->values(), 'Rep performance report retrieved successfully.');
    }
}
