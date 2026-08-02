<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function dismiss(Request $request)
    {
        $user = $request->user();
        $user->notificationsDismissedAt = now();
        $user->save();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Bildirimler temizlendi.']);
        }

        return back();
    }
}
