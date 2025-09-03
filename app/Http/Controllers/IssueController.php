<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\Task;
use Illuminate\Http\Request;
    
    class IssueController extends Controller
    {
        public function index()
        {
            $issues = Issue::latest()->with('story', 'user', 'task', 'tags')->get();
            return view('issues.index', compact('issues'));
        }
    
        public function create()
        {
            $tasks = Task::all();
            return view('issues.create', compact('tasks'));
        }
    
        public function store(Request $request)
        {
            $data = $request->validate([
                'title'         => 'required|string|max:255',
                'description'   => 'nullable|string',
                'type'          => 'required|in:bug,feature,improvement',
                'priority'      => 'required|in:low,medium,high',
                'status'        => 'required|in:open,in_progress,resolved',
                'user_story_id' => 'nullable|exists:user_stories,id',
                'task_id'       => 'nullable|exists:tasks,id',
                'user_id'       => 'nullable|exists:users,id',
                'tags'          => 'array',
                'tags.*'        => 'exists:tags,id',
            ]);
        
            $issue = Issue::create($data);
            if (!empty($data['tags'])) {
                $issue->tags()->sync($data['tags']);
            }
        
            return back()->with('success', 'Issue reported!');
        }
        
    }
    