<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    public function dashboard(): JsonResponse
    {
        return response()->json([
            'user_roles' => $this->countsFor(User::class, 'role', ['ADMIN', 'STAFF', 'USER']),
            'tickets_by_status' => $this->countsFor(Ticket::class, 'status', ['OPEN', 'ESCALATED', 'PENDING', 'RESOLVED', 'CLOSE', 'CANCEL']),
            'tickets_by_priority' => $this->countsFor(Ticket::class, 'urgency', ['LOW', 'NORMAL', 'HIGH', 'CRITICAL']),
        ]);
    }

    /** @param class-string<\Illuminate\Database\Eloquent\Model> $model */
    private function countsFor(string $model, string $column, array $knownValues): array
    {
        $counts = $model::query()
            ->selectRaw("{$column}, COUNT(*) as aggregate")
            ->groupBy($column)
            ->pluck('aggregate', $column)
            ->mapWithKeys(fn ($count, $value) => [strtoupper((string) $value) => (int) $count])
            ->all();

        return array_replace(array_fill_keys($knownValues, 0), $counts);
    }
}
