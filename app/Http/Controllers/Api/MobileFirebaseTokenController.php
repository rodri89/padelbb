<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FirebaseAdminService;
use Illuminate\Http\Request;

class MobileFirebaseTokenController extends Controller
{
    public function store(Request $request, FirebaseAdminService $firebase)
    {
        return response()->json([
            'firebaseToken' => $firebase->createCustomToken((string) $request->user()->id),
        ]);
    }
}
