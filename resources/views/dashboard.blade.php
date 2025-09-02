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
        .task-card { transition: all .2s ease }
        .task-card:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0,0,0,.1) }
        .modal { display:none }
        .modal.show { display:flex }
    </style>
</head>
@php use Illuminate\Support\Str; @endphp
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
                <button id="openTaskBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">+ Task</button>
            </div>
        </div>
    </header>

    {{-- ===================== Banner ======================== --}}
    <div class="bg-gradient-to-r from-blue-500 to-blue-700 text-center text-white py-6">
        <h2 class="text-2xl font-semibold">Professional Task Management</h2>
    </div>

    {{-- ============== Stories + 5 Columns Each ============= --}}
    <div class="max-w-7xl mx-auto py-8 space-y-6">
        @if($stories->count())
            @foreach($stories as $story)
                <div class="bg-white shadow rounded-lg p-4">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold">{{ $story->title }}</h3>
                            <p class="text-sm text-gray-600">{{ $story->description }}</p>

                            @if($story->user)
                                <p class="text-xs text-gray-400 mt-1">Assigned to: {{ $story->user->name }}</p>
                            @else
                                <p class="text-xs text-gray-400 mt-1">Unassigned</p>
                            @endif

                            @if($story->deadline)
                                <p class="text-xs text-red-500 mt-1">Deadline: {{ \Carbon\Carbon::parse($story->deadline)->format('M d, Y') }}</p>
                            @else
                                <p class="text-xs text-red-500 mt-1">no deadline</p>
                            @endif
                        </div>

                        <!-- Per-story +Issue -->
                        <button
                            class="open-issue bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded-md text-sm"
                            data-story="{{ $story->id }}">
                            + Issue
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        @foreach($columnLabels as $key => $label)
                            @php($list = $key === 'ready_for_qa'
                                ? $story->tasks->whereIn('status', ['ready_for_qa'])
                                : $story->tasks->where('status', $key)
                            )

                            <div class="bg-gray-50 border rounded-lg p-3">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="font-semibold">{{ $label }}</h4>
                                    <span class="text-xs text-gray-500">{{ $list->count() }}</span>
                                </div>

                                <div class="space-y-3">
                                    @forelse($list as $task)
                                        <div
                                            class="p-3 bg-white border rounded cursor-pointer task-card open-edit-task"
                                            data-id="{{ $task->id }}"
                                            data-story="{{ $task->user_story_id }}"
                                            data-title="{{ e($task->title) }}"
                                            data-description="{{ e($task->description) }}"
                                            data-criteria="{{ e($task->acceptance_criteria) }}"
                                            data-points="{{ $task->story_points }}"
                                            data-priority="{{ $task->priority }}"
                                            data-status="{{ $task->status }}"
                                            data-user="{{ $task->user_id ?? '' }}"
                                        >
                                            <div class="flex justify-between">
                                                <span class="font-medium text-sm">{{ $task->title }}</span>
                                                <span class="text-xs {{ $task->priority === 'high' ? 'text-red-600' : ($task->priority === 'medium' ? 'text-yellow-600' : 'text-green-600') }}">
                                                    {{ ucfirst($task->priority) }}
                                                </span>
                                            </div>

                                            @if($task->user)
                                                <p class="text-xs text-gray-400 mt-1">Assigned to: {{ $task->user->name }}</p>
                                            @endif

                                            <p class="text-xs text-gray-500 mt-1">{{ Str::limit($task->description, 50) }}</p>

                                            <div class="flex justify-between items-center mt-2">
                                                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                                    {{ $task->story_points }} SP
                                                </span>
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

        {{-- ================= Issues Board (Global) ================= --}}
        <div class="bg-white shadow rounded-lg p-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Issues</h3>
            </div>

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
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="font-medium text-sm">{{ $issue->title }}</div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                Type: <span class="uppercase">{{ $issue->type }}</span> ·
                                                Priority:
                                                <span class="{{ $issue->priority === 'high' ? 'text-red-600' : ($issue->priority === 'medium' ? 'text-yellow-700' : 'text-green-700') }}">
                                                    {{ ucfirst($issue->priority) }}
                                                </span>
                                            </div>

                                            @if($issue->user)
                                                <div class="text-xs text-gray-400 mt-1">Assigned to: {{ $issue->user->name }}</div>
                                            @else
                                                <div class="text-xs text-gray-400 mt-1">Unassigned</div>
                                            @endif

                                            @if($issue->story)
                                                <div class="text-xs text-indigo-700 mt-1">
                                                    User Story: #{{ $issue->story->id }} — {{ Str::limit($issue->story->title, 60) }}
                                                </div>
                                            @else
                                                <div class="text-xs text-gray-400 mt-1">No User Story</div>
                                            @endif

                                            @if($issue->task)
                                                <div class="text-xs text-blue-700 mt-1">
                                                    Linked Task: #{{ $issue->task->id }} — {{ Str::limit($issue->task->title, 40) }}
                                                </div>
                                            @endif
                                        </div>

                                        <span class="text-[10px] px-2 py-1 rounded bg-gray-100 text-gray-700">
                                            #{{ $issue->id }}
                                        </span>
                                    </div>

                                    @if($issue->description)
                                        <p class="text-xs text-gray-600 mt-2">{{ Str::limit($issue->description, 120) }}</p>
                                    @endif
                                </div>
                            @empty
                                <p class="text-xs text-gray-400">No issues.</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ===================== Modals (AJAX) ======================== --}}

    {{-- Create Story Modal --}}
    <div id="storyModal" class="modal fixed inset-0 bg-black/40 items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
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

                <div class="flex justify-end space-x-2">
                    <button type="button" class="closeModal px-4 py-2 bg-gray-200 rounded-md" data-target="#storyModal">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md">Create</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Create Task Modal --}}
    <div id="taskModal" class="modal fixed inset-0 bg-black/40 items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
            <button type="button" class="closeModal absolute top-3 right-3 text-gray-500 hover:text-gray-700" data-target="#taskModal">✖</button>
            <h2 class="text-xl font-semibold mb-4">+ Create New Task</h2>

            <form id="taskForm">
                @csrf
                <div id="taskErrors" class="text-sm text-red-600 space-y-1 hidden"></div>

                <label class="block text-sm font-medium text-gray-700">User Story</label>
                <select name="user_story_id" class="w-full border rounded-md p-2 mb-4" {{ $stories->isEmpty() ? 'disabled' : '' }} required>
                    @forelse($stories as $story)
                        <option value="{{ $story->id }}">{{ $story->title }}</option>
                    @empty
                        <option value="">No stories yet</option>
                    @endforelse
                </select>

                <label class="block text-sm font-medium text-gray-700">Task Title</label>
                <input type="text" name="title" class="w-full border rounded-md p-2 mb-4 focus:ring-2 focus:ring-blue-500" required>

                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" class="w-full border rounded-md p-2 mb-4 focus:ring-2 focus:ring-blue-500"></textarea>

                <label class="block text-sm font-medium text-gray-700">Acceptance Criteria</label>
                <textarea name="acceptance_criteria" class="w-full border rounded-md p-2 mb-4 focus:ring-2 focus:ring-blue-500"></textarea>

                <label class="block text-sm font-medium text-gray-700">Story Points</label>
                <select name="story_points" class="w-full border rounded-md p-2 mb-4">
                    <option>1</option><option>2</option><option>3</option><option>5</option><option>8</option>
                </select>

                <label class="block text-sm font-medium text-gray-700">Priority</label>
                <select name="priority" class="w-full border rounded-md p-2 mb-4">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
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

                <div class="flex justify-end space-x-2">
                    <button type="button" class="closeModal px-4 py-2 bg-gray-200 rounded-md" data-target="#taskModal">Cancel</button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md {{ $stories->isEmpty() ? 'opacity-50 cursor-not-allowed' : '' }}"
                        @if($stories->isEmpty()) disabled title="Create a User Story first" @endif>
                        Create Task
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Create Issue Modal --}}
    <div id="issueModal" class="modal fixed inset-0 bg-black/40 items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
            <button type="button" class="closeModal absolute top-3 right-3 text-gray-500 hover:text-gray-700" data-target="#issueModal">✖</button>
            <h2 class="text-xl font-semibold mb-4">+ Create Issue</h2>

            <form id="issueForm" class="space-y-4">
                @csrf
                <div id="issueErrors" class="text-sm text-red-600 space-y-1 hidden"></div>

                {{-- Assign to User Story --}}
                <div>
                    <label class="block text-sm font-medium">Assign to User Story</label>
                    <select name="user_story_id" id="issue_story_id" class="w-full border rounded-md p-2" required>
                        <option value="">— Select User Story —</option>
                        @foreach($stories as $s)
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
                        <label class="block text-sm font-medium">Link to Task (optional)</label>
                        <select name="task_id" id="issue_task_id" class="w-full border rounded-md p-2">
                            <option value="">— None —</option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1" id="issueNoStoryMsg">Select a User Story to load its tasks.</p>
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

    {{-- Edit Task Modal --}}
    <div id="editTaskModal" class="modal fixed inset-0 bg-black/40 items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
            <button type="button" class="closeModal absolute top-3 right-3 text-gray-500 hover:text-gray-700" data-target="#editTaskModal">✖</button>
            <h2 class="text-xl font-semibold mb-4">Edit Task</h2>

            <form id="editTaskForm" method="POST">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" id="edit_task_id">

                <div id="editTaskErrors" class="text-sm text-red-600 space-y-1 hidden"></div>

                <label class="block text-sm font-medium text-gray-700">User Story</label>
                <select name="user_story_id" id="edit_user_story_id" class="w-full border rounded-md p-2 mb-4">
                    @foreach($stories as $story)
                        <option value="{{ $story->id }}">{{ $story->title }}</option>
                    @endforeach
                </select>

                <label class="block text-sm font-medium text-gray-700">Title</label>
                <input type="text" name="title" id="edit_title" class="w-full border rounded-md p-2 mb-4" required>

                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" id="edit_description" class="w-full border rounded-md p-2 mb-4"></textarea>

                <label class="block text-sm font-medium text-gray-700">Acceptance Criteria</label>
                <textarea name="acceptance_criteria" id="edit_criteria" class="w-full border rounded-md p-2 mb-4"></textarea>

                <label class="block text-sm font-medium text-gray-700">Story Points</label>
                <select name="story_points" id="edit_points" class="w-full border rounded-md p-2 mb-4">
                    <option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="5">5</option><option value="8">8</option>
                </select>

                <label class="block text-sm font-medium text-gray-700">Priority</label>
                <select name="priority" id="edit_priority" class="w-full border rounded-md p-2 mb-4">
                    <option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option>
                </select>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Assign To</label>
                    <select name="user_id" id="edit_user_id" class="w-full border rounded-md p-2">
                        <option value="">-- Select User --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <label class="block text-sm font-medium text-gray-700">Status</label>
                <select name="status" id="edit_status" class="w-full border rounded-md p-2 mb-4">
                    <option value="new">New</option>
                    <option value="in_progress">In Progress</option>
                    <option value="blocked">Blocked</option>
                    <option value="ready_for_qa">Ready for QA</option>
                    <option value="done">Done</option>
                </select>

                <div class="flex justify-end space-x-2">
                    <button type="button" class="closeModal px-4 py-2 bg-gray-200 rounded-md" data-target="#editTaskModal">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== Scripts ======================== --}}
    <script>
        // CSRF for AJAX
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

        const openModal  = (sel) => $(sel).addClass('show');
        const closeModal = (sel) => $(sel).removeClass('show');
        const showErrors = ($box, errors) => {
            $box.empty().removeClass('hidden');
            if (typeof errors === 'string') { $box.append(`<div>${errors}</div>`); return; }
            Object.values(errors).forEach(arr => (arr || []).forEach(msg => $box.append(`<div>${msg}</div>`)));
        };
        const clearErrors = ($box) => { $box.addClass('hidden').empty(); }

        // Open/Close modal buttons
        $('#openStoryBtn, #openStoryBtnEmpty').on('click', () => openModal('#storyModal'));
        $('#openTaskBtn').on('click', () => openModal('#taskModal'));
        $(document).on('click', '.closeModal', function() { closeModal($(this).data('target')); });

        // Open Issue modal from a story button: preselect story + load tasks
        $('.open-issue').on('click', function() {
            const storyId = $(this).data('story');
            $('#issue_story_id').val(storyId);

            $('#issue_task_id').empty().append('<option value="">— None —</option>');
            $('#issueNoStoryMsg').toggle(!storyId);

            if (storyId) {
                $.get(`/stories/${storyId}/tasks`, function(tasks) {
                    (tasks || []).forEach(t => {
                        $('#issue_task_id').append(`<option value="${t.id}">${$('<div>').text(t.title).html()}</option>`);
                    });
                    openModal('#issueModal');
                }).fail(() => openModal('#issueModal'));
            } else {
                openModal('#issueModal');
            }
        });

        // When user picks a story manually in Issue modal, (re)load tasks
        $('#issue_story_id').on('change', function() {
            const storyId = $(this).val();
            $('#issue_task_id').empty().append('<option value="">— None —</option>');
            $('#issueNoStoryMsg').toggle(!storyId);

            if (!storyId) return;

            $.get(`/stories/${storyId}/tasks`, function(tasks) {
                (tasks || []).forEach(t => {
                    $('#issue_task_id').append(`<option value="${t.id}">${$('<div>').text(t.title).html()}</option>`);
                });
            });
        });

        // Submit Story (AJAX)
        $('#storyForm').on('submit', function(e) {
            e.preventDefault();
            clearErrors($('#storyErrors'));

            $.post(`{{ route('stories.store') }}`, $(this).serialize())
                .done(() => { closeModal('#storyModal'); location.reload(); })
                .fail(xhr => {
                    const res = xhr.responseJSON || {};
                    showErrors($('#storyErrors'), res.errors || res.message || 'Failed to create story.');
                });
        });

        // Submit Task (AJAX)
        $('#taskForm').on('submit', function(e) {
            e.preventDefault();
            clearErrors($('#taskErrors'));

            $.post(`{{ route('tasks.store') }}`, $(this).serialize())
                .done(() => { closeModal('#taskModal'); location.reload(); })
                .fail(xhr => {
                    const res = xhr.responseJSON || {};
                    showErrors($('#taskErrors'), res.errors || res.message || 'Failed to create task.');
                });
        });

        // Submit Issue (AJAX)
        $('#issueForm').on('submit', function(e) {
            e.preventDefault();
            clearErrors($('#issueErrors'));

            $.post(`{{ route('issues.store') }}`, $(this).serialize())
                .done(() => { closeModal('#issueModal'); location.reload(); })
                .fail(xhr => {
                    const res = xhr.responseJSON || {};
                    showErrors($('#issueErrors'), res.errors || res.message || 'Failed to create issue.');
                });
        });

        // Open Edit Task with data attributes
        $(document).on('click', '.open-edit-task', function() {
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
            openModal('#editTaskModal');
        });

        // Submit Edit Task (AJAX PUT)
        $('#editTaskForm').on('submit', function(e) {
            e.preventDefault();
            clearErrors($('#editTaskErrors'));

            const id = $('#edit_task_id').val();
            const payload = $(this).serialize();

            $.ajax({
                url: `/tasks/${id}`,
                method: 'POST', // using POST with _method=PUT
                data: payload
            })
            .done(() => { closeModal('#editTaskModal'); location.reload(); })
            .fail(xhr => {
                const res = xhr.responseJSON || {};
                showErrors($('#editTaskErrors'), res.errors || res.message || 'Failed to update task.');
            });
        });
    </script>

    {{-- === Helper route to add in routes/web.php (authenticated) ===
    Route::get('/stories/{story}/tasks', function (\App\Models\UserStory $story) {
        return $story->tasks()->select('id','title')->orderBy('title')->get();
    })->middleware('auth');
    --}}
</body>
</html>
