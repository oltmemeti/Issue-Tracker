<?php
namespace App\Http\Controllers;

use App\Models\UserStory;
use Illuminate\Http\Request;

class UserStoryController extends Controller
{
public function store(Request $request)
{
    $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'priority' => 'nullable|string',
        'status' => 'nullable|string',
        'user_id' => 'nullable|exists:users,id',
        'deadline' => 'nullable|date'
    ]);
    

    UserStory::create($request->only([
        'title', 'description', 'priority', 'status', 'user_id','deadline'
    ]));

    return back()->with('success', 'User Story created successfully!');
}

    
}
