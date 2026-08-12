<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    private const DEFAULTS = [
        'maintenance' => false,
        'email_notifs' => true,
        'auto_close' => true,
        'audit_log' => true,
        'sla_critical' => 2,
        'sla_high' => 8,
        'sla_medium' => 24,
        'sla_low' => 72,
    ];

    public function show(): JsonResponse
    {
        $saved = SystemSetting::all()->mapWithKeys(fn (SystemSetting $setting) => [$setting->key => $setting->value])->all();
        return response()->json(array_replace(self::DEFAULTS, $saved));
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'maintenance' => 'required|boolean',
            'email_notifs' => 'required|boolean',
            'auto_close' => 'required|boolean',
            'audit_log' => 'required|boolean',
            'sla_critical' => 'required|integer|min:1|max:720',
            'sla_high' => 'required|integer|min:1|max:720',
            'sla_medium' => 'required|integer|min:1|max:720',
            'sla_low' => 'required|integer|min:1|max:720',
        ]);

        $saved = SystemSetting::all()->mapWithKeys(fn (SystemSetting $setting) => [$setting->key => $setting->value])->all();
        $current = array_replace(self::DEFAULTS, $saved);
        $changedKeys = [];
        foreach ($validated as $key => $value) {
            if ($current[$key] !== $value) {
                $changedKeys[] = $key;
            }
            SystemSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        if ($changedKeys) {
            Log::create([
                'user_id' => $request->user()->id,
                'action' => 'UPDATE',
                'message' => 'System settings updated: ' . implode(', ', $changedKeys) . '.',
                'address' => $request->ip(),
            ]);
        }

        return response()->json($validated);
    }
}
