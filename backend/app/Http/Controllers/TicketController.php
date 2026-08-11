<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    /** Columns actually needed by the list views (card/table). */
    private const LIST_COLUMNS = [
        'id',
        'ticket_no',
        'issue',
        'description',
        'urgency',
        'status',
        'user_id',
        'assigned_staff_id',
        'problem_category_id',
        'upload_intralab',
        'upload_limsportal',
        'date_submitted',
        'created_at',
    ];

    /**
     * Display a listing of the tickets (admin/staff-wide view, scoped for USER role).
     */
    public function index(Request $request)
    {
        $baseQuery = Ticket::query();

        if ($request->user()->role === 'USER') {
            $baseQuery->where('user_id', $request->user()->id);
        }

        return $this->paginateTickets($request, $baseQuery);
    }

    /**
     * Display tickets assigned to the authenticated staff member.
     */
    public function getTickets(Request $request)
    {
        $baseQuery = Ticket::where('assigned_staff_id', $request->user()->id);

        return $this->paginateTickets($request, $baseQuery);
    }

    /**
     * Shared list/filter/paginate logic for index() and getTickets().
     *
     * Perf notes vs. the old implementation:
     * - 1 grouped query for status tab counts instead of 5x clone()->count()
     * - only the columns the list UI needs are selected, on the ticket
     *   and on each eager-loaded relation
     * - real server-side pagination (capped per_page) instead of an
     *   unbounded `all=true` ->get() escape hatch
     * - ordered by created_at + id, which matches the composite index
     *   added in the accompanying migration
     */
    private function paginateTickets(Request $request, $baseQuery)
    {
        $counts = (clone $baseQuery)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $statusCounts = [
            'ALL' => $counts->sum(),
            'OPEN' => $counts->get('OPEN', 0),
            'ESCALATED' => $counts->get('ESCALATED', 0),
            'CLOSE' => $counts->get('CLOSE', 0),
            'CANCEL' => $counts->get('CANCEL', 0),
        ];

        $query = (clone $baseQuery)
            ->select(self::LIST_COLUMNS)
            ->with([
                'user:id,first_name,last_name,name',
                'assignedStaff:id,first_name,last_name,name',
                'problemCategory:id,categories',
            ]);

        if ($request->filled('status') && strtoupper($request->status) !== 'ALL') {
            $query->where('status', strtoupper($request->status));
        }

        if ($request->filled('urgency')) {
            $query->where('urgency', strtoupper($request->urgency));
        } elseif ($request->filled('priority')) {
            $query->where('urgency', strtoupper($request->priority));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_no', 'like', "{$search}%") // prefix match, index-friendly
                    ->orWhere('issue', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('problemCategory', function ($cq) use ($search) {
                        $cq->where('categories', 'like', "%{$search}%");
                    });
            });
        }

        $query->orderByDesc('created_at')->orderByDesc('id');

        $perPage = min((int) $request->get('per_page', 10), 10);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'data' => $paginated->items(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
            'status_counts' => $statusCounts,
        ]);
    }

    /**
     * Store a newly created ticket in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'issue' => ['required', 'string', 'max:255'],
            'problem_category_id' => ['nullable', 'exists:problem_categories,id'],
            'urgency' => ['nullable', 'string', 'in:LOW,NORMAL,HIGH'],
            'assigned_staff_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'STAFF')],
            'description' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
            'upload_intralab' => ['nullable', 'file', 'max:10240'],
            'upload_limsportal' => ['nullable', 'file', 'max:10240'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        if ($request->user()->role !== 'ADMIN') {
            unset($data['urgency'], $data['assigned_staff_id']);
        }
        $data['user_id'] = $request->user()->id ?? 1; // Fallback to 1 if not authenticating middleware correctly
        $data['date_submitted'] = now();
        $data['status'] = 'OPEN';
        if (!isset($data['urgency'])) {
            $data['urgency'] = 'NORMAL';
        }
        $data['upload_intralab'] = $this->storeAttachment($request, 'upload_intralab');
        $data['upload_limsportal'] = $this->storeAttachment($request, 'upload_limsportal');

        // Generate Ticket Number: STP-<YEAR>-0001
        $data['ticket_no'] = DB::transaction(function () {
            $year = date('Y');
            $prefix = "STN-{$year}-";

            // Find the highest ticket number for this year
            $latest = Ticket::where('ticket_no', 'like', "{$prefix}%")
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            if ($latest && preg_match('/-(\d+)$/', $latest->ticket_no, $matches)) {
                $sequence = (int) $matches[1] + 1;
            } else {
                $sequence = 1;
            }

            return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        });

        $ticket = Ticket::create($data);
        $this->writeLog($request, $ticket, 'CREATE', "Ticket {$ticket->ticket_no} created: {$ticket->issue}.");

        return response()->json($ticket->load(['user', 'assignedStaff', 'problemCategory']), 201);
    }

    /**
     * Display the specified ticket.
     */
    public function show(Ticket $ticket)
    {
        $this->ensureTicketAccess(request(), $ticket);
        return response()->json($ticket->load(['user', 'assignedStaff', 'problemCategory', 'logs']));
    }



    public function getOpenTickets(Request $request)
    {
        $tickets = Ticket::with([
            'user',
            'assignedStaff',
            'problemCategory',
        ])
            ->where('status', 'OPEN')
            ->latest()
            ->get();

        return response()->json($tickets);
    }

    /**
     * Update the specified ticket in storage.
     */
    public function update(Request $request, Ticket $ticket)
    {
        $this->ensureTicketAccess($request, $ticket);
        $input = $request->all();
        if (isset($input['status'])) {
            $input['status'] = strtoupper($input['status']);
        }

        $validator = Validator::make($input, [
            'status' => ['sometimes', 'string', 'in:OPEN,ESCALATED,CANCEL,CLOSE'],
            'urgency' => ['sometimes', 'string', 'in:LOW,NORMAL,HIGH'],
            'assigned_staff_id' => ['sometimes', 'nullable', Rule::exists('users', 'id')->where('role', 'STAFF')],
            'problem_category_id' => ['nullable', 'exists:problem_categories,id'],
            'description' => ['sometimes', 'nullable', 'string'],
            'remarks' => ['sometimes', 'nullable', 'string'],
            'issue' => ['sometimes', 'required', 'string', 'max:255'],
            'upload_intralab' => ['sometimes', 'nullable', 'file', 'max:10240'],
            'upload_limsportal' => ['sometimes', 'nullable', 'file', 'max:10240'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $changes = $validator->validated();
        if ($request->user()->role !== 'ADMIN') {
            unset($changes['urgency'], $changes['assigned_staff_id']);
        }
        foreach (['upload_intralab', 'upload_limsportal'] as $attachment) {
            if ($request->hasFile($attachment)) {
                $changes[$attachment] = $this->storeAttachment($request, $attachment);
            } else {
                unset($changes[$attachment]);
            }
        }
        $original = $ticket->only(array_keys($changes));
        $ticket->update($changes);

        if ($changes) {
            $descriptions = [];
            foreach ($changes as $field => $value) {
                if (($original[$field] ?? null) !== $value) {
                    $descriptions[] = $field . ' changed to ' . ($value ?? 'empty');
                }
            }
            if ($descriptions) {
                $this->writeLog($request, $ticket, 'UPDATE', "Ticket {$ticket->ticket_no} updated: " . implode(', ', $descriptions) . '.');
            }
        }

        return response()->json($ticket->load(['user', 'assignedStaff', 'problemCategory']));
    }

    /**
     * Remove the specified ticket from storage.
     */
    public function destroy(Request $request, Ticket $ticket)
    {
        $this->ensureTicketAccess($request, $ticket);
        $ticketNo = $ticket->ticket_no;
        $issue = $ticket->issue;
        // Keep deletion visible in the audit trail by retaining a ticket-less log.
        Log::create([
            'user_id' => $request->user()->id,
            'action' => 'DELETE',
            'message' => "Ticket {$ticketNo} deleted: {$issue}.",
            'address' => $request->ip(),
        ]);
        $ticket->delete();
        return response()->json(null, 204);
    }

    private function ensureTicketAccess(Request $request, Ticket $ticket): void
    {
        if ($request->user()->role === 'USER' && $ticket->user_id !== $request->user()->id) {
            abort(403, 'You may only access your own tickets.');
        }
    }

    private function writeLog(Request $request, Ticket $ticket, string $action, string $message): void
    {
        Log::create([
            'user_id' => $request->user()->id,
            'ticket_id' => $ticket->id,
            'action' => $action,
            'message' => $message,
            'address' => $request->ip(),
        ]);
    }

    private function storeAttachment(Request $request, string $field): ?string
    {
        if (!$request->hasFile($field)) {
            return null;
        }

        return $request->file($field)->store('ticket-attachments', 'public');
    }

    /**
     * Download or view an attachment for the given ticket.
     */
    public function attachment(Request $request, Ticket $ticket, $type)
    {
        $this->ensureTicketAccess($request, $ticket);

        $field = 'upload_' . $type;

        if (!in_array($field, ['upload_intralab', 'upload_limsportal'])) {
            abort(404, 'Invalid attachment type.');
        }

        $path = $ticket->$field;

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404, 'Attachment not found.');
        }

        $fullPath = storage_path('app/public/' . $path);
        return response()->file($fullPath);
    }
}