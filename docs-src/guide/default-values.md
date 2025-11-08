# Setting Default Values

Learn how to set default/pre-selected values in various scenarios.

::: tip Version 1.1.0 Update
In version 1.1.0, default values work seamlessly with `wire:model`. You no longer need to pass the `:value` attribute separately when using `wire:model` - the component automatically uses the property value from your Livewire component.
:::

## Single Selection

### Method 1: Set Livewire Property (Recommended)

The simplest way - set the property value in your Livewire component. The component automatically detects and uses the `wire:model` property value:

**Livewire Component:**

```php
<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;

class UserForm extends Component
{
    public $userId = 5;  // Default value - automatically used by wire:model
    
    public function mount($userId = null)
    {
        // Set default from route parameter or use default
        $this->userId = $userId ?? 5;
    }
    
    public function render()
    {
        $users = User::all()->map(fn($user) => [
            'value' => $user->id,
            'label' => $user->name,
        ]);
        
        return view('livewire.user-form', ['users' => $users]);
    }
}
```

**Blade View:**

```html
<!-- No :value attribute needed - wire:model automatically uses $userId -->
<livewire:async-select
    wire:model="userId"
    :options="$users"
    placeholder="Select user..."
/>
```

### Method 2: Pass Value Attribute (Legacy)

You can still pass the default value directly to the component, though it's not necessary when using `wire:model`:

```html
<livewire:async-select
    wire:model="userId"
    :options="$users"
    :value="5"
    placeholder="Select user..."
/>
```

::: tip
When using `wire:model`, the `:value` attribute is optional. The component will automatically use the Livewire property value from `wire:model`.
:::

### Method 3: Edit Forms with Existing Data

When editing existing records:

```php
<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;

class EditProject extends Component
{
    public $projectId;
    public $categoryId;
    public $ownerId;
    
    public function mount($projectId)
    {
        $project = Project::findOrFail($projectId);
        
        // Set defaults from existing project
        $this->projectId = $project->id;
        $this->categoryId = $project->category_id;
        $this->ownerId = $project->owner_id;
    }
    
    public function render()
    {
        return view('livewire.edit-project');
    }
}
```

```html
<form wire:submit="save">
    <div>
        <label>Category</label>
        <livewire:async-select
            wire:model="categoryId"
            endpoint="/api/categories"
        />
    </div>
    
    <div>
        <label>Owner</label>
        <livewire:async-select
            wire:model="ownerId"
            endpoint="/api/users/search"
        />
    </div>
    
    <button type="submit">Update Project</button>
</form>
```

## Multiple Selection

### Method 1: Array of Values

Set an array of IDs as the default:

**Livewire Component:**

```php
<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;

class TeamForm extends Component
{
    public $teamMembers = [1, 5, 7];  // Default selected members
    
    public function mount($teamId = null)
    {
        if ($teamId) {
            // Load existing team members
            $team = Team::with('members')->find($teamId);
            $this->teamMembers = $team->members->pluck('id')->toArray();
        }
    }
    
    public function render()
    {
        return view('livewire.team-form');
    }
}
```

**Blade View:**

```html
<livewire:async-select
    wire:model="teamMembers"
    endpoint="/api/users/search"
    :multiple="true"
    placeholder="Select team members..."
/>
```

### Method 2: Pass Value Array

```html
<livewire:async-select
    wire:model="tags"
    :multiple="true"
    :options="$availableTags"
    :value="[1, 3, 5]"
    placeholder="Select tags..."
/>
```

## With Async Endpoints

### Using selected-endpoint

When using async loading, the component needs to fetch labels for pre-selected values:

**Livewire Component:**

```php
<?php

namespace App\Livewire;

use Livewire\Component;

class ProjectForm extends Component
{
    public $categoryId = 3;  // Pre-selected category
    public $teamMembers = [1, 5, 8];  // Pre-selected team members
    
    public function render()
    {
        return view('livewire.project-form');
    }
}
```

