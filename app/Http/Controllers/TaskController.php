<?php

namespace App\Http\Controllers;

use App\Models\UserStory;
use App\Models\Task;
use App\Models\Issue;
use App\Models\User;
use App\Models\Tag;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $columnLabels = [
            'new'          => 'New',
            'in_progress'  => 'In Progress',
            'blocked'      => 'Blocked',
            'ready_for_qa' => 'Ready for QA',
            'done'         => 'Done',
        ];
    
        $users = User::select('id','name')->orderBy('name')->get();
    
        $issueColumnLabels = [
            'open'        => 'Open',
            'in_progress' => 'In Progress',
            'resolved'    => 'Resolved',
        ];
    
        // --- Existing issue filters ---
        $fStatus   = $request->query('issue_status');                  // open | in_progress | resolved
        $fPriority = $request->query('issue_priority');                // low | medium | high
        $fAssignee = $request->query('issue_user');                    // user_id
        $fTags     = array_filter((array) $request->query('issue_tags', []));
        $fQuery    = $request->query('q');
    
        // --- NEW: pin priority to top (applies to both issues and stories) ---
        $pinPriority = $request->query('pin_priority'); // low|medium|high
        $validPin    = in_array($pinPriority, ['low','medium','high'], true) ? $pinPriority : null;
    
        $issuesQuery = Issue::with(['story','task','user','tags'])->latest();
    
        if ($fStatus)   { $issuesQuery->where('status', $fStatus); }
        if ($fPriority) { $issuesQuery->where('priority', $fPriority); }
        if ($fAssignee) { $issuesQuery->where('user_id', $fAssignee); }
        if (!empty($fTags)) {
            $issuesQuery->whereHas('tags', fn($q)=> $q->whereIn('tags.id', $fTags));
        }
        if ($fQuery) {
            $issuesQuery->where(function($q) use ($fQuery){
                $q->where('title','like',"%{$fQuery}%")
                  ->orWhere('description','like',"%{$fQuery}%");
            });
        }
    
        // Order issues so pinned priority appears first, then High > Medium > Low, then newest
        if ($validPin) {
            $issuesQuery->orderByRaw('(priority = ?) DESC', [$validPin]);
        }
        $issuesQuery->orderByRaw("FIELD(priority, 'high','medium','low') ASC")
                    ->orderByDesc('created_at');
    
        $issues = $issuesQuery->get();
        $issuesByStatus = $issues->groupBy('status');
    
        $allTasks = Task::select('id','title')->orderBy('title')->get();
    
        // ---- STORIES (Projects): also pin priority at top, then High > Medium > Low, then newest
        $baseStoryOrder = function ($q) use ($validPin) {
            if ($validPin) {
                $q->orderByRaw('(priority = ?) DESC', [$validPin]);
            }
            $q->orderByRaw("FIELD(priority, 'high','medium','low') ASC")
              ->latest();
        };
    
        $activeStories = UserStory::with(['tasks' => fn($q)=>$q->latest(), 'user'])
            ->where('status','!=','done')
            ->tap($baseStoryOrder)
            ->get();
    
        $doneStories = UserStory::with(['tasks' => fn($q)=>$q->latest(), 'user'])
            ->where('status','done')
            ->tap($baseStoryOrder)
            ->get();
    
        // tags for filters
        $tags = Tag::orderBy('name')->get();
    
        return view('dashboard', compact(
            'activeStories',
            'doneStories',
            'columnLabels',
            'users',
            'issueColumnLabels',
            'issuesByStatus',
            'allTasks',
            'tags'
        ));
    }

    public function updateStatus(Request $request, Task $task)
    {
        $data = $request->validate([
            'status' => 'required|in:new,in_progress,blocked,ready_for_qa,done',
        ]);

        $task->update(['status' => $data['status']]);

        // If you added this helper on UserStory, keep it
        optional($task->story)->recomputeStatusFromTasks();

        return response()->json([
            'ok'           => true,
            'task_id'      => $task->id,
            'status'       => $task->status,
            'story_id'     => $task->user_story_id,
            'story_status' => optional($task->story)->status,
        ]);
    }

    public function update(Request $request, Task $task)
    {
        $data = $request->validate([
            'user_story_id'       => 'required|exists:user_stories,id',
            'title'               => 'required|string|max:255',
            'description'         => 'nullable|string',
            'acceptance_criteria' => 'nullable|string',
            'story_points'        => 'nullable|in:1,2,3,5,8',
            'priority'            => 'required|in:low,medium,high',
            'status'              => 'required|in:new,in_progress,blocked,ready_for_qa,done',
            'user_id'             => 'nullable|exists:users,id',
        ]);

        $task->update($data);

        optional($task->story)->recomputeStatusFromTasks();

        return response()->json(['ok' => true]);
    }
}