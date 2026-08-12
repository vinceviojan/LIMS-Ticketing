<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Log;
use App\Models\ProblemCategory;
use App\Models\User;
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
        'remarks',
        'urgency',
        'status',
        'user_id',
        'assigned_staff_id',
        'problem_category_id',
        'date_submitted',
        'created_at',
        'rating',
        'feedback',
        'resolution',
        'final_remarks',
        'target_resolution_date',
        'date_action',
        'date_closed',
        'approved_by_id',
    ];

    /**
     * Display a listing of the tickets (admin/staff-wide view, scoped for USER role).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $baseQuery = Ticket::query();

        if (strtoupper($user->role) === 'USER') {
            $baseQuery->where('user_id', $user->id);
        } elseif (strtoupper($user->role) === 'STAFF') {
            $baseQuery->where('assigned_staff_id', $user->id);
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
                'user:id,first_name,last_name,name,email',
                'assignedStaff:id,first_name,last_name,name',
                'approvedBy:id,first_name,last_name,name',
                'problemCategory:id,categories',
                'attachments',
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
                $q->where('ticket_no', 'like', "{$search}%")
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
        $user = $request->user();

        // For non-admin users, enforce creation guards
        if ($user->role !== 'ADMIN') {
            // Guard 2: Check if the user has a closed or resolved ticket missing rating or feedback
            $unratedTicket = Ticket::where('user_id', $user->id)
                ->whereIn('status', ['CLOSE'])
                ->where(function ($q) {
                    $q->whereNull('rating')->orWhereNull('feedback')->orWhere('feedback', '');
                })
                ->first();

            if ($unratedTicket) {
                return response()->json([
                    'message' => 'You cannot create a new ticket until you provide a star rating and feedback for your completed ticket(s).',
                    'unrated_ticket' => [
                        'id' => $unratedTicket->id,
                        'ticket_no' => $unratedTicket->ticket_no,
                        'issue' => $unratedTicket->issue,
                    ],
                ], 422);
            }
        }

        $validator = Validator::make($request->all(), [
            'issue' => ['required', 'string', 'max:255'],
            'problem_category_id' => ['nullable', 'exists:problem_categories,id'],
            'urgency' => ['nullable', 'string', 'in:LOW,NORMAL,HIGH'],
            'assigned_staff_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'STAFF')],
            'description' => ['nullable', 'string'],
            'target_resolution_date' => ['sometimes', 'nullable', 'date'],
            'attachments.*' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,doc,docx', 'max:10240'],
            'gdrive_links' => ['nullable', 'array'],
            'gdrive_links.*' => ['nullable', 'string', 'url'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        if ($request->user()->role !== 'ADMIN') {
            unset($data['urgency'], $data['assigned_staff_id']);
        }
        $data['user_id'] = $user->id;
        $data['date_submitted'] = now();
        $data['status'] = 'OPEN';
        if (!isset($data['urgency'])) {
            $data['urgency'] = 'NORMAL';
        }

        // Generate Ticket Number: STN-<YEAR>-0001
        $data['ticket_no'] = DB::transaction(function () {
            $year = date('Y');
            $prefix = "STN-{$year}-";

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

        // Process uploaded multi-files
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $ext = strtolower($file->getClientOriginalExtension());
                    $path = $file->store('ticket-attachments', 'local');

                    $ticket->attachments()->create([
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $ext,
                        'file_size' => $file->getSize(),
                    ]);
                }
            }
        }

        // Process Google Drive / external links
        if ($request->filled('gdrive_links')) {
            foreach ($request->input('gdrive_links') as $link) {
                if (!empty($link)) {
                    $ticket->attachments()->create([
                        'file_name' => 'Google Drive Link',
                        'file_type' => 'gdrive',
                        'external_url' => $link,
                    ]);
                }
            }
        }

        $this->writeLog($request, $ticket, 'CREATE', "Ticket {$ticket->ticket_no} created: {$ticket->issue}.");

        return response()->json($ticket->load(['user', 'assignedStaff', 'approvedBy', 'problemCategory', 'attachments']), 201);
    }

    /**
     * Submit star rating and review feedback for a ticket.
     */
    public function submitRating(Request $request, Ticket $ticket)
    {
        $this->ensureTicketAccess($request, $ticket);

        if (!in_array($ticket->status, ['CLOSE', 'RESOLVED'])) {
            return response()->json([
                'message' => 'Ratings and feedback can only be submitted for resolved or closed tickets.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'feedback' => ['required', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ticket->update($validator->validated());
        $this->writeLog($request, $ticket, 'UPDATE', "Rating ({$ticket->rating} stars) and feedback submitted for Ticket {$ticket->ticket_no}.");

        return response()->json([
            'message' => 'Thank you for your rating and feedback!',
            'ticket' => $ticket->fresh(['user', 'assignedStaff', 'approvedBy', 'problemCategory', 'attachments']),
        ]);
    }

    /**
     * Staff self-assign / claim an open ticket.
     */
    public function assignSelf(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        if (!in_array(strtoupper($user->role), ['STAFF', 'ADMIN'])) {
            return response()->json(['message' => 'Only staff members or admins can claim tickets.'], 403);
        }

        $ticket->update([
            'assigned_staff_id' => $user->id,
            'status' => 'ON-GOING',
        ]);

        $this->writeLog(
            $request,
            $ticket,
            'ASSIGN',
            "Ticket {$ticket->ticket_no} claimed by staff {$user->first_name} {$user->last_name}."
        );

        return response()->json([
            'message' => 'Ticket assigned successfully to you.',
            'ticket' => $ticket->fresh(['user', 'assignedStaff', 'approvedBy', 'problemCategory', 'attachments']),
        ]);
    }

    /**
     * Resolve a ticket with remarks.
     */
    public function resolveTicket(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        if (strtoupper($user->role) === 'USER') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($ticket->assigned_staff_id !== $user->id && strtoupper($user->role) !== 'ADMIN') {
            return response()->json(['message' => 'You are not assigned to this ticket.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'remarks' => ['required', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ticket->update([
            'status' => 'RESOLVED',
            'remarks' => $request->remarks,
            'resolution' => $request->resolution,
            'date_action' => now(),
        ]);

        $this->writeLog(
            $request,
            $ticket,
            'UPDATE',
            "Ticket resolved by {$user->first_name} {$user->last_name} with remarks: '{$request->remarks}'."
        );

        return response()->json([
            'message' => 'Ticket resolved successfully.',
            'ticket' => $ticket->fresh(['user', 'assignedStaff', 'approvedBy', 'problemCategory', 'attachments']),
        ]);
    }

    /**
     * Display the specified ticket.
     */
    public function show(Ticket $ticket)
    {
        $this->ensureTicketAccess(request(), $ticket);
        return response()->json($ticket->load(['user', 'assignedStaff', 'approvedBy', 'problemCategory', 'logs', 'attachments']));
    }

    public function getOpenTickets(Request $request)
    {
        $tickets = Ticket::with([
            'user',
            'assignedStaff',
            'approvedBy',
            'problemCategory',
            'attachments',
        ])
            ->whereNull('assigned_staff_id')
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
            'status' => ['sometimes', 'string', 'in:OPEN,ON-GOING,PENDING,ESCALATED,RESOLVED,CLOSE,CANCEL'],
            'urgency' => ['sometimes', 'string', 'in:LOW,NORMAL,HIGH,CRITICAL'],
            'assigned_staff_id' => ['sometimes', 'nullable', Rule::exists('users', 'id')->where('role', 'STAFF')],
            'problem_category_id' => ['nullable', 'exists:problem_categories,id'],
            'description' => ['sometimes', 'nullable', 'string'],
            'remarks' => ['sometimes', 'nullable', 'string'],
            'resolution' => ['sometimes', 'nullable', 'string'],
            'final_remarks' => ['sometimes', 'nullable', 'string'],
            'target_resolution_date' => ['sometimes', 'nullable', 'date'],
            'issue' => ['sometimes', 'required', 'string', 'max:255'],
            'rating' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5'],
            'feedback' => ['sometimes', 'nullable', 'string'],
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
        if (isset($changes['status']) && $changes['status'] === 'CLOSE' && ($original['status'] ?? null) !== 'CLOSE') {
            $changes['date_closed'] = now();
            // Assign the active user who closed it, or fallback to user ID 1 as a default
            $changes['approved_by_id'] = $request->user()->id ?? 1;
        }

        $ticket->update($changes);

        if ($changes) {
            $descriptions = [];
            foreach ($changes as $field => $value) {
                if (($original[$field] ?? null) !== $value) {
                    $descriptions[] = $this->describeTicketChange($field, $value);
                }
            }
            if ($descriptions) {
                $this->writeLog($request, $ticket, 'UPDATE', "Ticket {$ticket->ticket_no} updated: " . implode(', ', $descriptions) . '.');
            }
        }

        // Process uploaded multi-files in update
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $ext = strtolower($file->getClientOriginalExtension());
                    $path = $file->store('ticket-attachments', 'local');

                    $ticket->attachments()->create([
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $ext,
                        'file_size' => $file->getSize(),
                    ]);
                }
            }
        }

        // Process Google Drive / external links in update
        if ($request->filled('gdrive_links')) {
            foreach ($request->input('gdrive_links') as $link) {
                if (!empty($link)) {
                    $ticket->attachments()->create([
                        'file_name' => 'Google Drive Link',
                        'file_type' => 'gdrive',
                        'external_url' => $link,
                    ]);
                }
            }
        }

        return response()->json($ticket->load(['user', 'assignedStaff', 'approvedBy', 'problemCategory', 'attachments']));
    }

    /**
     * Remove the specified ticket from storage.
     */
    public function destroy(Request $request, Ticket $ticket)
    {
        $this->ensureTicketAccess($request, $ticket);
        $ticketNo = $ticket->ticket_no;
        $issue = $ticket->issue;
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

    private function describeTicketChange(string $field, mixed $value): string
    {
        if ($field === 'assigned_staff_id') {
            $staff = $value ? User::find($value) : null;
            $name = $staff
                ? (trim("{$staff->first_name} {$staff->last_name}") ?: $staff->name ?: $staff->email)
                : 'Unassigned';

            return "assigned staff changed to {$name}";
        }

        if ($field === 'problem_category_id') {
            $category = $value ? ProblemCategory::find($value) : null;
            $name = $category
                ? "{$category->type} / {$category->categories}"
                : 'No category';

            return "problem category changed to {$name}";
        }

        $label = str_replace('_', ' ', $field);
        return $label . ' changed to ' . ($value ?? 'empty');
    }

    private function storeAttachment(Request $request, string $field): ?string
    {
        if (!$request->hasFile($field)) {
            return null;
        }

        return $request->file($field)->store('ticket-attachments', 'local');
    }

    public function attachment(Request $request, Ticket $ticket, $type)
    {
        $this->ensureTicketAccess($request, $ticket);

        $field = 'upload_' . $type;

        if (!in_array($field, ['upload_intralab', 'upload_limsportal'])) {
            abort(404, 'Invalid attachment type.');
        }

        $path = $ticket->$field;

        if (!$path) {
            abort(404, 'Attachment not found.');
        }

        // Check local (private) disk first, then public disk for legacy files
        if (Storage::disk('local')->exists($path)) {
            return response()->file(Storage::disk('local')->path($path));
        } elseif (Storage::disk('public')->exists($path)) {
            return response()->file(Storage::disk('public')->path($path));
        }

        abort(404, 'Attachment file not found.');
    }

    public function viewAttachment(Request $request, $id)
    {
        $attachment = \App\Models\TicketAttachment::find($id);

        if (!$attachment) {
            return response()->json(['message' => 'Attachment record not found.', 'id' => $id], 404);
        }

        if ($attachment->external_url) {
            return redirect()->away($attachment->external_url);
        }

        $path = $attachment->file_path;

        if (!$path) {
            return response()->json(['message' => 'Attachment file path is empty.', 'attachment' => $attachment], 404);
        }

        // Clean relative path (e.g. ticket-attachments/xyz.jpg)
        $cleanPath = ltrim(str_replace('\\', '/', $path), '/');

        // Target search locations
        $candidatePaths = [
            storage_path('app/private/' . $cleanPath),
            storage_path('app/' . $cleanPath),
            storage_path('app/public/' . $cleanPath),
            storage_path($cleanPath),
            base_path('storage/app/private/' . $cleanPath),
            base_path('storage/app/' . $cleanPath),
            base_path('storage/app/public/' . $cleanPath),
        ];

        foreach ($candidatePaths as $fullPath) {
            if (file_exists($fullPath) && is_file($fullPath)) {
                return response()->file($fullPath);
            }
        }

        if (Storage::disk('local')->exists($cleanPath)) {
            return response()->file(Storage::disk('local')->path($cleanPath));
        }

        if (Storage::disk('public')->exists($cleanPath)) {
            return response()->file(Storage::disk('public')->path($cleanPath));
        }

        return response()->json([
            'message' => 'File not found on server disk.',
            'file_path_db' => $path,
            'clean_path' => $cleanPath,
            'checked_locations' => $candidatePaths,
        ], 404);
    }
}