**Blade View:**

```html
<form wire:submit="save">
    {{-- Single selection with async endpoint --}}
    <div>
        <label>Category</label>
        <livewire:async-select
            wire:model="categoryId"
            endpoint="/api/categories"
            selected-endpoint="/api/categories/selected"
        />
    </div>
    
    {{-- Multiple selection with async endpoint --}}
    <div>
        <label>Team Members</label>
        <livewire:async-select
            wire:model="teamMembers"
            endpoint="/api/users/search"
            selected-endpoint="/api/users/selected"
            :multiple="true"
        />
    </div>
</form>
```

**API Endpoints:**

```php
// For single selection
Route::get('/api/categories/selected', function (Request $request) {
    $selected = $request->get('selected');
    
    $categories = Category::whereIn('id', (array) $selected)
        ->get()
        ->map(fn($cat) => [
            'value' => $cat->id,
            'label' => $cat->name,
        ]);
    
    return response()->json(['data' => $categories]);
});

// For multiple selection
Route::get('/api/users/selected', function (Request $request) {
    $selected = $request->get('selected');
    
    // Split comma-separated IDs
    $ids = is_string($selected) ? explode(',', $selected) : $selected;
    
    $users = User::whereIn('id', $ids)
        ->get()
        ->map(fn($user) => [
            'value' => $user->id,
            'label' => $user->name,
            'image' => $user->avatar_url,
        ]);
    
    return response()->json(['data' => $users]);
});
```

::: tip
The `selected-endpoint` is called automatically when the component mounts with a pre-selected value. This fetches the labels for display.
:::

### Using value-labels (No API Calls Required)

::: tip Version 1.1.0 Feature
In version 1.1.0, you can use `value-labels` to display selected labels **without making any API requests**. This is perfect when you already know the labels and want to avoid network calls entirely.
:::

Instead of using a `selected-endpoint` to fetch labels, you can provide labels directly using the `value-labels` attribute. When `value-labels` is provided, the component will:

1. **Display labels immediately** - No API call is made
2. **Work with pre-selected values** - Labels show up right away when the component mounts
3. **Support dynamic updates** - Labels update when values change programmatically

**Use Case: Programmatically Setting Selected Values**

For example, when you have a suffix button that adds users to the selection:

**Livewire Component:**

```php
<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class UserSelector extends Component
{
    public $selectedUsers = [];
    
    #[On('addRecommendedUsers')]
    public function addRecommendedUsers()
    {
        // Set selected user IDs
        $this->selectedUsers = [
            'john_doe',
            'jane_smith',
            'bob_wilson'
        ];
    }
    
    public function render()
    {
        return view('livewire.user-selector');
    }
}
```

**Blade View:**

```html
<livewire:async-select
    wire:model="selectedUsers"
    :multiple="true"
    name="users"
    endpoint="{{ route('api.users.search') }}"
    :value-labels="[
        'john_doe' => 'John Doe',
        'jane_smith' => 'Jane Smith',
        'bob_wilson' => 'Bob Wilson'
    ]"
    :min-search-length="3"
    value-field="id"
    label-field="name"
    :per-page="20"
    :autoload="false"
    placeholder="Type at least 3 characters to search users..."
    :suffix-button="true"
    suffix-button-action="addRecommendedUsers"
/>
```

When `addRecommendedUsers()` is called and sets the `selectedUsers` array, the component will automatically display the labels "John Doe", "Jane Smith", and "Bob Wilson" **without making any API request**.

**Using value-labels with Images:**

You can also provide images along with labels:

```html
<livewire:async-select
    wire:model="selectedUsers"
    :multiple="true"
    name="users"
    endpoint="{{ route('api.users.search') }}"
    :value-labels="[
        'john_doe' => [
            'label' => 'John Doe',
            'image' => 'https://example.com/avatars/john.jpg'
        ],
        'jane_smith' => 'Jane Smith',
        'bob_wilson' => [
            'label' => 'Bob Wilson',
            'image' => 'https://example.com/avatars/bob.jpg'
        ]
    ]"
    image-field="avatar"
    ...
/>
```

