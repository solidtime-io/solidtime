<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\Tag\TagStoreRequest;
use App\Http\Requests\V1\Tag\TagUpdateRequest;
use App\Http\Resources\V1\Client\ClientCollection;
use App\Http\Resources\V1\Client\ClientResource;
use App\Models\Client;
use App\Models\Organization;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class ClientController extends Controller
{
    protected function checkPermission(Organization $organization, string $permission, ?Client $client = null): void
    {
        parent::checkPermission($organization, $permission);
        if ($client !== null && $client->organization_id !== $organization->getKey()) {
            throw new AuthorizationException('Tag does not belong to organization');
        }
    }

    /**
     * Get clients
     *
     * @return ClientCollection<ClientResource>
     *
     * @throws AuthorizationException
     */
    public function index(Organization $organization): ClientCollection
    {
        $this->checkPermission($organization, 'clients:view');

        $clients = Client::query()
            ->whereBelongsTo($organization, 'organization')
            ->orderBy('created_at', 'desc')
            ->paginate();

        return new ClientCollection($clients);
    }

    /**
     * Create client
     *
     * @throws AuthorizationException
     */
    public function store(Organization $organization, TagStoreRequest $request): ClientResource
    {
        $this->checkPermission($organization, 'clients:create');

        $client = new Client();
        $client->name = $request->input('name');
        $client->organization()->associate($organization);
        $client->save();

        return new ClientResource($client);
    }

    /**
     * Update client
     *
     * @throws AuthorizationException
     */
    public function update(Organization $organization, Client $client, TagUpdateRequest $request): ClientResource
    {
        $this->checkPermission($organization, 'clients:update', $client);

        $client->name = $request->input('name');
        $client->save();

        return new ClientResource($client);
    }

    /**
     * Delete client
     *
     * @throws AuthorizationException
     */
    public function destroy(Organization $organization, Client $client): JsonResponse
    {
        $this->checkPermission($organization, 'clients:delete', $client);

        $client->delete();

        return response()->json(null, 204);
    }
}
