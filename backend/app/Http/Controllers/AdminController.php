<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Ticket;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Get top requesters ranked by ticket submission frequency.
     */
    public function topRequesters(Request $request)
    {
        $limit = (int) $request->query('limit', 5);
        $formatted = $this->getTopRequestersData($limit);

        return response()->json($formatted);
    }

    /**
     * Get report analytics summary by field frequencies (Status, Category, KPIs, Resolution, Monthly).
     */
    public function reportsSummary(Request $request)
    {
        $period = strtolower($request->query('period', 'month'));

        // Determine date range filters for period comparison
        $now = Carbon::now();
        if ($period === 'week') {
            $startDate = $now->copy()->subDays(7);
            $prevStartDate = $now->copy()->subDays(14);
        } elseif ($period === 'year') {
            $startDate = $now->copy()->startOfYear();
            $prevStartDate = $now->copy()->subYear()->startOfYear();
        } else { // default 'month'
            $startDate = $now->copy()->subDays(30);
            $prevStartDate = $now->copy()->subDays(60);
        }

        // 1. KPI Calculations
        $totalTicketsAllTime = Ticket::count();
        $totalCurrent = Ticket::where('date_submitted', '>=', $startDate)->count();
        $totalPrev = Ticket::whereBetween('date_submitted', [$prevStartDate, $startDate])->count();
        $totalDiff = $totalCurrent - $totalPrev;

        $openTicketsCount = Ticket::where('status', 'OPEN')->count();
        $openInPeriod = Ticket::where('status', 'OPEN')->where('date_submitted', '>=', $startDate)->count();

        $closedTicketsCount = Ticket::where('status', 'CLOSE')->count();
        $closedInPeriod = Ticket::where('status', 'CLOSE')->where('date_submitted', '>=', $startDate)->count();

        // Calculate average resolution time for CLOSED tickets (in hours)
        $closedTickets = Ticket::where('status', 'CLOSE')->get();
        $totalResolutionMinutes = 0;
        $closedCountWithDates = 0;

        foreach ($closedTickets as $ticket) {
            $end = $ticket->updated_at ?? $now;
            $start = $ticket->date_submitted ?? $ticket->created_at;
            if ($start && $end) {
                $totalResolutionMinutes += max(0, $start->diffInMinutes($end));
                $closedCountWithDates++;
            }
        }

        $avgResolutionHours = $closedCountWithDates > 0
            ? ($totalResolutionMinutes / $closedCountWithDates / 60)
            : 0;

        $kpiCards = [
            [
                'label' => 'Total Tickets',
                'value' => (string) $totalTicketsAllTime,
                'icon'  => 'confirmation_number',
                'color' => 'blue',
                'trend' => ($totalDiff >= 0 ? "+{$totalDiff}" : "{$totalDiff}") . ' vs last period',
                'up'    => $totalDiff >= 0,
            ],
            [
                'label' => 'Open Tickets',
                'value' => (string) $openTicketsCount,
                'icon'  => 'inbox',
                'color' => 'orange',
                'trend' => "+{$openInPeriod} in period",
                'up'    => true,
            ],
            [
                'label' => 'Closed Tickets',
                'value' => (string) $closedTicketsCount,
                'icon'  => 'task_alt',
                'color' => 'green',
                'trend' => "+{$closedInPeriod} in period",
                'up'    => true,
            ],
            [
                'label' => 'Avg Resolution Time',
                'value' => $avgResolutionHours > 0 ? number_format($avgResolutionHours, 1) . 'h' : '0h',
                'icon'  => 'timer',
                'color' => 'purple',
                'trend' => 'based on closed tickets',
                'up'    => false,
            ],
        ];

        // 2. Tickets by Status (Database status ENUM: OPEN, ESCALATED, CANCEL, CLOSE)
        $statusConfig = [
            'OPEN'      => ['label' => 'Open',      'color' => 'orange'],
            'ESCALATED' => ['label' => 'Escalated', 'color' => 'yellow'],
            'CANCEL'    => ['label' => 'Canceled',  'color' => 'grey'],
            'CLOSE'     => ['label' => 'Closed',    'color' => 'green'],
        ];

        $statusCountsDb = Ticket::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $ticketsByStatus = [];
        $maxStatusCount = 1;

        foreach ($statusConfig as $statusKey => $cfg) {
            $cnt = (int) ($statusCountsDb[$statusKey] ?? 0);
            if ($cnt > $maxStatusCount) {
                $maxStatusCount = $cnt;
            }
            $ticketsByStatus[] = [
                'label' => $cfg['label'],
                'count' => $cnt,
                'color' => $cfg['color'],
            ];
        }

        foreach ($ticketsByStatus as &$sItem) {
            $sItem['pct'] = round(($sItem['count'] / $maxStatusCount) * 100);
        }

        // 3. Tickets by Category (grouped by main Category Types: Hardware, Software, Network, Account, Other)
        $standardCategoryTypes = ['Hardware', 'Software', 'Network', 'Account', 'Other'];

        $typeCountsDb = DB::table('tickets')
            ->join('problem_categories', 'tickets.problem_category_id', '=', 'problem_categories.id')
            ->select('problem_categories.type', DB::raw('count(*) as count'))
            ->groupBy('problem_categories.type')
            ->pluck('count', 'problem_categories.type')
            ->toArray();

        $ticketsByCategory = [];
        $maxCatCount = 1;

        foreach ($standardCategoryTypes as $catType) {
            $cnt = 0;
            foreach ($typeCountsDb as $dbType => $cVal) {
                if (strcasecmp((string)$dbType, $catType) === 0) {
                    $cnt += (int) $cVal;
                }
            }
            if ($cnt > $maxCatCount) {
                $maxCatCount = $cnt;
            }

            $ticketsByCategory[] = [
                'label' => $catType,
                'count' => $cnt,
            ];
        }

        // Include any remaining types under 'Other' if not in standard list
        $otherExtraCount = 0;
        foreach ($typeCountsDb as $dbType => $cVal) {
            $matched = false;
            foreach ($standardCategoryTypes as $catType) {
                if (strcasecmp((string)$dbType, $catType) === 0) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                $otherExtraCount += (int) $cVal;
            }
        }

        if ($otherExtraCount > 0) {
            foreach ($ticketsByCategory as &$cItem) {
                if ($cItem['label'] === 'Other') {
                    $cItem['count'] += $otherExtraCount;
                    if ($cItem['count'] > $maxCatCount) {
                        $maxCatCount = $cItem['count'];
                    }
                }
            }
        }

        foreach ($ticketsByCategory as &$cItem) {
            $cItem['pct'] = round(($cItem['count'] / $maxCatCount) * 100);
        }

        // 4. Avg. Resolution Time by Urgency (Database urgency ENUM: HIGH, NORMAL, LOW)
        $urgencyConfig = [
            'HIGH'   => ['priority' => 'High',   'color' => '#ef4444'],
            'NORMAL' => ['priority' => 'Normal', 'color' => '#006836'],
            'LOW'    => ['priority' => 'Low',    'color' => '#b5c7b5'],
        ];

        $urgencyStats = [];
        $maxHours = 0.1;

        foreach ($urgencyConfig as $uKey => $cfg) {
            $uTickets = Ticket::where('status', 'CLOSE')->where('urgency', $uKey)->get();
            $totMin = 0;
            $cntWithDates = 0;

            foreach ($uTickets as $t) {
                $end = $t->updated_at ?? $now;
                $start = $t->date_submitted ?? $t->created_at;
                if ($start && $end) {
                    $totMin += max(0, $start->diffInMinutes($end));
                    $cntWithDates++;
                }
            }

            $hrs = $cntWithDates > 0 ? ($totMin / $cntWithDates / 60) : 0;
            if ($hrs > $maxHours) {
                $maxHours = $hrs;
            }

            $urgencyStats[] = [
                'priority' => $cfg['priority'],
                'hours'    => round($hrs, 1),
                'color'    => $cfg['color'],
            ];
        }

        foreach ($urgencyStats as &$uItem) {
            $uItem['pct'] = $maxHours > 0 ? round(($uItem['hours'] / $maxHours) * 100) : 0;
        }

        // 5. Top Requesters
        $topRequesters = $this->getTopRequestersData(5);

        // 6. Monthly Ticket Volume (past 6 months)
        $monthlyData = [];
        $maxMonthCount = 1;

        for ($i = 5; $i >= 0; $i--) {
            $mDate = $now->copy()->subMonths($i);
            $monthLabel = $mDate->format('M');
            $yearNum = $mDate->year;
            $monthNum = $mDate->month;

            $mCount = Ticket::whereYear('date_submitted', $yearNum)
                ->whereMonth('date_submitted', $monthNum)
                ->count();

            if ($mCount > $maxMonthCount) {
                $maxMonthCount = $mCount;
            }

            $monthlyData[] = [
                'label' => $monthLabel,
                'count' => $mCount,
            ];
        }

        foreach ($monthlyData as &$mItem) {
            $mItem['pct'] = round(($mItem['count'] / $maxMonthCount) * 100);
        }

        return response()->json([
            'kpis'                => $kpiCards,
            'tickets_by_status'   => $ticketsByStatus,
            'tickets_by_category' => $ticketsByCategory,
            'resolution_times'    => $urgencyStats,
            'top_requesters'      => $topRequesters,
            'monthly_data'        => $monthlyData,
        ]);
    }

    /**
     * Helper method to fetch top requesters.
     */
    private function getTopRequestersData(int $limit = 5)
    {
        $topUsers = User::withCount('tickets')
            ->orderBy('tickets_count', 'desc')
            ->limit($limit)
            ->get();

        return $topUsers->map(function ($user) {
            $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            if (empty($name)) {
                $name = $user->email ?? ('User #' . $user->id);
            }

            $dept = $user->division;
            if (empty($dept)) {
                $dept = $user->sections ?: 'General User';
            }

            return [
                'id'       => $user->id,
                'name'     => $name,
                'dept'     => $dept,
                'division' => $user->division,
                'sections' => $user->sections,
                'tickets'  => $user->tickets_count,
            ];
        });
    }

    public function dashboard(): JsonResponse
    {
        return response()->json([
            'user_roles' => $this->countsFor(User::class, 'role', ['ADMIN', 'STAFF', 'USER']),
            'tickets_by_status' => $this->countsFor(Ticket::class, 'status', ['OPEN', 'ESCALATED', 'PENDING', 'RESOLVED', 'CLOSE', 'CANCEL']),
            'tickets_by_priority' => $this->countsFor(Ticket::class, 'urgency', ['LOW', 'NORMAL', 'HIGH', 'CRITICAL']),
        ]);
    }

    private function countsFor(string $model, string $column, array $knownValues): array
    {
        $counts = $model::query()
            ->selectRaw("{$column}, COUNT(*) as aggregate")
            ->groupBy($column)
            ->pluck('aggregate', $column)
            ->mapWithKeys(fn($count, $value) => [strtoupper((string) $value) => (int) $count])
            ->all();

        return array_replace(array_fill_keys($knownValues, 0), $counts);
    }

    public function updateAdminInfo(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255|unique:users,email,' . $user->id,
            'position' => 'nullable|string|max:255',
        ]);
        $original = $user->only(array_keys($validated));
        if (isset($validated['first_name']) || isset($validated['last_name'])) {
            $validated['name'] = trim(($validated['first_name'] ?? $user->first_name) . ' ' . ($validated['last_name'] ?? $user->last_name));
        }
        $user->update($validated);

        $changes = collect($user->getChanges())
            ->except(['updated_at', 'name'])
            ->map(fn ($value, $field) => "{$field} changed from '" . ($original[$field] ?? '') . "' to '" . ($value ?? '') . "'")
            ->values()->all();
        if ($changes) {
            Log::create([
                'user_id' => $user->id,
                'action' => 'UPDATE',
                'message' => "Settings profile updated: " . implode(', ', $changes) . '.',
                'address' => $request->ip(),
            ]);
        }
        return response()->json($user);
    }
}
