<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\ForumCategory;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Admin-side CRUD: categories + forums + per-forum role lists.
 *
 * Lives at /forum/admin/* and is gated by permission:forum.admin.
 */
class ForumAdminController extends Controller
{
    public function index()
    {
        $u = auth()->user();
        $categories = ForumCategory::orderBy('sort_order')->orderBy('id')
            ->with('forums')->get();
        // Private forums are visible only to their owner — hide everyone
        // else's personal forums from the admin list too.
        $categories->each(function ($c) use ($u) {
            $c->setRelation('forums', $c->forums->filter(fn ($f) => $f->isVisibleTo($u))->values());
        });
        $roleNames = Role::orderBy('sort_order')->pluck('name')->all();
        return view('forum.admin.index', compact('categories', 'roleNames'));
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:120',
            'description' => 'nullable|string|max:500',
            'sort_order'  => 'nullable|integer',
        ]);
        $data['slug'] = $this->uniqueSlug(ForumCategory::class, $data['name']);
        ForumCategory::create($data);
        return back()->with('success', 'Category created.');
    }

    public function updateCategory(ForumCategory $category, Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:120',
            'description' => 'nullable|string|max:500',
            'sort_order'  => 'nullable|integer',
        ]);
        $category->update($data);
        return back()->with('success', 'Category updated.');
    }

    public function destroyCategory(ForumCategory $category)
    {
        $category->delete();
        return back()->with('success', 'Category deleted.');
    }

    public function createForum(ForumCategory $category)
    {
        $roleNames = Role::orderBy('sort_order')->pluck('label', 'name')->all();
        return view('forum.admin.forum-edit', compact('category', 'roleNames'))
            ->with('forum', new Forum(['allowed_roles' => $this->defaultAllowed()]));
    }

    public function storeForum(ForumCategory $category, Request $request)
    {
        $data = $this->validateForum($request);
        $data['forum_category_id'] = $category->id;
        $data['slug'] = $this->uniqueSlug(Forum::class, $data['name']);
        Forum::create($data);
        return redirect()->route('forum.admin.index')->with('success', 'Forum created.');
    }

    public function editForum(Forum $forum)
    {
        $this->guardPrivate($forum);
        $category  = $forum->category;
        $roleNames = Role::orderBy('sort_order')->pluck('label', 'name')->all();
        return view('forum.admin.forum-edit', compact('category', 'forum', 'roleNames'));
    }

    public function updateForum(Forum $forum, Request $request)
    {
        $this->guardPrivate($forum);
        $data = $this->validateForum($request);
        $forum->update($data);
        return redirect()->route('forum.admin.index')->with('success', 'Forum updated.');
    }

    public function destroyForum(Forum $forum)
    {
        $this->guardPrivate($forum);
        $forum->delete();
        return back()->with('success', 'Forum deleted.');
    }

    /** Block managing someone else's private forum (and hide its existence). */
    private function guardPrivate(Forum $forum): void
    {
        if ($forum->isPrivate() && !$forum->isOwnedBy(auth()->user())) abort(404);
    }

    private function validateForum(Request $request): array
    {
        $data = $request->validate([
            'name'             => 'required|string|max:160',
            'description'      => 'nullable|string|max:800',
            'icon'             => 'nullable|string|max:60',
            'sort_order'       => 'nullable|integer',
            'is_announcements' => 'nullable|boolean',
            'is_private'       => 'nullable|boolean',
            'roles_view'       => 'array',
            'roles_view.*'     => 'string',
            'roles_post'       => 'array',
            'roles_post.*'     => 'string',
            'roles_reply'      => 'array',
            'roles_reply.*'    => 'string',
            'roles_moderate'   => 'array',
            'roles_moderate.*' => 'string',
        ]);
        $allowed = [
            'view'     => array_values($request->input('roles_view', [])),
            'post'     => array_values($request->input('roles_post', [])),
            'reply'    => array_values($request->input('roles_reply', [])),
            'moderate' => array_values($request->input('roles_moderate', ['admin'])),
        ];
        // Defensive: moderate always at least includes admin so no one
        // accidentally locks themselves out of every forum.
        if (!in_array('admin', $allowed['moderate'], true)) $allowed['moderate'][] = 'admin';

        // Private/personal forum: bound to the current admin, never public.
        // Role lists become irrelevant (Forum::userCan short-circuits on
        // private), but we keep whatever was submitted for when it's flipped
        // back to non-private.
        $isPrivate = $request->boolean('is_private');

        return [
            'name'             => $data['name'],
            'description'      => $data['description'] ?? null,
            'icon'             => $data['icon'] ?? null,
            'sort_order'       => $data['sort_order'] ?? 100,
            'is_announcements' => $isPrivate ? false : $request->boolean('is_announcements'),
            'is_private'       => $isPrivate,
            'private_owner_id' => $isPrivate ? auth()->id() : null,
            'allowed_roles'    => $allowed,
        ];
    }

    private function defaultAllowed(): array
    {
        return [
            'view'     => ['admin','doctor','nurse','receptionist','lab','pharmacist'],
            'post'     => ['admin','doctor','nurse','receptionist','lab','pharmacist'],
            'reply'    => ['admin','doctor','nurse','receptionist','lab','pharmacist'],
            'moderate' => ['admin'],
        ];
    }

    private function uniqueSlug(string $modelClass, string $name): string
    {
        $base = Str::slug(Str::limit($name, 80, ''));
        if ($base === '') $base = 'item';
        $slug = $base;
        $n = 2;
        while ($modelClass::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $n++;
            if ($n > 100) { $slug = $base . '-' . substr(bin2hex(random_bytes(3)), 0, 6); break; }
        }
        return $slug;
    }
}
