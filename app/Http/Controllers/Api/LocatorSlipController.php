<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LocatorSlip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocatorSlipController extends Controller
{
    public function indexAdmin()
    {
        $slips = LocatorSlip::with(['teacher' => function($q) {
            $q->select('id', 'name', 'employee_number');
        }, 'reviewer' => function($q) {
            $q->select('id', 'name');
        }])->orderBy('created_at', 'desc')->paginate(15);
        
        return response()->json($slips);
    }

    public function indexTeacher()
    {
        $slips = LocatorSlip::where('teacher_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json(['data' => $slips]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date_of_filing' => 'required|date',
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'permanent_station' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'purpose_of_travel' => 'required|string',
            'official_type' => 'required|string|in:Official Business,Official Time',
            'date_time' => 'required|date',
            'time_out' => 'required',
            'expected_return' => 'required',
        ]);

        $validated['teacher_id'] = Auth::id();
        $validated['status'] = 'pending';

        $slip = LocatorSlip::create($validated);

        return response()->json([
            'message' => 'Locator slip submitted successfully.',
            'data' => $slip
        ], 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'admin_remarks' => 'nullable|string'
        ]);

        $slip = LocatorSlip::findOrFail($id);
        
        $slip->update([
            'status' => $validated['status'],
            'admin_remarks' => $validated['admin_remarks'],
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Locator slip status updated.',
            'data' => $slip
        ]);
    }
}
