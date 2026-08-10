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
}
