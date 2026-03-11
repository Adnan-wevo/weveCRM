<?php

namespace Modules\AccessControl\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\AccessControl\Http\Requests\Api\V1\StoreRoleRequest;
use Modules\AccessControl\Http\Requests\Api\V1\UpdateRoleRequest;
use Modules\AccessControl\Http\Resources\Api\V1\RoleResource;

/**
 * @group Access Control — Roles
 *
 * Manage roles. Requires `access-control.roles.*` permissions.
 */
class RoleController extends Controller
{
    /**
     * List roles
     *
     * Returns a paginated list of all roles (including soft-deleted when `with_trashed=1`).
     *
     * @queryParam per_page int Number of results per page. Default: 15. Example: 25
     * @queryParam with_trashed bool Include soft-deleted roles. Example: 0
     *
     * @response 200 {"data": [{"id": "uuid", "name": "admin", "guard_name": "web"}], "links": {}, "meta": {}}
     * @response 403 {"message": "This action is unauthorized."}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('access-control.roles.index');

        $query = Role::query()->with('permissions');

        if ($request->boolean('with_trashed')) {
            $query->withTrashed();
        }

        return RoleResource::collection($query->paginate($request->integer('per_page', 15)));
    }

    /**
     * Create a role
     *
     * Creates a new role and optionally assigns permissions to it.
     *
     * @response 201 {"data": {"id": "uuid", "name": "editor", "guard_name": "web", "permissions": ["access-control.users.index"]}}
     * @response 403 {"message": "This action is unauthorized."}
     * @response 422 {"message": "The given data was invalid.", "errors": {"name": ["A role with this name already exists."]}}
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        /** @var array<string, mixed> $validated */
        $validated = $request->validated();

        $role = Role::query()->create([
            'name' => $validated['name'],
            'guard_name' => $validated['guard_name'] ?? 'web',
        ]);

        if (! empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        $role->load('permissions');

        return (new RoleResource($role))->response()->setStatusCode(201);
    }

    /**
     * Get a role
     *
     * Returns a single role by UUID.
     *
     * @urlParam role string required The UUID of the role. Example: 9d8f7c6b-5a4e-3d2c-1b0a-9e8f7d6c5b4e
     *
     * @response 200 {"data": {"id": "uuid", "name": "admin", "permissions": ["access-control.users.index"]}}
     * @response 403 {"message": "This action is unauthorized."}
     * @response 404 {"message": "No query results for model [App\\Models\\Role]."}
     */
    public function show(Role $role): RoleResource
    {
        $this->authorize('access-control.roles.show');

        $role->load('permissions');

        return new RoleResource($role);
    }

    /**
     * Update a role
     *
     * Updates a role's name and/or permissions.
     *
     * @urlParam role string required The UUID of the role. Example: 9d8f7c6b-5a4e-3d2c-1b0a-9e8f7d6c5b4e
     *
     * @response 200 {"data": {"id": "uuid", "name": "editor"}}
     * @response 403 {"message": "This action is unauthorized."}
     * @response 404 {"message": "No query results for model [App\\Models\\Role]."}
     * @response 422 {"message": "The given data was invalid.", "errors": {}}
     */
    public function update(UpdateRoleRequest $request, Role $role): RoleResource
    {
        /** @var array<string, mixed> $validated */
        $validated = $request->validated();

        $role->update($validated);

        if (array_key_exists('permissions', $validated)) {
            $role->syncPermissions($validated['permissions']);
        }

        $role->load('permissions');

        return new RoleResource($role);
    }

    /**
     * Soft-delete a role
     *
     * Moves the role to the trash.
     *
     * @urlParam role string required The UUID of the role. Example: 9d8f7c6b-5a4e-3d2c-1b0a-9e8f7d6c5b4e
     *
     * @response 204 {}
     * @response 403 {"message": "This action is unauthorized."}
     */
    public function destroy(Role $role): JsonResponse
    {
        $this->authorize('access-control.roles.destroy');

        $role->delete();

        return response()->json(null, 204);
    }

    /**
     * Restore a soft-deleted role
     *
     * Restores a previously soft-deleted role.
     *
     * @urlParam role string required The UUID of the trashed role. Example: 9d8f7c6b-5a4e-3d2c-1b0a-9e8f7d6c5b4e
     *
     * @response 200 {"data": {"id": "uuid", "name": "admin", "deleted_at": null}}
     * @response 403 {"message": "This action is unauthorized."}
     * @response 404 {"message": "No query results for model [App\\Models\\Role]."}
     */
    public function restore(string $role): RoleResource
    {
        $this->authorize('access-control.roles.restore');

        /** @var Role $model */
        $model = Role::withTrashed()->findOrFail($role);
        $model->restore();

        return new RoleResource($model);
    }

    /**
     * Permanently delete a role
     *
     * Irreversibly removes the role from the database.
     *
     * @urlParam role string required The UUID of the trashed role. Example: 9d8f7c6b-5a4e-3d2c-1b0a-9e8f7d6c5b4e
     *
     * @response 204 {}
     * @response 403 {"message": "This action is unauthorized."}
     * @response 404 {"message": "No query results for model [App\\Models\\Role]."}
     */
    public function forceDelete(string $role): JsonResponse
    {
        $this->authorize('access-control.roles.force-delete');

        /** @var Role $model */
        $model = Role::withTrashed()->findOrFail($role);
        $model->forceDelete();

        return response()->json(null, 204);
    }
}
