<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\IssueComment;
use Illuminate\Http\Request;

class IssueCommentController extends Controller
{
    public function index(Issue $issue)
    {
        // newest first + include author
        return $issue->comments()
            ->with('user:id,name')
            ->latest()
            ->get(['id','issue_id','user_id','body','created_at']);
    }

    public function store(Request $request, Issue $issue)
    {
        $data = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $comment = $issue->comments()->create([
            'user_id' => $request->user()->id,
            'body'    => $data['body'],
        ]);

        return response()->json(
            $comment->load('user:id,name'),
            201
        );
    }

    public function destroy(Issue $issue, IssueComment $comment)
    {
        // Simple ownership check
        if ($comment->issue_id !== $issue->id) {
            abort(404);
        }
        if ($comment->user_id !== auth()->id()) {
            abort(403, 'You can only delete your own comments.');
        }

        $comment->delete();

        return response()->json(['ok' => true]);
    }

}
