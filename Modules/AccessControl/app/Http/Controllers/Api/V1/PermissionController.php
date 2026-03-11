<?php

namespace Modules\AccessControl\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\AccessControl\Http\Requests\Api\V1\StorePermissionRequest;
use Modules\AccessControl\Http\Requests\Api\V1\UpdatePermissionRequest;
use Modules\AccessControl\Http\Resources\Api\V1\PermissionResource;

/**
 * @group Access Control — Permissions
 *
 * Manage permissions. Requires `access-control.permissions.*` permissions.
 */
class PermissionController extends Controller
{
    /**
     * List permissions
     *
     * Returns a paginated list of all permissions (including soft-deleted when `with_trashed=1`).
     *
     * @queryParam per_page int Number of results per page. Default: 15. Example: 25
     * @queryParam with_trashed bool Include soft-deleted permissions. Example: 0
     *
     * @response 200 {"data": [{"id": "uuid", "name": "access-control.users.index", "guard_name": "web"}], "links": {}, "meta": {}}
     * @response 403 {"message": "This action is unauthorized."}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('access-control.permissions.index');

        $query = Permission::query();

        if ($request->boolean('with_trashed')) {
            $query->withTrashed();
        }

        return PermissionResource::collection($query->paginate($request->integer('per_page', 15)));
    }

    /**
     * Create a permission
     *
     * Creates a new permission. Use the `access-control.{module}.{action}` naming convention.
     *
     * @response 201 {"data": {"id": "uuid", "name": "access-control.reports.index", "guard_name": "web"}}
     * @response 403 {"message": "This action is unauthorized."}
     * @response 422 {"message": "The given data was invalid.", "errors": {"name": ["A permission with this name already exists."]}}
     */
    public function store(StorePermissionRequest $request): JsonResponse
    {
        /** @var array<string, mixed> $validated */
        $validated = $request->validated();

        $permission = Permission::query()->create([
            'name' => $validated['name'],
            'guard_name' => $validated['guard_name'] ?? 'web',
        ]);

        return (new PermissionResource($permission))->response()->setStatusCode(201);
    }

    /**
     * Get a permission
     *
     * Returns a single permission by UUID.
     *
     * @urlParam permission string required The UUID of the permission. Example: 9d8f7c6b-5a4e-3d2c-1b0a-9e8f7d6c5b4e
     *
     * @response 200 {"data": {"id": "uuid", "name": "access-control.users.index"}}
     * @response 403 {"message": "This action is unauthorized."}
     * @response 404 {"message": "No query results for model [App\\Models\\Permission]."}
     */
    public function show(Permission $permission): PermissionResource
    {
        $this->authorize('access-control.permissions.show');

        return new PermissionResource($permission);
    }

    /**
     * Update a permission
     *
     * Updates a permission's name or guard.
     *
     * @urlParam permission string required The UUID of the permission. Example: 9d8f7c6b-5a4e-3d2c-1b0a-9e8f7d6c5b4e
     *
     * @response 200 {"data": {"id": "uuid", "name": "access-control.reports.export"}}
     * @response 403 {"message": "This action is unauthorized."}
     * @response 404 {"message": "No query results for model [App\\Models\\Permission]."}
     * @response 422 {"message": "The given data was invalid.", "errors": {}}
     */
    public function update(UpdatePermissionRequest $request, Permission $permission): PermissionResource
    {
        $permission->update($request->validated());

        return new PermissionResource($permission);
    }

    /**
     * Soft-delete a permission
     *
     * Moves the permission to the trash.
     *
     * @urlParam permission string required The UUID of the permission. Example: 9d8f7c6b-5a4e-3d2c-1b0a-9e8f7d6c5b4e
     *
     * @response 204 {}
     * @response 403 {"message": "This action is unauthorized."}
     */
    public function destroy(Permission $permission): JsonResponse
    {
        $this->authorize('access-control.permissions.destroy');

        $permission->delete();

        return response()->json(null, 204);
    }

    /**
     * Restore a soft-deleted permission
     *
     * Restores a previously soft-deleted permission.
     *
     * @urlParam permission string required The UUID of the trashed permission. Example: 9d8f7c6b-5a4e-3d2c-1b0a-9e8f7d6c5b4e
     *
     * @response 200 {"data": {"id": "uuid", "name": "access-control.users.index", "deleted_at": null}}
     * @response 403 {"message": "This action is unauthorized."}
     * @response 404 {"message": "No query results for model [App\\Models\\Permission]."}
     */
    public function restore(string $permission): PermissionResource
    {
        $this->authorize('access-control.permissions.restore');

        /** @var Permission $model */
        $model = Permission::withTrashed()->findOrFail($permission);
        $model->restore();

        return new PermissionResource($model);
    }

    /**
     * Permanently delete a permission
     *
     * Irreversibly removes the permission from the database.
     *
     * @urlParam permission string required The UUID of the trashed permission. Example: 9d8f7c6b-5a4e-3d2c-1b0a-9e8f7d6c5b4e
     *
     * @response 204 {}
     * @response 403 {"message": "This action is unauthorized."}
     * @response 404 {"message": "No query results for model [App\\Models\\Permission]."}
     */
    public function forceDelete(string $permission): JsonResponse
    {
        $this->authorize('access-control.permissions.force-delete');

        /** @var Permission $model */
        $model = Permission::withTrashed()->findOrFail($permission);
        $model->forceDelete();

        return response()->json(null, 204);
    }
}
