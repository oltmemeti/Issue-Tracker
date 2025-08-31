<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Issue Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs" defer></script>
    <style>
        [x-cloak] {
            display: none !important
        }
        
        .task-card {
            transition: all 0.2s ease;
        }
        
        .task-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body x-data="{ 
    openTaskModal: false, 
    openStoryModal: false, 
    openEditModal: false, 
    selectedTask: null,
    editTask(task) {
        this.selectedTask = {
            id: task.id,
            user_story_id: task.user_story_id,
            title: task.title,
            description: task.description,
            acceptance_criteria: task.acceptance_criteria,
            story_points: task.story_points,
            priority: task.priority,
            status: task.status
        };
        this.openEditModal = true;
    }
}">

    {{-- ================= Create Task Modal ================= --}}
    <div x-cloak x-show="openTaskModal" x-transition
        class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
            <button @click="openTaskModal = false" class="absolute top-3 right-3 text-gray-500 hover:text-gray-700">✖</button>
            <h2 class="text-xl font-semibold mb-4">+ Create New Task</h2>

            <form action="{{ route('tasks.store') }}" method="POST">
                @csrf

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
                    <option>1</option>
                    <option>2</option>
                    <option>3</option>
                    <option>5</option>
                    <option>8</option>
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
                    <button type="button" @click="openTaskModal = false" class="px-4 py-2 bg-gray-200 rounded-md">Cancel</button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md {{ $stories->isEmpty() ? 'opacity-50 cursor-not-allowed' : '' }}"
                        {{ $stories->isEmpty() ? 'disabled title=Create_a_User_Story_first' : '' }}>
                        Create Task
                    </button>
                </div>
            </form>
        </div>
    </div>
{{-- ================= Create Story Modal ================= --}}
<div x-cloak x-show="openStoryModal" x-transition class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
        <button @click="openStoryModal=false" class="absolute top-3 right-3 text-gray-500 hover:text-gray-700">✖</button>
        <h2 class="text-lg font-semibold mb-4">Create Story</h2>

        <form method="POST" action="{{ route('stories.store') }}" class="space-y-4">
            @csrf

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
                <button type="button" @click="openStoryModal=false" class="px-4 py-2 bg-gray-200 rounded-md">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md">Create</button>
            </div>
        </form>
    </div>
</div>

    {{-- ================= Edit Task Modal ================= --}}
    <div x-cloak x-show="openEditModal" x-transition
        class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
            <button @click="openEditModal = false" class="absolute top-3 right-3 text-gray-500 hover:text-gray-700">✖</button>
            <h2 class="text-xl font-semibold mb-4">Edit Task</h2>

            <template x-if="selectedTask">
                <form x-bind:action="'{{ url('/tasks') }}/' + selectedTask.id" method="POST">
                    @csrf
                    @method('PUT')

                    <label class="block text-sm font-medium text-gray-700">User Story</label>
                    <select name="user_story_id" x-model="selectedTask.user_story_id" class="w-full border rounded-md p-2 mb-4">
                        @foreach($stories as $story)
                        <option value="{{ $story->id }}">{{ $story->title }}</option>
                        @endforeach
                    </select>

                    <label class="block text-sm font-medium text-gray-700">Title</label>
                    <input type="text" name="title" x-model="selectedTask.title" class="w-full border rounded-md p-2 mb-4" required>

                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" x-model="selectedTask.description" class="w-full border rounded-md p-2 mb-4"></textarea>

                    <label class="block text-sm font-medium text-gray-700">Acceptance Criteria</label>
                    <textarea name="acceptance_criteria" x-model="selectedTask.acceptance_criteria" class="w-full border rounded-md p-2 mb-4"></textarea>

                    <label class="block text-sm font-medium text-gray-700">Story Points</label>
                    <select name="story_points" x-model="selectedTask.story_points" class="w-full border rounded-md p-2 mb-4">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="5">5</option>
                        <option value="8">8</option>
                    </select>

                    <label class="block text-sm font-medium text-gray-700">Priority</label>
                    <select name="priority" x-model="selectedTask.priority" class="w-full border rounded-md p-2 mb-4">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
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
                    <select name="status" x-model="selectedTask.status" class="w-full border rounded-md p-2 mb-4">
                        <option value="new">New</option>
                        <option value="in_progress">In Progress</option>
                        <option value="blocked">Blocked</option>
                        <option value="ready_for_qa">Ready for QA</option>
                        <option value="done">Done</option>
                    </select>

                    <div class="flex justify-end space-x-2">
                        <button type="button" @click="openEditModal=false" class="px-4 py-2 bg-gray-200 rounded-md">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">Save Changes</button>
                    </div>
                </form>
            </template>
        </div>
    </div>

    {{-- ===================== Header ======================== --}}
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto flex justify-between items-center p-4">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Issue Tracker</h1>
                <p class="text-sm text-gray-500">Professional task management</p>
            </div>
            <div class="flex gap-2">
                <button @click="openStoryModal=true" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">+ Story</button>
                <button @click="openTaskModal=true" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">+ Task</button>
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
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold">{{ $story->title }}</h3>
                    <p class="text-sm text-gray-500">{{ $story->description }}</p>
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
            </div>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                @foreach($columnLabels as $key => $label)
                @php($list = $key === 'ready_for_qa'
                ? $story->tasks->whereIn('status', ['ready_for_qa'])
                : $story->tasks->where('status', $key))

                <div class="bg-gray-50 border rounded-lg p-3">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold">{{ $label }}</h4>
                        <span class="text-xs text-gray-500">{{ $list->count() }}</span>
                    </div>

<div class="space-y-3">
    @forelse($list as $task)
        <div class="p-3 bg-white border rounded cursor-pointer task-card"
            @click="editTask(@js($task))">
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
                <span class="text-xs text-gray-400">
                    #{{ $task->id }}
                </span>
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
            <button @click="openStoryModal=true" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                + Create Your First Story
            </button>
        </div>
        @endif
    </div>

</body>

</html>