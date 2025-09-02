<?php

namespace App\Http\Controllers;

use App\Models\UserStory;
use App\Models\Task;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Http\Request;



class TaskController extends Controller
{
    public function index()
    {
        $stories = UserStory::with(['tasks' => fn($q) => $q->latest(), 'user'])
            ->latest()->get();
    
        $columnLabels = [
            'new' => 'New',
            'in_progress' => 'In Progress',
            'blocked' => 'Blocked',
            'ready_for_qa' => 'Ready for QA',
            'done' => 'Done',
        ];
    
        $users = User::select('id','name')->orderBy('name')->get();
    
        // ✨ Issues lane data
        $issueColumnLabels = [
            'open'        => 'Open',
            'in_progress' => 'In Progress',
            'resolved'    => 'Resolved',
        ];
    
        $issues = Issue::with(['task', 'user'])->latest()->get();
        $issuesByStatus = $issues->groupBy('status');
    
        // (optional) for some selects, even if not required now
        $allTasks = Task::select('id','title')->orderBy('title')->get();
    
        return view('dashboard', compact(
            'stories',
            'columnLabels',
            'users',
            'issueColumnLabels',
            'issuesByStatus',
            'allTasks'
        ));
    }
}
