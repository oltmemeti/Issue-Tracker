<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskCommentController extends Controller
{
    public function index(Task $task)
    {
        return $task->comments()
            ->with('user:id,name') // eager load user name
            ->latest()
            ->get(['id','task_id','user_id','body','created_at']);
    }

    public function store(Request $request, Task $task)
    {
        $data = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $comment = $task->comments()->create([
            'user_id' => Auth::id(),   // ✅ logged-in user
            'body'    => $data['body'],
        ]);

        return response()->json([
            'ok'      => true,
            'comment' => $comment->load('user:id,name'),
        ], 201);
    }

    public function destroy(Task $task, TaskComment $comment)
    {
        // simple ownership check: only author can delete
        if ($comment->user_id !== Auth::id()) {
            abort(403);
        }

        $comment->delete();
        return response()->json(['ok' => true]);
    }
}