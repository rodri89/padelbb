<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatService;
use Illuminate\Http\Request;

class ChatMessageController extends Controller
{
    public function store(Request $request, string $threadId, ChatService $chat)
    {
        $data = $request->validate([
            'text' => 'required|string|max:4000',
        ]);

        return response()->json($chat->sendMessage($request->user(), $threadId, $data['text']), 201);
    }
}
