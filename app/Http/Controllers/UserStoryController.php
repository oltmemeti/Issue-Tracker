<?php
namespace App\Http\Controllers;

use App\Models\UserStory;
use Illuminate\Http\Request;

class UserStoryController extends Controller
{
    public function store(Request $request)
    {
        UserStory::create($request->only(['title','description','priority','status']));
        return back()->with('success','User Story created successfully!');
    }
}
