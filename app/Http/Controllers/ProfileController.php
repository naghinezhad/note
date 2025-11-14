<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    // مشاهده پروفایل
    public function profile(Request $request)
    {
        return response()->json(['message' => 'اطلاعات پروفایل با موفقیت دریافت شد.', 'user' => $request->user()]);
    }

    // تغییر رمز عبور
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6',
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'رمز عبور فعلی اشتباه است.'], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'رمز عبور با موفقیت تغییر کرد.']);
    }
}
