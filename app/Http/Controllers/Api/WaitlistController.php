<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Waitlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;

class WaitlistController extends Controller
{
    /**
     * Join the waitlist
     */
    public function join(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:waitlist,email',
            'phone' => 'required|string|max:20',
        ], [
            'email.unique' => 'This email is already on the waitlist.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $waitlistEntry = Waitlist::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'joined_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Successfully joined the waitlist! We\'ll notify you when EasyGear launches.',
                'data' => [
                    'id' => $waitlistEntry->id,
                    'name' => $waitlistEntry->name,
                    'email' => $waitlistEntry->email,
                    'joined_at' => $waitlistEntry->joined_at->format('Y-m-d H:i:s'),
                ]
            ], 201);

        } catch (QueryException $e) {
            // Handle unique constraint violation (in case of race conditions)
            if ($e->errorInfo[1] === 1062) {
                return response()->json([
                    'success' => false,
                    'message' => 'This email is already on the waitlist.',
                    'errors' => [
                        'email' => ['This email is already on the waitlist.']
                    ]
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     * Get waitlist statistics (admin only)
     */
    public function stats(Request $request)
    {
        $total = Waitlist::count();
        $recent = Waitlist::recent(7)->count();
        $today = Waitlist::whereDate('created_at', today())->count();

        return response()->json([
            'success' => true,
            'message' => 'Waitlist statistics retrieved successfully',
            'data' => [
                'total_signups' => $total,
                'recent_signups' => $recent, // Last 7 days
                'today_signups' => $today,
                'growth_rate' => $total > 0 ? round(($recent / $total) * 100, 2) : 0,
            ]
        ], 200);
    }

    /**
     * Check if email exists in waitlist
     */
    public function checkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email format',
                'errors' => $validator->errors()
            ], 422);
        }

        $exists = Waitlist::emailExists($request->email);

        return response()->json([
            'success' => true,
            'message' => 'Email check completed',
            'data' => [
                'email' => $request->email,
                'exists' => $exists,
                'status' => $exists ? 'already_on_waitlist' : 'available'
            ]
        ], 200);
    }
}
