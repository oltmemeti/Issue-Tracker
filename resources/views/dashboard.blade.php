<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Issue Tracker</title>

  <meta name="csrf-token" content="{{ csrf_token() }}">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <style>
    .task-card{transition:transform .15s ease, box-shadow .15s ease}
    .task-card:hover{transform:translateY(-2px); box-shadow:0 6px 20px -10px rgba(0,0,0,.35)}
    .task-card.dragging{opacity:.75; transform:rotate(1deg) scale(1.01)}
    .dropzone{transition:background-color .15s ease, border-color .15s ease}
    .dropzone.drag-over{background:rgba(59,130,246,.08); border-color:rgba(59,130,246,.6)}
    .modal{display:none}
    .modal.show{display:flex}
  </style>
</head>

@php
  use Illuminate\Support\Str;
  $allStories   = ($activeStories ?? collect())->concat($doneStories ?? collect());
  $selectedTags = collect(request('issue_tags', []))->map(fn($x)=>(string)$x)->all();
  $hasFilters   = request()->filled('issue_status')
                || request()->filled('issue_priority')
                || request()->filled('issue_user')
                || count($selectedTags) > 0
                || request()->filled('q');
@endphp

<body class="bg-gray-100">

  {{-- ===================== Header ======================== --}}
  <header class="bg-white shadow">
    <div class="max-w-7xl mx-auto flex justify-between items-center p-4">
      <div>
        <h1 class="text-xl font-bold text-gray-800">Issue Tracker</h1>
        <p class="text-sm text-gray-500">Professional task management</p>
      </div>
      <div class="flex gap-2">
        <button id="openStoryBtn" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">+ Story</button>
        <button id="openTaskBtn"  class="bg-blue-600  hover:bg-blue-700  text-white px-4 py-2 rounded-md">+ Task</button>
      </div>
    </div>
  </header>

  {{-- ===================== Banner ======================== --}}
  <div class="bg-gradient-to-r from-blue-500 to-blue-700 text-center text-white py-6">
    <h2 class="text-2xl font-semibold">Professional Task Management</h2>
  </div>

  <div class="max-w-7xl mx-auto py-8 space-y-6">

    {{-- ===================== Filters toolbar ======================== --}}
    <form id="filtersForm" method="GET" action="{{ route('dashboard') }}" class="bg-white border rounded-lg p-4">
      <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
        {{-- Status --}}
        <div>
          <label class="block text-xs text-gray-500 mb-1">Status</label>
          <select name="issue_status" class="w-full border rounded-md p-2">
            <option value="">All</option>
            @foreach(['open'=>'Open','in_progress'=>'In Progress','resolved'=>'Resolved'] as $v => $label)
              <option value="{{ $v }}" @selected(request('issue_status')===$v)>{{ $label }}</option>
            @endforeach
          </select>
        </div>

        {{-- Priority pin (UI only; sorting handled in controller) --}}
        <div>
          <label class="block text-xs text-gray-500 mb-1">Priority</label>
          <select name="pin_priority" class="w-full border rounded-md p-2">
            <option value="">— No pin —</option>
            @foreach(['high'=>'High','medium'=>'Medium','low'=>'Low'] as $v => $label)
              <option value="{{ $v }}" @selected(request('pin_priority')===$v)>{{ $label }}</option>
            @endforeach
          </select>
          <p class="text-[10px] text-gray-400 mt-1">Keeps the selected priority at the top across Projects & Issues.</p>
        </div>

        {{-- Assignee --}}
        <div>
          <label class="block text-xs text-gray-500 mb-1">Assignee</label>
          <select name="issue_user" class="w-full border rounded-md p-2">
            <option value="">Anyone</option>
            @foreach($users as $u)
              <option value="{{ $u->id }}" @selected((string)request('issue_user')===(string)$u->id)>{{ $u->name }}</option>
            @endforeach
          </select>
        </div>

        {{-- Tags dropdown (no Alpine; pure jQuery) --}}
        <div class="md:col-span-2">
          <label class="block text-xs text-gray-500 mb-1">Tags</label>

          <button type="button" id="tagsDropdownBtn"
                  class="w-full border rounded-md p-2 bg-white flex items-center justify-between">
            <span id="tagsDropdownLabel" class="text-sm text-gray-700">
              {{ count($selectedTags) ? count($selectedTags).' selected' : 'Select tags' }}
            </span>
            <svg class="w-4 h-4 text-gray-500 transition-transform" id="tagsDropdownChevron" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <div id="tagsDropdownPanel"
               class="hidden relative z-30 mt-1 w-full bg-white border rounded-md shadow-lg max-h-56 overflow-y-auto">
            @foreach($tags as $tag)
              <label class="flex items-center px-3 py-2 hover:bg-gray-50 text-sm">
                <input type="checkbox"
                       class="mr-2 tags-multi"
                       name="issue_tags[]"
                       value="{{ $tag->id }}"
                       @checked(in_array((string)$tag->id, $selectedTags))>
                <span>{{ $tag->name }}</span>
              </label>
            @endforeach
            <div class="border-t p-2 flex items-center justify-between">
              <button type="button" id="tagsDropdownClear" class="text-xs text-gray-600 hover:underline">Clear selection</button>
              <button type="button" id="tagsDropdownClose" class="text-xs text-gray-600 hover:underline">Close</button>
            </div>
          </div>

          <p class="text-[10px] text-gray-400 mt-1">Click to expand and select multiple tags.</p>
        </div>

        {{-- Search --}}
        <div>
          <label class="block text-xs text-gray-500 mb-1">Search</label>
          <input type="text" name="q" value="{{ request('q') }}" placeholder="Title or description"
                 class="w-full border rounded-md p-2">
        </div>
      </div>

      <div class="mt-3 flex gap-2">
        <button class="px-3 py-2 bg-gray-800 text-white rounded-md">Apply</button>
        <a href="{{ route('dashboard') }}" class="px-3 py-2 bg-gray-200 rounded-md">Reset</a>
      </div>
    </form>

    {{-- ===================== Active Stories ======================== --}}
    @if(($activeStories ?? collect())->count())
      @foreach($activeStories as $story)
        <div class="bg-white shadow rounded-lg p-4">
          <div class="flex items-start justify-between mb-4">
            <div>
              <h3 class="text-lg font-semibold">{{ $story->title }}</h3>
              <p class="text-sm text-gray-600">{{ $story->description }}</p>
              <p class="text-xs text-gray-400 mt-1">
                {{ $story->user ? 'Assigned to: '.$story->user->name : 'Unassigned' }}
              </p>
              <p class="text-xs text-red-500 mt-1">
                {{ $story->deadline ? 'Deadline: '.\Carbon\Carbon::parse($story->deadline)->format('M d, Y') : 'No deadline' }}
              </p>
            </div>

            <button class="open-issue bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded-md text-sm"
                    data-story="{{ $story->id }}">+ Issue</button>
          </div>

          <div class="overflow-x-auto">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 min-w-[900px]">
              @foreach($columnLabels as $key => $label)
                @php($list = $key==='ready_for_qa' ? $story->tasks->whereIn('status',['ready_for_qa'])
                                                    : $story->tasks->where('status',$key))
                <div class="bg-gray-50 border rounded-lg p-3 dropzone" data-status="{{ $key }}">
                  <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold">{{ $label }}</h4>
                    <span class="text-xs text-gray-500">{{ $list->count() }}</span>
                  </div>

                  <div class="space-y-3">
                    @forelse($list as $task)
                      <div class="p-3 bg-white border rounded cursor-pointer task-card open-edit-task"
                           data-id="{{ $task->id }}"
                           data-story="{{ $task->user_story_id }}"
                           data-title="{{ e($task->title) }}"
                           data-description="{{ e($task->description) }}"
                           data-criteria="{{ e($task->acceptance_criteria) }}"
                           data-points="{{ $task->story_points }}"
                           data-priority="{{ $task->priority }}"
                           data-status="{{ $task->status }}"
                           data-user="{{ $task->user_id ?? '' }}"
                           draggable="true">
                        <div class="flex justify-between">
                          <span class="font-medium text-sm">{{ $task->title }}</span>
                          <span class="text-xs {{ $task->priority==='high'?'text-red-600':($task->priority==='medium'?'text-yellow-600':'text-green-600') }}">
                            {{ ucfirst($task->priority) }}
                          </span>
                        </div>
                        @if($task->user)
                          <p class="text-xs text-gray-400 mt-1">Assigned to: {{ $task->user->name }}</p>
                        @endif
                        <p class="text-xs text-gray-500 mt-1">{{ Str::limit($task->description, 50) }}</p>
                        <div class="flex justify-between items-center mt-2">
                          <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">{{ $task->story_points }} SP</span>
                          <span class="text-xs text-gray-400">#{{ $task->id }}</span>
                        </div>
                      </div>
                    @empty
                      <p class="text-xs text-gray-400">No tasks.</p>
                    @endforelse
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </div>
      @endforeach
    @else
      <div class="max-w-3xl mx-auto bg-white rounded-lg shadow p-8 text-center">
        <h3 class="text-lg font-semibold mb-2">No user stories yet</h3>
        <p class="text-gray-500 mb-4">Create a story and then add Dev/QA tasks inside it.</p>
        <button id="openStoryBtnEmpty" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
          + Create Your First Story
        </button>
      </div>
    @endif

    {{-- ===================== Issues Board ======================== --}}
    <div class="bg-white shadow rounded-lg p-4">
      @php($filteredTotal = $issuesByStatus->flatten(1)->count())
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-semibold">Issues</h3>
        <span class="text-xs text-gray-500">{{ $filteredTotal }} total</span>
      </div>

      {{-- Filter chips --}}
      @if($hasFilters)
        <div class="mb-4 flex flex-wrap items-center gap-2">
          <span class="text-xs text-gray-500 mr-1">Filters:</span>

          @if(request('issue_status'))
            <span class="text-xs bg-gray-100 text-gray-800 px-2 py-1 rounded">Status: {{ Str::headline(request('issue_status')) }}</span>
          @endif
          @if(request('issue_priority'))
            <span class="text-xs bg-gray-100 text-gray-800 px-2 py-1 rounded">Priority: {{ Str::headline(request('issue_priority')) }}</span>
          @endif
          @if(request('issue_user'))
            @php($u = $users->firstWhere('id', request('issue_user')))
            <span class="text-xs bg-gray-100 text-gray-800 px-2 py-1 rounded">Assignee: {{ $u?->name ?? ('#'.request('issue_user')) }}</span>
          @endif
          @if(count($selectedTags))
            <span class="text-xs bg-gray-100 text-gray-800 px-2 py-1 rounded">
              Tags:
              @foreach($tags->whereIn('id', $selectedTags) as $t)
                <span class="ml-1">{{ $t->name }}</span>
              @endforeach
            </span>
          @endif
          @if(request('q'))
            <span class="text-xs bg-gray-100 text-gray-800 px-2 py-1 rounded">Search: “{{ request('q') }}”</span>
          @endif

          <a href="{{ route('dashboard') }}" class="text-xs px-2 py-1 bg-gray-800 text-white rounded">Clear</a>
        </div>
      @endif

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach($issueColumnLabels as $statusKey => $statusLabel)
          @php($list = $issuesByStatus->get($statusKey, collect()))
          <div class="bg-gray-50 border rounded-lg p-3">
            <div class="flex items-center justify-between mb-3">
              <h4 class="font-semibold">{{ $statusLabel }}</h4>
              <span class="text-xs text-gray-500">{{ $list->count() }}</span>
            </div>

            <div class="space-y-3">
              @forelse($list as $issue)
                <div class="p-3 bg-white border rounded task-card">
                  <div class="flex justify-between items-start gap-3">
                    <div class="min-w-0">
                      <div class="font-medium text-sm truncate" title="{{ $issue->title }}">{{ $issue->title }}</div>
                      <div class="text-xs text-gray-500 mt-1">
                        Type: <span class="uppercase">{{ $issue->type }}</span> ·
                        Priority:
                        <span class="{{ $issue->priority==='high'?'text-red-600':($issue->priority==='medium'?'text-yellow-700':'text-green-700') }}">
                          {{ ucfirst($issue->priority) }}
                        </span>
                      </div>

                      <div class="text-xs text-gray-400 mt-1">
                        {{ $issue->user ? 'Assigned to: '.$issue->user->name : 'Unassigned' }}
                      </div>

                      @if($issue->story)
                        <div class="text-xs text-indigo-700 mt-1">
                          Project: #{{ $issue->story->id }} — {{ Str::limit($issue->story->title, 60) }}
                        </div>
                      @else
                        <div class="text-xs text-gray-400 mt-1">No Project</div>
                      @endif

                      @if($issue->task)
                        <div class="text-xs text-blue-700 mt-1">
                          Linked Task: #{{ $issue->task->id }} — {{ Str::limit($issue->task->title, 40) }}
                        </div>
                      @endif

                      @if($issue->tags && $issue->tags->count())
                        <div class="flex flex-wrap gap-1 mt-2">
                          @foreach($issue->tags as $tag)
                            @php($activeTag = in_array((string)$tag->id, $selectedTags))
                            <span class="text-[10px] px-2 py-0.5 rounded {{ $activeTag ? 'ring-2 ring-offset-1 ring-indigo-400' : '' }}"
                                  style="background-color: {{ $tag->color ?? '#e5e7eb' }}; color:#fff;">
                              {{ $tag->name }}
                            </span>
                          @endforeach
                        </div>
                      @endif
                    </div>

                    <div class="flex flex-col items-end gap-2 shrink-0">
                      <span class="text-[10px] px-2 py-1 rounded bg-gray-100 text-gray-700">#{{ $issue->id }}</span>
                      <button class="open-issue-comments text-xs px-2 py-1 rounded bg-gray-800 text-white"
                              data-issue="{{ $issue->id }}" data-title="{{ e($issue->title) }}">Comments</button>
                    </div>
                  </div>

                  @if($issue->description)
                    <p class="text-xs text-gray-600 mt-2">{{ Str::limit($issue->description, 120) }}</p>
                  @endif
                </div>
              @empty
                <p class="text-xs text-gray-400">
                  {{ $hasFilters ? 'No issues match the current filters.' : 'No issues.' }}
                </p>
              @endforelse
            </div>
          </div>
        @endforeach
      </div>
    </div>

    {{-- ===================== Completed Stories ======================== --}}
    @if(($doneStories ?? collect())->count())
      <div class="py-2 space-y-4">
        <h3 class="text-lg font-semibold text-gray-800">Completed Projects</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          @foreach($doneStories as $story)
            <div class="bg-white border rounded-lg p-4">
              <div class="flex items-start justify-between">
                <div>
                  <div class="text-sm uppercase tracking-wide text-green-700 font-semibold">Done</div>
                  <h4 class="text-base font-semibold mt-1">{{ $story->title }}</h4>
                  <p class="text-sm text-gray-600 mt-1">{{ $story->description }}</p>
                  @if($story->user)
                    <p class="text-xs text-gray-400 mt-2">Owner: {{ $story->user->name }}</p>
                  @endif
                  @if($story->deadline)
                    <p class="text-xs text-gray-400">Deadline: {{ \Carbon\Carbon::parse($story->deadline)->format('M d, Y') }}</p>
                  @endif
                </div>
                <span class="text-[10px] px-2 py-1 rounded bg-green-100 text-green-800">#{{ $story->id }}</span>
              </div>

              @php($doneTasks = $story->tasks->where('status','done'))
              @if($doneTasks->count())
                <div class="mt-3">
                  <div class="text-xs text-gray-500 mb-1">Tasks ({{ $doneTasks->count() }}):</div>
                  <ul class="space-y-1">
                    @foreach($doneTasks->take(5) as $t)
                      <li class="text-xs text-gray-600">✓ {{ $t->title }}</li>
                    @endforeach
                    @if($doneTasks->count() > 5)
                      <li class="text-xs text-gray-400">… and {{ $doneTasks->count() - 5 }} more</li>
                    @endif
                  </ul>
                </div>
              @endif
            </div>
          @endforeach
        </div>
      </div>
    @endif
  </div>

  {{-- ===================== Modals ======================== --}}
  {{-- Create Story --}}
  <div id="storyModal" class="modal fixed inset-0 bg-black/40 items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl p-6 relative">
      <button type="button" class="closeModal absolute top-3 right-3 text-gray-500 hover:text-gray-700" data-target="#storyModal">✖</button>
      <h2 class="text-lg font-semibold mb-4">Create Story</h2>

      <form id="storyForm" class="space-y-4">
        @csrf
        <div id="storyErrors" class="text-sm text-red-600 space-y-1 hidden"></div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Title</label>
          <input name="title" class="w-full mt-1 border rounded-md p-2" required />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Description</label>
          <textarea name="description" class="w-full mt-1 border rounded-md p-2"></textarea>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Assign To</label>
          <select name="user_id" class="w-full border rounded-md p-2">
            <option value="">-- Select User --</option>
            @foreach($users as $user)
              <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Priority</label>
          <select name="priority" class="w-full border rounded-md p-2">
            <option value="low">Low</option>
            <option value="medium" selected>Medium</option>
            <option value="high">High</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Status</label>
          <select name="status" class="w-full border rounded-md p-2">
            <option value="new" selected>New</option>
            <option value="in_progress">In Progress</option>
            <option value="blocked">Blocked</option>
            <option value="ready_for_qa">Ready for QA</option>
            <option value="done">Done</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Deadline</label>
          <input type="date" name="deadline" class="w-full mt-1 border rounded-md p-2" />
        </div>

        <div class="flex justify-end gap-2">
          <button type="button" class="closeModal px-4 py-2 bg-gray-200 rounded-md" data-target="#storyModal">Cancel</button>
          <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md">Create</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Create Task --}}
  <div id="taskModal" class="modal fixed inset-0 bg-black/40 items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl p-6 relative">
      <button type="button" class="closeModal absolute top-3 right-3 text-gray-500 hover:text-gray-700" data-target="#taskModal">✖</button>
      <h2 class="text-xl font-semibold mb-4">+ Create New Task</h2>

      <form id="taskForm">
        @csrf
        <div id="taskErrors" class="text-sm text-red-600 space-y-1 hidden"></div>

        <label class="block text-sm font-medium text-gray-700">User Story</label>
        <select name="user_story_id" class="w-full border rounded-md p-2 mb-4" {{ $allStories->isEmpty() ? 'disabled' : '' }} required>
          @forelse($allStories as $story)
            <option value="{{ $story->id }}">{{ $story->title }}</option>
          @empty
            <option value="">No stories yet</option>
          @endforelse
        </select>

        <label class="block text-sm font-medium text-gray-700">Task Title</label>
        <input type="text" name="title" class="w-full border rounded-md p-2 mb-4" required>

        <label class="block text-sm font-medium text-gray-700">Description</label>
        <textarea name="description" class="w-full border rounded-md p-2 mb-4"></textarea>

        <label class="block text-sm font-medium text-gray-700">Acceptance Criteria</label>
        <textarea name="acceptance_criteria" class="w-full border rounded-md p-2 mb-4"></textarea>

        <label class="block text-sm font-medium text-gray-700">Story Points</label>
        <select name="story_points" class="w-full border rounded-md p-2 mb-4">
          <option>1</option><option>2</option><option>3</option><option>5</option><option>8</option>
        </select>

        <label class="block text-sm font-medium text-gray-700">Priority</label>
        <select name="priority" class="w-full border rounded-md p-2 mb-4">
          <option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option>
        </select>

        <div>
          <label class="block text-sm font-medium text-gray-700">Assign To</label>
          <select name="user_id" class="w-full border rounded-md p-2">
            <option value="">-- Select User --</option>
            @foreach($users as $user)
              <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endforeach
          </select>
        </div>

        <label class="block text-sm font-medium text-gray-700">Status</label>
        <select name="status" class="w-full border rounded-md p-2 mb-4">
          <option value="new" selected>New</option>
          <option value="in_progress">In Progress</option>
          <option value="blocked">Blocked</option>
          <option value="ready_for_qa">Ready for QA</option>
          <option value="done">Done</option>
        </select>

        <div class="flex justify-end gap-2">
          <button type="button" class="closeModal px-4 py-2 bg-gray-200 rounded-md" data-target="#taskModal">Cancel</button>
          <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md {{ $allStories->isEmpty() ? 'opacity-50 cursor-not-allowed' : '' }}"
            @if($allStories->isEmpty()) disabled title="Create a User Story first" @endif>
            Create Task
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- Create Issue --}}
  <div id="issueModal" class="modal fixed inset-0 bg-black/40 items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl p-6 relative">
      <button type="button" class="closeModal absolute top-3 right-3 text-gray-500 hover:text-gray-700" data-target="#issueModal">✖</button>
      <h2 class="text-xl font-semibold mb-4">+ Create Issue</h2>

      <form id="issueForm" class="space-y-4">
        @csrf
        <div id="issueErrors" class="text-sm text-red-600 space-y-1 hidden"></div>

        <div>
          <label class="block text-sm font-medium">Assign to Project (User Story)</label>
          <select name="user_story_id" id="issue_story_id" class="w-full border rounded-md p-2" required>
            <option value="">— Select Project —</option>
            @foreach($allStories as $s)
              <option value="{{ $s->id }}">{{ $s->title }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium">Title</label>
          <input name="title" class="w-full border rounded-md p-2" required>
        </div>

        <div>
          <label class="block text-sm font-medium">Description</label>
          <textarea name="description" class="w-full border rounded-md p-2"></textarea>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium">Type</label>
            <select name="type" class="w-full border rounded-md p-2">
              <option value="bug">Bug</option>
              <option value="feature">Feature</option>
              <option value="improvement">Improvement</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium">Priority</label>
            <select name="priority" class="w-full border rounded-md p-2">
              <option value="low">Low</option>
              <option value="medium" selected>Medium</option>
              <option value="high">High</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium">Status</label>
            <select name="status" class="w-full border rounded-md p-2">
              <option value="open" selected>Open</option>
              <option value="in_progress">In Progress</option>
              <option value="resolved">Resolved</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium">Tags</label>
            <select name="tags[]" multiple class="w-full border rounded-md p-2">
              @foreach($tags as $tag)
                <option value="{{ $tag->id }}">{{ $tag->name }}</option>
              @endforeach
            </select>
            <p class="text-xs text-gray-400 mt-1">Hold Ctrl (Windows) or Cmd (Mac) to select multiple.</p>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium">Assign To</label>
          <select name="user_id" class="w-full border rounded-md p-2">
            <option value="">— Unassigned —</option>
            @foreach($users as $u)
              <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="flex justify-end gap-2 mt-4">
          <button type="button" class="closeModal px-4 py-2 bg-gray-200 rounded-md" data-target="#issueModal">Cancel</button>
          <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md">Create Issue</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Edit Task --}}
  <div id="editTaskModal" class="modal fixed inset-0 bg-black/40 items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-full sm:max-w-2xl p-0 relative">
      <button type="button" class="closeModal absolute top-3 right-3 text-gray-500 hover:text-gray-700" data-target="#editTaskModal">✖</button>

      <div class="px-6 pt-5 pb-3 border-b">
        <h2 class="text-xl font-semibold">Edit Task</h2>
      </div>

      <div class="p-6">
        <form id="editTaskForm" method="POST">
          @csrf
          <input type="hidden" name="_method" value="PUT">
          <input type="hidden" id="edit_task_id">

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <fieldset class="space-y-4">
              <legend class="text-sm font-semibold text-gray-800">Details</legend>

              <div>
                <label class="block text-sm font-medium text-gray-700">Title</label>
                <input type="text" name="title" id="edit_title" class="w-full border rounded-md p-2" required>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" id="edit_description" class="w-full border rounded-md p-2 min-h-[110px]"></textarea>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Acceptance Criteria</label>
                <textarea name="acceptance_criteria" id="edit_criteria" class="w-full border rounded-md p-2 min-h-[110px]"></textarea>
              </div>
            </fieldset>

            <fieldset class="space-y-4">
              <legend class="text-sm font-semibold text-gray-800">Planning & Assignment</legend>

              <div>
                <label class="block text-sm font-medium text-gray-700">User Story</label>
                <select name="user_story_id" id="edit_user_story_id" class="w-full border rounded-md p-2">
                  @foreach(($allStories ?? $stories ?? collect()) as $story)
                    <option value="{{ $story->id }}">{{ $story->title }}</option>
                  @endforeach
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Story Points</label>
                <select name="story_points" id="edit_points" class="w-full border rounded-md p-2">
                  <option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="5">5</option><option value="8">8</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Priority</label>
                <select name="priority" id="edit_priority" class="w-full border rounded-md p-2">
                  <option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Assign To</label>
                <select name="user_id" id="edit_user_id" class="w-full border rounded-md p-2">
                  <option value="">-- Select User --</option>
                  @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                  @endforeach
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Status</label>
                <select name="status" id="edit_status" class="w-full border rounded-md p-2">
                  <option value="new">New</option>
                  <option value="in_progress">In Progress</option>
                  <option value="blocked">Blocked</option>
                  <option value="ready_for_qa">Ready for QA</option>
                  <option value="done">Done</option>
                </select>
              </div>
            </fieldset>
          </div>

          <div id="editTaskErrors" class="text-sm text-red-600 space-y-1 hidden mt-4"></div>

          <div class="flex justify-end gap-2 mt-6">
            <button type="button" class="closeModal px-4 py-2 bg-gray-200 rounded-md" data-target="#editTaskModal">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">Save Changes</button>
          </div>
        </form>

        {{-- Task Comments --}}
        <div class="mt-8 border-t pt-6">
          <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-semibold">Comments</h3>
            <span class="text-xs text-gray-500" id="commentsCount">0</span>
          </div>

          <div id="commentsList" class="space-y-3 max-h-60 overflow-y-auto pr-1"></div>

          <form id="commentForm" class="mt-4 space-y-2">
            @csrf
            <textarea name="body" class="w-full border rounded-md p-2 text-sm" placeholder="Write a comment..." required></textarea>
            <div class="flex justify-end">
              <button type="submit" class="px-3 py-1.5 text-sm bg-gray-800 text-white rounded-md">Add Comment</button>
            </div>
            <div id="commentErrors" class="text-xs text-red-600 hidden"></div>
          </form>
        </div>
      </div>
    </div>
  </div>

  {{-- Issue Comments --}}
  <div id="issueCommentsModal" class="modal fixed inset-0 bg-black/40 items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-full sm:max-w-3xl p-0 relative">
      <button type="button" class="closeModal absolute top-3 right-3 text-gray-500 hover:text-gray-700" data-target="#issueCommentsModal">✖</button>

      <div class="px-6 pt-5 pb-3 border-b">
        <h2 class="text-lg font-semibold">Issue Comments</h2>
        <div class="text-xs text-gray-500" id="issueCommentsHeader"></div>
        <input type="hidden" id="issue_comments_issue_id">
      </div>

      <div class="p-6">
        <div class="space-y-3 max-h-72 overflow-y-auto pr-1" id="issueCommentsList"></div>

        <form id="issueCommentForm" class="mt-4 space-y-2">
          @csrf
          <textarea name="body" class="w-full border rounded-md p-2 text-sm" placeholder="Write a comment..." required></textarea>
          <div class="flex justify-end">
            <button type="submit" class="px-3 py-1.5 text-sm bg-gray-800 text-white rounded-md">Add Comment</button>
          </div>
          <div id="issueCommentErrors" class="text-xs text-red-600 hidden"></div>
        </form>
      </div>
    </div>
  </div>

  {{-- ===================== Scripts ======================== --}}
  <script>
    // CSRF
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    // Globals
    const currentUserId = @json(Auth::id());
    const TASKS_BASE  = @json(url('/tasks'));
    const ISSUES_BASE = @json(url('/issues'));

    // Helpers
    const openModal  = sel => $(sel).addClass('show');
    const closeModal = sel => $(sel).removeClass('show');
    const clearErrors = $box => $box.addClass('hidden').empty();
    const escapeHtml = s => $('<div>').text(s ?? '').html();
    function showErrors($box, errors){
      $box.empty().removeClass('hidden');
      if (typeof errors === 'string') { $box.append(`<div>${errors}</div>`); return; }
      Object.values(errors || {}).forEach(arr => (arr || []).forEach(msg => $box.append(`<div>${msg}</div>`)));
    }

    // New / Open modals
    $('#openStoryBtn, #openStoryBtnEmpty').on('click', () => openModal('#storyModal'));
    $('#openTaskBtn').on('click', () => openModal('#taskModal'));
    $(document).on('click', '.closeModal', function(){ closeModal($(this).data('target')); });

    // Issue modal (preselect story)
    $(document).on('click', '.open-issue', function(){
      $('#issue_story_id').val($(this).data('story'));
      openModal('#issueModal');
    });

    // Create Story
    $('#storyForm').on('submit', function(e){
      e.preventDefault();
      clearErrors($('#storyErrors'));
      $.post(`{{ route('stories.store') }}`, $(this).serialize())
        .done(() => { closeModal('#storyModal'); location.reload(); })
        .fail(xhr => showErrors($('#storyErrors'), (xhr.responseJSON||{}).errors || 'Failed to create story.'));
    });

    // Create Task
    $('#taskForm').on('submit', function(e){
      e.preventDefault();
      clearErrors($('#taskErrors'));
      $.post(`{{ route('tasks.store') }}`, $(this).serialize())
        .done(() => { closeModal('#taskModal'); location.reload(); })
        .fail(xhr => showErrors($('#taskErrors'), (xhr.responseJSON||{}).errors || 'Failed to create task.'));
    });

    // Create Issue
    $('#issueForm').on('submit', function(e){
      e.preventDefault();
      clearErrors($('#issueErrors'));
      $.post(`{{ route('issues.store') }}`, $(this).serialize())
        .done(() => { closeModal('#issueModal'); location.reload(); })
        .fail(xhr => showErrors($('#issueErrors'), (xhr.responseJSON||{}).errors || 'Failed to create issue.'));
    });

    // Edit Task (fill)
    $(document).on('click', '.open-edit-task', function(){
      const $el = $(this);
      $('#edit_task_id').val($el.data('id'));
      $('#edit_user_story_id').val($el.data('story'));
      $('#edit_title').val($el.data('title'));
      $('#edit_description').val($el.data('description'));
      $('#edit_criteria').val($el.data('criteria'));
      $('#edit_points').val($el.data('points'));
      $('#edit_priority').val($el.data('priority'));
      $('#edit_status').val($el.data('status'));
      $('#edit_user_id').val($el.data('user') || '');
      loadComments($el.data('id'));
      $('#commentForm')[0]?.reset();
      openModal('#editTaskModal');
    });

    // Save Task edits
    $('#editTaskForm').on('submit', function(e){
      e.preventDefault();
      clearErrors($('#editTaskErrors'));
      const id = $('#edit_task_id').val();
      $.ajax({ url: `/tasks/${id}`, method: 'POST', data: $(this).serialize() })
        .done(() => { closeModal('#editTaskModal'); location.reload(); })
        .fail(xhr => showErrors($('#editTaskErrors'), (xhr.responseJSON||{}).errors || 'Failed to update task.'));
    });

    // Drag & Drop + persist
    (function DnD(){
      let draggedEl=null;
      $(document).on('dragstart','.task-card',function(e){
        draggedEl=this; $(this).addClass('dragging');
        e.originalEvent.dataTransfer.effectAllowed='move';
        e.originalEvent.dataTransfer.setData('text/plain', $(this).data('id'));
      });
      $(document).on('dragend','.task-card',function(){ $(this).removeClass('dragging'); draggedEl=null; });
      $(document).on('dragover','.dropzone',function(e){ e.preventDefault(); $(this).addClass('drag-over'); });
      $(document).on('dragleave','.dropzone',function(){ $(this).removeClass('drag-over'); });
      $(document).on('drop','.dropzone',function(e){
        e.preventDefault(); $(this).removeClass('drag-over'); if(!draggedEl) return;
        const $card=$(draggedEl), taskId=$card.data('id'), newStatus=$(this).data('status');
        if ($card.data('status')===newStatus) return;
        const $list=$(this).find('.space-y-3'); $list.append($card); $card.data('status', newStatus);
        $.ajax({ url:`{{ url('/tasks') }}/${taskId}/status`, method:'PATCH', data:{status:newStatus}})
          .fail(()=>location.reload());
      });
    })();

    // ----- Task Comments -----
    function renderComments(list){
      const $list=$('#commentsList').empty(); $('#commentsCount').text(list.length);
      if(!list.length){ $list.append('<p class="text-xs text-gray-400">No comments yet.</p>'); return; }
      list.forEach(c=>{
        const created=new Date(c.created_at).toLocaleString();
        const canDelete=String(c.user_id)===String(currentUserId);
        $list.append(`
          <div class="border rounded-md p-2">
            <div class="flex items-center justify-between">
              <div class="text-xs font-medium text-gray-800">${escapeHtml(c.user?.name||'Unknown')}</div>
              <div class="text-[10px] text-gray-500">${created}</div>
            </div>
            <p class="text-sm text-gray-700 mt-1 whitespace-pre-wrap">${escapeHtml(c.body)}</p>
            ${canDelete?`<div class="flex justify-end mt-1"><button class="text-[11px] text-red-600 hover:underline comment-delete" data-id="${c.id}">Delete</button></div>`:''}
          </div>`);
      });
    }
    function loadComments(taskId){
      if(!taskId) return;
      $('#commentsList').html('<div class="text-xs text-gray-400">Loading...</div>');
      $.get(`${TASKS_BASE}/${taskId}/comments`)
        .done(list=>renderComments(list||[]))
        .fail(()=>$('#commentsList').html('<div class="text-xs text-red-600">Failed to load comments.</div>'));
    }
    $(document).on('submit','#commentForm',function(e){
      e.preventDefault();
      const taskId=$('#edit_task_id').val(); if(!taskId) return alert('Task ID missing. Close and reopen the task.');
      $('#commentErrors').addClass('hidden').empty();
      $.post(`${TASKS_BASE}/${taskId}/comments`, $(this).serialize())
        .done(()=>{ loadComments(taskId); $('#commentForm textarea[name="body"]').val(''); })
        .fail(xhr=>{
          const $box=$('#commentErrors').removeClass('hidden').empty();
          if(xhr.status===401) return $box.text('Please sign in to add a comment.');
          const res=xhr.responseJSON||{}, errs=res.errors||res.message||'Failed to add comment.';
          if(typeof errs==='string') $box.text(errs); else Object.values(errs||{}).forEach(arr=>(arr||[]).forEach(m=>$box.append(`<div>${m}</div>`)));
        });
    });
    $(document).on('click','.comment-delete',function(){
      const id=$(this).data('id'), taskId=$('#edit_task_id').val(); if(!id||!taskId) return;
      $.ajax({url:`${TASKS_BASE}/${taskId}/comments/${id}`, method:'DELETE'})
        .done(()=>loadComments(taskId))
        .fail(()=>alert('Failed to delete comment.'));
    });

    // ----- Issue Comments -----
    $(document).on('click','.open-issue-comments',function(){
      const issueId=$(this).data('issue'), title=$(this).data('title')||'';
      $('#issue_comments_issue_id').val(issueId);
      $('#issueCommentsHeader').text(`#${issueId} — ${title}`);
      $('#issueCommentForm')[0].reset();
      loadIssueComments(issueId);
      openModal('#issueCommentsModal');
    });
    function renderIssueComments(list){
      const $list=$('#issueCommentsList').empty();
      if(!list.length){ $list.append('<p class="text-xs text-gray-400">No comments yet.</p>'); return; }
      list.forEach(c=>{
        const created=new Date(c.created_at).toLocaleString();
        const canDelete=String(c.user_id)===String(currentUserId);
        $list.append(`
          <div class="border rounded-md p-2">
            <div class="flex items-center justify-between">
              <div class="text-xs font-medium text-gray-800">${escapeHtml(c.user?.name||'Unknown')}</div>
              <div class="text-[10px] text-gray-500">${created}</div>
            </div>
            <p class="text-sm text-gray-700 mt-1 whitespace-pre-wrap">${escapeHtml(c.body)}</p>
            ${canDelete?`<div class="flex justify-end mt-1"><button class="text-[11px] text-red-600 hover:underline issue-comment-delete" data-id="${c.id}">Delete</button></div>`:''}
          </div>`);
      });
    }
    function loadIssueComments(issueId){
      $('#issueCommentsList').html('<div class="text-xs text-gray-400">Loading...</div>');
      $.get(`${ISSUES_BASE}/${issueId}/comments`)
        .done(list=>renderIssueComments(list||[]))
        .fail(()=>$('#issueCommentsList').html('<div class="text-xs text-red-600">Failed to load comments.</div>'));
    }
    $(document).on('submit','#issueCommentForm',function(e){
      e.preventDefault();
      const issueId=$('#issue_comments_issue_id').val(); if(!issueId) return;
      $('#issueCommentErrors').addClass('hidden').empty();
      $.post(`${ISSUES_BASE}/${issueId}/comments`, $(this).serialize())
        .done(()=>{ loadIssueComments(issueId); $('#issueCommentForm textarea[name="body"]').val(''); })
        .fail(xhr=>{
          const $box=$('#issueCommentErrors').removeClass('hidden').empty();
          if(xhr.status===401) return $box.text('Please sign in to add a comment.');
          const res=xhr.responseJSON||{}, errs=res.errors||res.message||'Failed to add comment.';
          if(typeof errs==='string') $box.text(errs); else Object.values(errs||{}).forEach(arr=>(arr||[]).forEach(m=>$box.append(`<div>${m}</div>`)));
        });
    });
    $(document).on('click','.issue-comment-delete',function(){
      const id=$(this).data('id'), issueId=$('#issue_comments_issue_id').val(); if(!id||!issueId) return;
      $.ajax({url:`${ISSUES_BASE}/${issueId}/comments/${id}`, method:'DELETE'})
        .done(()=>loadIssueComments(issueId))
        .fail(()=>alert('Failed to delete comment.'));
    });

    // ===== Tags dropdown (pure jQuery) =====
    (function TagsDropdown(){
      const $btn   = $('#tagsDropdownBtn');
      const $panel = $('#tagsDropdownPanel');
      const $label = $('#tagsDropdownLabel');
      const $chev  = $('#tagsDropdownChevron');

      function updateLabel(){
        const count = $panel.find('input.tags-multi:checked').length;
        $label.text(count ? `${count} selected` : 'Select tags');
      }
      function openPanel(){
        $panel.removeClass('hidden');
        $chev.addClass('rotate-180');
        $(document).on('mousedown.tagsOutside', (e)=>{
          if(!$panel.is(e.target) && $panel.has(e.target).length===0 && !$btn.is(e.target) && $btn.has(e.target).length===0){
            closePanel();
          }
        });
        $(document).on('keydown.tagsEsc', (e)=>{ if(e.key==='Escape') closePanel(); });
      }
      function closePanel(){
        $panel.addClass('hidden');
        $chev.removeClass('rotate-180');
        $(document).off('mousedown.tagsOutside keydown.tagsEsc');
      }
      $btn.on('click', function(){ $panel.hasClass('hidden') ? openPanel() : closePanel(); });
      $('#tagsDropdownClose').on('click', closePanel);
      $('#tagsDropdownClear').on('click', ()=>{ $panel.find('input.tags-multi:checked').prop('checked', false); updateLabel(); });

      $panel.on('change','input.tags-multi', updateLabel);
      updateLabel();
    })();
  </script>

</body>
</html>
