<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatService;
use Illuminate\Http\Request;

class ChatThreadController extends Controller
{
    public function index(Request $request, ChatService $chat)
    {
        return response()->json([
            'threads' => $chat->listThreadsForUser($request->user()),
        ]);
    }

    public function store(Request $request, ChatService $chat)
    {
        $data = $request->validate([
            'participantIds' => 'required|array|min:2',
            'participantIds.*' => 'required|string',
            'participantNames' => 'nullable|array',
            'participantNames.*' => 'nullable|string|max:255',
            'contextType' => 'required|string|in:match,complex,direct',
            'contextId' => 'nullable|string|max:255',
        ]);

        $thread = $chat->createOrGetThread($request->user(), $data);

        return response()->json([
            'threadId' => $thread['threadId'],
            'thread' => $thread,
        ]);
    }
}
