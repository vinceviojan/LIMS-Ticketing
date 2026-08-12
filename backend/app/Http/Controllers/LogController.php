<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Illuminate\Http\Request;

class LogController extends Controller
{
    /**
     * Return the audit trail, newest first. Logs are intentionally read-only.
     */
    public function index(Request $request)
    {
        $query = Log::with([
            'user:id,first_name,last_name,name,email',
            'ticket:id,ticket_no,issue',
        ])->latest();

        if ($action = $request->query('action')) {
            $query->where('action', strtoupper($action));
        }

        return response()->json($query->get());
    }

    /**
     * Return logs for the currently authenticated user (session-based).
     */
    public function getBySession(Request $request)
    {
        $allowedActions = ['LOGIN', 'LOGOUT'];

        if ($action = $request->query('action')) {
            $action = strtoupper($action);
            if (!in_array($action, $allowedActions)) {
                return response()->json([
                    'message' => 'Invalid action. Allowed values: LOGIN, LOGOUT.',
                ], 422);
            }
        }

        $userId = $request->user()->id;

        $query = Log::with([
            'user:id,first_name,last_name,name,email',
            'ticket:id,ticket_no,issue',
        ])
        ->where('user_id', $userId)
        ->whereIn('action', $action ? [$action] : $allowedActions)
        ->latest();

        return response()->json($query->get());
    }
}