**Simple Format (Labels Only):**

```html
:value-labels="[
    'user_1' => 'John Doe',
    'user_2' => 'Jane Smith',
    'user_3' => 'Bob Wilson'
]"
```

**Extended Format (Labels with Images):**

```html
:value-labels="[
    'user_1' => [
        'label' => 'John Doe',
        'image' => '/avatars/john.jpg'
    ],
    'user_2' => [
        'label' => 'Jane Smith',
        'image' => '/avatars/jane.jpg'
    ]
]"
```

::: tip When to Use value-labels vs selected-endpoint
- **Use `value-labels`** when:
  - You already know the labels (e.g., from previous API calls)
  - You're programmatically setting values and want to avoid extra API calls
  - The labels are static or known at render time
  - **You want zero API requests for displaying selected labels** (v1.1.0)
  - Performance is critical and you want to reduce network traffic
  
- **Use `selected-endpoint`** when:
  - Labels need to be fetched from the server
  - Labels might change and need to be up-to-date
  - You want to keep the data source centralized
  - Labels are not available at render time
:::

### Pre-selected Values with value-labels

When you have pre-selected values and provide `value-labels`, the component will display the labels immediately on mount without any API calls:

**Livewire Component:**

```php
<?php

namespace App\Livewire;

use Livewire\Component;

class EditProject extends Component
{
    public $categoryId = 3;  // Pre-selected
    
    public function mount($projectId)
    {
        $project = Project::find($projectId);
        $this->categoryId = $project->category_id;  // Pre-selected from existing data
    }
    
    public function render()
    {
        return view('livewire.edit-project');
    }
}
```

**Blade View:**

```html
<livewire:async-select
    wire:model="categoryId"
    endpoint="/api/categories"
    :value-labels="[
        3 => 'Web Development',  // Label for pre-selected value
    ]"
    placeholder="Select category..."
/>
```

The label "Web Development" will be displayed immediately when the component mounts, **without making any API request** to fetch it.

## Dynamic Defaults

### From Route Parameters

```php
<?php

namespace App\Livewire;

use Livewire\Component;

class FilteredList extends Component
{
    public $categoryId;
    public $status;
    
    public function mount($categoryId = null, $status = 'active')
    {
        $this->categoryId = $categoryId;
        $this->status = $status;
    }
    
    public function render()
    {
        return view('livewire.filtered-list');
    }
}
```

**Route:**

```php
Route::get('/projects/{categoryId?}', FilteredList::class);
```

**URL:** `/projects/5` will set `categoryId` to 5 by default.

### From User Preferences

```php
<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public $selectedTeam;
    public $selectedProject;
    
    public function mount()
    {
        $user = Auth::user();
        
        // Set defaults from user preferences
        $this->selectedTeam = $user->default_team_id;
        $this->selectedProject = $user->last_viewed_project_id;
    }
    
    public function render()
    {
        return view('livewire.dashboard');
    }
}
```

### From Session/Cache

```php
<?php

namespace App\Livewire;

use Livewire\Component;

class SearchForm extends Component
{
    public $categoryId;
    public $tags = [];
    
    public function mount()
    {
        // Restore from session
        $this->categoryId = session('last_category_id');
        $this->tags = session('last_selected_tags', []);
    }
    
    public function updatedCategoryId($value)
    {
        // Save to session for next time
        session(['last_category_id' => $value]);
    }
    
    public function render()
    {
        return view('livewire.search-form');
    }
}
```

## Computed Properties

Set defaults based on logic:

```php
<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AssignTask extends Component
{
    public $assignedTo;
    
    public function mount($taskId = null, $assignedTo = null)
    {
        // Priority: 1. Provided value, 2. Current user, 3. Team lead
        $this->assignedTo = $assignedTo 
            ?? Auth::id() 
            ?? Auth::user()->team->lead_id;
    }
    
    public function render()
    {
        return view('livewire.assign-task');
    }
}
```

