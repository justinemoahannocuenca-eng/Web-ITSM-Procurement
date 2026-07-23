<?php

namespace Modules\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class AccountController extends Controller
{
    public function updateProfile(Request $request)
    {
        $user = Auth::guard('ecommerce')->user();

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $user->id,
            'gender' => 'nullable|in:male,female,other',
            'dob_year' => 'nullable|integer',
            'dob_month' => 'nullable|integer',
            'dob_day' => 'nullable|integer',
        ]);

        $user->name = $validated['name'] ?? null;
        $user->email = $validated['email'] ?? null;
        $user->phone = $validated['phone'] ?? null;
        $user->gender = $validated['gender'] ?? null;

        if (!empty($validated['dob_year']) && !empty($validated['dob_month']) && !empty($validated['dob_day'])) {
            try {
                $user->dob = Carbon::createFromDate($validated['dob_year'], $validated['dob_month'], $validated['dob_day'])->format('Y-m-d');
            } catch (\Exception $e) {
                // Invalid date
            }
        }

        $user->save();

        return redirect()->route('ecommerce.account.profile')->with('success', 'Profile updated successfully!');
    }
}
