<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = $request->string('search')->toString();
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->input('role')))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return $this->success(
            UserResource::collection($users->items()),
            'Users retrieved successfully.',
            200,
            [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ]
        );
    }

    public function show(User $user)
    {
        return $this->success(new UserResource($user), 'User retrieved successfully.');
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        if (isset($data['role']) && $data['role'] !== UserRole::Admin->value && $user->role === UserRole::Admin) {
            if ($this->isLastActiveAdmin($user)) {
                return $this->error('Cannot demote the last active administrator.', 422);
            }
        }

        if (isset($data['is_active']) && ! $data['is_active'] && $user->role === UserRole::Admin) {
            if ($this->isLastActiveAdmin($user)) {
                return $this->error('Cannot deactivate the last active administrator.', 422);
            }
        }

        $user->update($data);

        ActivityLog::record('user.updated', 'User', $user->id, $data);

        return $this->success(new UserResource($user->fresh()), 'User updated successfully.');
    }

    private function isLastActiveAdmin(User $user): bool
    {
        return User::where('role', UserRole::Admin)
            ->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->doesntExist();
    }
}