## Conditional Defaults

Set different defaults based on conditions:

```php
<?php

namespace App\Livewire;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CreateProject extends Component
{
    public $ownerId;
    public $teamMembers = [];
    public $priority;
    
    public function mount()
    {
        $user = Auth::user();
        
        // Set owner to current user
        $this->ownerId = $user->id;
        
        // Auto-select user's team
        $this->teamMembers = $user->team->members->pluck('id')->toArray();
        
        // Priority based on user role
        $this->priority = $user->isAdmin() ? 'high' : 'medium';
    }
    
    public function render()
    {
        return view('livewire.create-project');
    }
}
```

## Resetting to Default

Reset the selection back to default value:

```php
<?php

namespace App\Livewire;

use Livewire\Component;

class SearchForm extends Component
{
    public $categoryId = 1;  // Default category
    public $tags = [];
    
    public function resetFilters()
    {
        $this->categoryId = 1;  // Reset to default
        $this->tags = [];
    }
    
    public function render()
    {
        return view('livewire.search-form');
    }
}
```

```html
<div>
    <livewire:async-select
        wire:model="categoryId"
        :options="$categories"
    />
    
    <button wire:click="resetFilters">Reset to Defaults</button>
</div>
```

## Complete Example: Edit Form

Full example with all default value scenarios:

**Livewire Component:**

```php
<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\Category;
use App\Models\User;
use Livewire\Component;

class EditProject extends Component
{
    public $projectId;
    public $name;
    public $description;
    public $categoryId;
    public $ownerId;
    public $teamMembers = [];
    public $tags = [];
    public $status;
    
    public function mount($projectId)
    {
        $project = Project::with(['category', 'owner', 'members', 'tags'])
            ->findOrFail($projectId);
        
        // Set all defaults from existing project
        $this->projectId = $project->id;
        $this->name = $project->name;
        $this->description = $project->description;
        $this->categoryId = $project->category_id;
        $this->ownerId = $project->owner_id;
        $this->teamMembers = $project->members->pluck('id')->toArray();
        $this->tags = $project->tags->pluck('id')->toArray();
        $this->status = $project->status;
    }
    
    public function save()
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'required',
            'categoryId' => 'required|exists:categories,id',
            'ownerId' => 'required|exists:users,id',
            'teamMembers' => 'required|array|min:1',
            'tags' => 'nullable|array',
            'status' => 'required',
        ]);
        
        $project = Project::find($this->projectId);
        $project->update($validated);
        $project->members()->sync($this->teamMembers);
        $project->tags()->sync($this->tags);
        
        session()->flash('message', 'Project updated successfully!');
        return redirect()->route('projects.show', $project);
    }
    
    public function render()
    {
        return view('livewire.edit-project');
    }
}
```

**Blade View:**

