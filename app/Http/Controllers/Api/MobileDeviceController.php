<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\MobileDeviceToken;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MobileDeviceController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'userId' => 'required|string',
            'fcmToken' => 'required|string|max:512',
            'platform' => 'required|string|in:android,ios',
            'deviceId' => 'nullable|string|max:255',
        ]);

        if ((string) $request->user()->id !== (string) $data['userId']) {
            return response()->json(['message' => 'The userId does not match the authenticated user.'], 403);
        }

        $device = MobileDeviceToken::updateOrCreate(
            ['fcm_token' => $data['fcmToken']],
            [
                'user_id' => $request->user()->id,
                'platform' => $data['platform'],
                'device_id' => $data['deviceId'] ?? null,
                'last_seen_at' => Carbon::now(),
                'revoked_at' => null,
            ]
        );

        return response()->json([
            'id' => $device->id,
            'userId' => (string) $device->user_id,
            'platform' => $device->platform,
            'deviceId' => $device->device_id,
            'lastSeenAt' => optional($device->last_seen_at)->toIso8601String(),
        ]);
    }

    public function unregister(Request $request)
    {
        $data = $request->validate([
            'userId' => 'required|string',
            'fcmToken' => 'required|string|max:512',
            'platform' => 'required|string|in:android,ios',
        ]);

        if ((string) $request->user()->id !== (string) $data['userId']) {
            return response()->json(['message' => 'The userId does not match the authenticated user.'], 403);
        }

        $updated = MobileDeviceToken::where('fcm_token', $data['fcmToken'])
            ->where('user_id', $request->user()->id)
            ->where('platform', $data['platform'])
            ->update([
                'last_seen_at' => Carbon::now(),
                'revoked_at' => Carbon::now(),
            ]);

        return response()->json([
            'revoked' => $updated > 0,
        ]);
    }
}
