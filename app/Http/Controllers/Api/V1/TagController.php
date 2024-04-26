<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Api\EntityStillInUseApiException;
use App\Http\Requests\V1\Tag\TagStoreRequest;
use App\Http\Requests\V1\Tag\TagUpdateRequest;
use App\Http\Resources\V1\Tag\TagCollection;
use App\Http\Resources\V1\Tag\TagResource;
use App\Models\Organization;
use App\Models\Tag;
use App\Models\TimeEntry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    protected function checkPermission(Organization $organization, string $permission, ?Tag $tag = null): void
    {
        parent::checkPermission($organization, $permission);
        if ($tag !== null && $tag->organization_id !== $organization->getKey()) {
            throw new AuthorizationException('Tag does not belong to organization');
        }
    }

    /**
     * Get tags
     *
     * @return TagCollection<TagResource>
     *
     * @operationId getTags
     *
     * @throws AuthorizationException
     */
    public function index(Organization $organization): TagCollection
    {
        $this->checkPermission($organization, 'tags:view');

        $tags = Tag::query()
            ->whereBelongsTo($organization, 'organization')
            ->orderBy('created_at', 'desc')
            ->paginate(config('app.pagination_per_page_default'));

        return new TagCollection($tags);
    }

    /**
     * Create tag
     *
     * @throws AuthorizationException
     *
     * @operationId createTag
     */
    public function store(Organization $organization, TagStoreRequest $request): TagResource
    {
        $this->checkPermission($organization, 'tags:create');

        $tag = new Tag();
        $tag->name = $request->input('name');
        $tag->organization()->associate($organization);
        $tag->save();

        return new TagResource($tag);
    }

    /**
     * Update tag
     *
     * @throws AuthorizationException
     *
     * @operationId updateTag
     */
    public function update(Organization $organization, Tag $tag, TagUpdateRequest $request): TagResource
    {
        $this->checkPermission($organization, 'tags:update', $tag);

        $tag->name = $request->input('name');
        $tag->save();

        return new TagResource($tag);
    }

    /**
     * Delete tag
     *
     * @throws AuthorizationException|EntityStillInUseApiException
     *
     * @operationId deleteTag
     */
    public function destroy(Organization $organization, Tag $tag): JsonResponse
    {
        $this->checkPermission($organization, 'tags:delete', $tag);

        if (TimeEntry::query()->hasTag($tag)->whereBelongsTo($organization, 'organization')->exists()) {
            throw new EntityStillInUseApiException('tag', 'time_entry');
        }

        $tag->delete();

        return response()->json(null, 204);
    }
}