```html
<div class="max-w-3xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-6">Edit Project</h1>
    
    <form wire:submit="save" class="space-y-6">
        {{-- Name --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Project Name
            </label>
            <input 
                type="text" 
                wire:model="name" 
                class="w-full border rounded-lg px-3 py-2"
            >
        </div>
        
        {{-- Category - Single selection with default --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Category
            </label>
            <livewire:async-select
                wire:model="categoryId"
                endpoint="/api/categories"
                selected-endpoint="/api/categories/selected"
                placeholder="Select category..."
            />
        </div>
        
        {{-- Owner - Single selection with default --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Project Owner
            </label>
            <livewire:async-select
                wire:model="ownerId"
                endpoint="/api/users/search"
                selected-endpoint="/api/users/selected"
                placeholder="Select owner..."
            >
                <x-slot name="slot" :option="$option">
                    <div class="flex items-center gap-2">
                        <img src="{{ $option['image'] }}" class="w-8 h-8 rounded-full">
                        <span>{{ $option['label'] }}</span>
                    </div>
                </x-slot>
            </livewire:async-select>
        </div>
        
        {{-- Team Members - Multiple selection with defaults --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Team Members
            </label>
            <livewire:async-select
                wire:model="teamMembers"
                endpoint="/api/users/search"
                selected-endpoint="/api/users/selected"
                :multiple="true"
                placeholder="Add team members..."
            />
        </div>
        
        {{-- Tags - Multiple selection with defaults --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Tags
            </label>
            <livewire:async-select
                wire:model="tags"
                endpoint="/api/tags"
                selected-endpoint="/api/tags/selected"
                :multiple="true"
                :tags="true"
                placeholder="Add or create tags..."
            />
        </div>
        
        {{-- Status --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Status
            </label>
            <livewire:async-select
                wire:model="status"
                :options="[
                    ['value' => 'draft', 'label' => 'Draft'],
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'completed', 'label' => 'Completed']
                ]"
            />
        </div>
        
        {{-- Actions --}}
        <div class="flex justify-end gap-3">
            <a 
                href="/projects/{{ $projectId }}" 
                class="px-4 py-2 border rounded-lg"
            >
                Cancel
            </a>
            <button 
                type="submit" 
                class="px-4 py-2 bg-blue-600 text-white rounded-lg"
            >
                Save Changes
            </button>
        </div>
    </form>
</div>
```

## Best Practices

### 1. Use wire:model for Reactivity

Always use `wire:model` to keep component and Livewire property in sync:

```html
✅ Good:
<livewire:async-select wire:model="userId" :options="$users" />

❌ Avoid:
<livewire:async-select :value="$userId" :options="$users" />
```

### 2. Provide selected-endpoint for Async

When using async endpoints, always provide a `selected-endpoint`:

```html
<livewire:async-select
    wire:model="userId"
    endpoint="/api/users/search"
    selected-endpoint="/api/users/selected"  {{-- Required for pre-selected values --}}
/>
```

### 3. Convert Collections for Multiple Selection

Ensure arrays for multiple selection:

```php
// Convert Eloquent Collection to array
$this->teamMembers = $project->members->pluck('id')->toArray();
```

### 4. Validate Default Values

Ensure default values exist in options:

```php
public function mount($categoryId = null)
{
    // Validate category exists
    if ($categoryId && Category::find($categoryId)) {
        $this->categoryId = $categoryId;
    } else {
        $this->categoryId = Category::first()->id; // Fallback
    }
}
```

### 5. Handle Null/Empty Gracefully

```php
public function mount($projectId = null)
{
    if ($projectId) {
        $project = Project::find($projectId);
        $this->teamMembers = $project?->members->pluck('id')->toArray() ?? [];
    }
}
```

## Common Patterns

### New vs Edit Mode

```php
public function mount($projectId = null)
{
    if ($projectId) {
        // Edit mode - load existing data
        $project = Project::find($projectId);
        $this->categoryId = $project->category_id;
        $this->ownerId = $project->owner_id;
    } else {
        // New mode - set sensible defaults
        $this->categoryId = Category::where('is_default', true)->first()?->id;
        $this->ownerId = Auth::id();
    }
}
```

### Clone/Duplicate

```php
public function mount($sourceProjectId = null)
{
    if ($sourceProjectId) {
        $source = Project::find($sourceProjectId);
        
        // Clone values but not IDs
        $this->categoryId = $source->category_id;
        $this->teamMembers = $source->members->pluck('id')->toArray();
        $this->tags = $source->tags->pluck('id')->toArray();
        
        // Modify for new project
        $this->name = $source->name . ' (Copy)';
        $this->ownerId = Auth::id(); // New owner
    }
}
```

## Next Steps

- [Validation →](/guide/validation.html)
- [API Reference →](/guide/api.html)
- [Examples →](/guide/examples.html)

