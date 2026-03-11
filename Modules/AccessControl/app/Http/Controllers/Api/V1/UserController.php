<?php

namespace Modules\AccessControl\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Modules\AccessControl\Http\Requests\Api\V1\StoreUserRequest;
use Modules\AccessControl\Http\Requests\Api\V1\UpdateUserRequest;
use Modules\AccessControl\Http\Resources\Api\V1\UserResource;

/**
 * @group Access Control — Users
 *
 * Manage application users. Requires `access-control.users.*` permissions.
 */
class UserController extends Controller
{
    /**
     * List users
     *
     * Returns a paginated list of all users (including soft-deleted when `with_trashed=1`).
     *
     * @queryParam per_page int Number of results per page. Default: 15. Example: 25
     * @queryParam with_trashed bool Include soft-deleted users. Example: 0
     *
     * @response 200 {"data": [{"id": "uuid", "name": "John Doe", "email": "john@example.com", "created_at": "2024-01-01T00:00:00+00:00"}], "links": {}, "meta": {}}
     * @response 403 {"message": "This action is unauthorized."}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('access-control.users.index');

        $query = User::query()->with(['roles', 'permissions']);

        if ($request->boolean('with_trashed')) {
            $query->withTrashed();
        }

        return UserResource::collection($query->paginate($request->integer('per_page', 15)));
    }

    /**
     * Create a user
     *
     * Creates a new user and optionally assigns roles.
     *
     * @response 201 {"data": {"id": "uuid", "name": "John Doe", "email": "john@example.com", "roles": ["admin"]}}
     * @response 403 {"message": "This action is unauthorized."}
     * @response 422 {"message": "The given data was invalid.", "errors": {"email": ["The email has already been taken."]}}
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        /** @var array<string, mixed> $validated */
        $validated = $request->validated();

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        if (! empty($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        $user->load(['roles', 'permissions']);

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    /**
     * Get a user
     *
     * Returns a single user by UUID.
     *
     * @urlParam user string required The UUID of the user. Example: 9d8f7c6b-5a4e-3d2c-1b0a-9e8f7d6c5b4e
     *
     * @response 200 {"data": {"id": "uuid", "name": "John Doe", "email": "john@example.com"}}
     * @response 403 {"message": "This action is unauthorized."}
     * @response 404 {"message": "No query results for model [App\\Models\\User]."}
     */
    public function show(User $user): UserResource
    {
        $this->authorize('access-control.users.show');

        $user->load(['roles', 'permissions']);

        return new UserResource($user);
    }

    /**
     * Update a user
     *
     * Updates an existing user's attributes and/or roles.
     *
     * @urlParam user string required The UUID of the user. Example: 9d8f7c6b-5a4e-3d2c-1b0a-9e8f7d6c5b4e
     *
     * @response 200 {"data": {"id": "uuid", "name": "Jane Doe", "email": "jane@example.com"}}
     * @response 403 {"message": "This action is unauthorized."}
     * @response 404 {"message": "No query results for model [App\\Models\\User]."}
     * @response 422 {"message": "The given data was invalid.", "errors": {}}
     */
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        /** @var array<string, mixed> $validated */
        $validated = $request->validated();

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        if (array_key_exists('roles', $validated)) {
            $user->syncRoles($validated['roles']);
        }

        $user->load(['roles', 'permissions']);

        return new UserResource($user);
    }

    /**
     * Soft-delete a user
     *
     * Moves the user to the trash. Can be restored with the restore endpoint.
     *
     * @urlParam user string required The UUID of the user. Example: 9d8f7c6b-5a4e-3d2c-1b0a-9e8f7d6c5b4e
     *
     * @response 204 {}
     * @response 403 {"message": "This action is unauthorized."}
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('access-control.users.destroy');

        $user->delete();

        return response()->json(null, 204);
    }

    /**
     * Restore a soft-deleted user
     *
     * Restores a previously soft-deleted user.
     *
     * @urlParam user string required The UUID of the trashed user. Example: 9d8f7c6b-5a4e-3d2c-1b0a-9e8f7d6c5b4e
     *
     * @response 200 {"data": {"id": "uuid", "name": "John Doe", "deleted_at": null}}
     * @response 403 {"message": "This action is unauthorized."}
     * @response 404 {"message": "No query results for model [App\\Models\\User]."}
     */
    public function restore(string $user): UserResource
    {
        $this->authorize('access-control.users.restore');

        /** @var User $model */
        $model = User::withTrashed()->findOrFail($user);
        $model->restore();

        return new UserResource($model);
    }

    /**
     * Permanently delete a user
     *
     * Irreversibly removes the user from the database.
     *
     * @urlParam user string required The UUID of the trashed user. Example: 9d8f7c6b-5a4e-3d2c-1b0a-9e8f7d6c5b4e
     *
     * @response 204 {}
     * @response 403 {"message": "This action is unauthorized."}
     * @response 404 {"message": "No query results for model [App\\Models\\User]."}
     */
    public function forceDelete(string $user): JsonResponse
    {
        $this->authorize('access-control.users.force-delete');

        /** @var User $model */
        $model = User::withTrashed()->findOrFail($user);
        $model->forceDelete();

        return response()->json(null, 204);
    }
}
