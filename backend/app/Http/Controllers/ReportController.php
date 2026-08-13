<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Models\ProblemCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Get analytics and statistics report data.
     */
    public function analytics(Request $request)
    {
        $period = strtolower($request->query('period', 'month'));

        $startDate = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(), // 'month'
        };

        // 1. KPI Cards
        $totalPeriodTickets = Ticket::where('created_at', '>=', $startDate)->count();
        $openTicketsCount = Ticket::where('status', 'OPEN')->count();
        $closedPeriodCount = Ticket::where('status', 'CLOSE')
            ->where('updated_at', '>=', $startDate)
            ->count();

        $avgRating = Ticket::whereNotNull('rating')
            ->where('updated_at', '>=', $startDate)
            ->avg('rating');
        $formattedRating = $avgRating ? number_format($avgRating, 1) . ' / 5.0' : 'N/A';

        $kpiCards = [
            [
                'label' => 'Total Tickets',
                'value' => (string) $totalPeriodTickets,
                'icon' => 'confirmation_number',
                'color' => 'blue',
                'trend' => "Since {$period} start",
                'up' => true,
            ],
            [
                'label' => 'Open Tickets',
                'value' => (string) $openTicketsCount,
                'icon' => 'inbox',
                'color' => 'orange',
                'trend' => 'Requires attention',
                'up' => $openTicketsCount > 0,
            ],
            [
                'label' => 'Resolved in Period',
                'value' => (string) $closedPeriodCount,
                'icon' => 'task_alt',
                'color' => 'green',
                'trend' => 'Closed successfully',
                'up' => true,
            ],
            [
                'label' => 'Avg User Rating',
                'value' => $formattedRating,
                'icon' => 'star',
                'color' => 'purple',
                'trend' => 'Based on feedback',
                'up' => true,
            ],
        ];

        // 2. Tickets by Status
        $statusCounts = Ticket::select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $maxStatus = max($statusCounts->max() ?: 1, 1);

        $ticketsByStatus = [
            [
                'label' => 'Open',
                'count' => $statusCounts->get('OPEN', 0),
                'pct' => round(($statusCounts->get('OPEN', 0) / $maxStatus) * 100),
                'color' => 'orange',
            ],
            [
                'label' => 'Escalated',
                'count' => $statusCounts->get('ESCALATED', 0),
                'pct' => round(($statusCounts->get('ESCALATED', 0) / $maxStatus) * 100),
                'color' => 'yellow',
            ],
            [
                'label' => 'Closed',
                'count' => $statusCounts->get('CLOSE', 0),
                'pct' => round(($statusCounts->get('CLOSE', 0) / $maxStatus) * 100),
                'color' => 'green',
            ],
            [
                'label' => 'Canceled',
                'count' => $statusCounts->get('CANCEL', 0),
                'pct' => round(($statusCounts->get('CANCEL', 0) / $maxStatus) * 100),
                'color' => 'grey',
            ],
        ];

        // 3. Tickets by Category
        $categories = ProblemCategory::withCount('tickets')->get();
        $maxCat = max($categories->max('tickets_count') ?: 1, 1);

        $ticketsByCategory = $categories->map(function ($cat) use ($maxCat) {
            return [
                'label' => $cat->categories,
                'count' => $cat->tickets_count,
                'pct' => round(($cat->tickets_count / $maxCat) * 100),
            ];
        })->sortByDesc('count')->values()->all();

        // 4. Resolution Times by Urgency/Priority
        $priorities = ['HIGH', 'NORMAL', 'LOW'];
        $priorityColors = ['HIGH' => '#e74c3c', 'NORMAL' => '#d98c00', 'LOW' => '#b5c7b5'];
        $resolutionTimes = [];

        foreach ($priorities as $p) {
            $closedP = Ticket::where('status', 'CLOSE')
                ->where('urgency', $p)
                ->get(['created_at', 'updated_at']);

            if ($closedP->count() > 0) {
                $totalHours = $closedP->reduce(function ($carry, $ticket) {
                    return $carry + $ticket->created_at->diffInHours($ticket->updated_at);
                }, 0);
                $avgHours = round($totalHours / $closedP->count(), 1);
            } else {
                $avgHours = 0;
            }

            $resolutionTimes[] = [
                'priority' => ucfirst(strtolower($p)),
                'hours' => $avgHours,
                'pct' => min(round(($avgHours / 48) * 100), 100),
                'color' => $priorityColors[$p],
            ];
        }

        // 5. Top Requesters
        $topRequestersRaw = Ticket::select('user_id', DB::raw('count(*) as ticket_count'))
            ->groupBy('user_id')
            ->orderByDesc('ticket_count')
            ->limit(5)
            ->get();

        $topRequesters = $topRequestersRaw->map(function ($item) {
            $user = User::with(['division', 'section'])->find($item->user_id);
            $dept = 'General';
            if ($user && $user->section && $user->section->name) {
                $dept = $user->section->name;
            } elseif ($user && $user->division && $user->division->name) {
                $dept = $user->division->name;
            }

            return [
                'name' => $user ? ($user->name ?: "{$user->first_name} {$user->last_name}") : 'Unknown',
                'dept' => $dept,
                'tickets' => $item->ticket_count,
            ];
        })->all();

        // 6. Monthly Ticket Volume (Last 6 Months)
        $monthlyData = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = now()->subMonths($i);
            $monthName = $monthDate->format('M');
            $count = Ticket::whereYear('created_at', $monthDate->year)
                ->whereMonth('created_at', $monthDate->month)
                ->count();

            $monthlyData[] = [
                'label' => $monthName,
                'count' => $count,
                'pct' => 0, // calculated below relative to max
            ];
        }

        $maxMonthly = max(array_column($monthlyData, 'count') ?: [1]);
        $maxMonthly = $maxMonthly > 0 ? $maxMonthly : 1;

        foreach ($monthlyData as &$m) {
            $m['pct'] = round(($m['count'] / $maxMonthly) * 100);
        }

        // 7. Most Resolved Tickets by Staff with Ratings
        $topStaffRaw = Ticket::select(
            'assigned_staff_id',
            DB::raw('count(*) as resolved_count'),
            DB::raw('avg(rating) as avg_rating')
        )
            ->whereNotNull('assigned_staff_id')
            ->whereIn('status', ['CLOSE', 'RESOLVED'])
            ->groupBy('assigned_staff_id')
            ->orderByDesc('resolved_count')
            ->limit(10)
            ->get();

        $topStaff = $topStaffRaw->map(function ($item) {
            $user = User::with(['division', 'section'])->find($item->assigned_staff_id);
            $dept = 'General';
            if ($user && $user->section && $user->section->name) {
                $dept = $user->section->name;
            } elseif ($user && $user->division && $user->division->name) {
                $dept = $user->division->name;
            }

            $avgRating = $item->avg_rating ? number_format((float) $item->avg_rating, 1) : null;

            return [
                'id' => $item->assigned_staff_id,
                'name' => $user ? ($user->name ?: "{$user->first_name} {$user->last_name}") : 'Unknown Staff',
                'position' => $user?->position ?? '',
                'dept' => $dept,
                'resolved_count' => (int) $item->resolved_count,
                'avg_rating' => $avgRating ? floatval($avgRating) : null,
                'rating_label' => $avgRating ? "{$avgRating} / 5.0" : 'N/A',
            ];
        })->all();

        return response()->json([
            'period' => $period,
            'kpis' => $kpiCards,
            'tickets_by_status' => $ticketsByStatus,
            'tickets_by_category' => $ticketsByCategory,
            'resolution_times' => $resolutionTimes,
            'top_requesters' => $topRequesters,
            'monthly_data' => $monthlyData,
            'top_staff' => $topStaff,
        ]);
    }
}
