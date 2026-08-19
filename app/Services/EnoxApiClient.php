<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class EnoxApiClient
{
    protected function baseUrl(): string
    {
        return rtrim(config('enox.api_url'), '/');
    }

    protected function token(): ?string
    {
        return Session::get('enox_access_token');
    }

    protected function client()
    {
        $client = Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->timeout(30);

        if ($token = $this->token()) {
            $client = $client->withToken($token);
        }

        return $client;
    }

    public function login(string $email, string $password): array
    {
        $response = Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->post('/admin/auth/login', compact('email', 'password'))
            ->throw();

        return $response->json();
    }

    public function me(): array
    {
        return $this->client()->get('/admin/auth/me')->throw()->json();
    }

    public function overview(array $query = []): array
    {
        return $this->client()->get('/admin/analytics/overview', $query)->throw()->json();
    }

    public function toolAnalytics(array $query = []): array
    {
        return $this->client()->get('/admin/analytics/tools', $query)->throw()->json();
    }

    public function csatAnalytics(array $query = []): array
    {
        return $this->client()->get('/admin/analytics/csat', $query)->throw()->json();
    }

    public function conversations(array $query = []): array
    {
        return $this->client()->get('/admin/conversations', $query)->throw()->json();
    }

    public function conversation(int $id): array
    {
        return $this->client()->get("/admin/conversations/{$id}")->throw()->json();
    }

    public function users(array $query = []): array
    {
        return $this->client()->get('/admin/users', $query)->throw()->json();
    }

    public function user(int $id, array $query = []): array
    {
        return $this->client()->get("/admin/users/{$id}", $query)->throw()->json();
    }

    public function handoffQueue(): array
    {
        return $this->client()->get('/admin/handoff/queue')->throw()->json();
    }

    public function activeHandoffs(bool $mine = false): array
    {
        return $this->client()->get('/admin/handoff/active', ['mine' => $mine ? 'true' : 'false'])->throw()->json();
    }

    public function claimHandoff(int $conversationId): array
    {
        return $this->client()->post("/admin/handoff/{$conversationId}/claim")->throw()->json();
    }

    public function sendHandoffMessage(int $conversationId, string $message): array
    {
        return $this->client()->post("/admin/handoff/{$conversationId}/message", [
            'message' => $message,
        ])->throw()->json();
    }

    public function resolveHandoff(int $conversationId): array
    {
        return $this->client()->post("/admin/handoff/{$conversationId}/resolve")->throw()->json();
    }

    public function releaseHandoff(int $conversationId): array
    {
        return $this->client()->post("/admin/handoff/{$conversationId}/release")->throw()->json();
    }

    public function setPresence(bool $isOnline): array
    {
        return $this->client()->post('/admin/agents/presence', [
            'is_online' => $isOnline,
        ])->throw()->json();
    }

    public function performanceAnalytics(): array
    {
        return $this->client()->get('/admin/analytics/performance')->throw()->json();
    }

    public function exportConversations(?array $conversationIds = null): string
    {
        return $this->client()
            ->post('/admin/conversations/export', [
                'conversation_ids' => $conversationIds,
            ])
            ->throw()
            ->body();
    }

    public function searchConversations(string $q, int $page = 1): array
    {
        return $this->client()->get('/admin/conversations/search', compact('q', 'page'))->throw()->json();
    }

    public function setTags(int $conversationId, array $tags): array
    {
        return $this->client()->post("/admin/conversations/{$conversationId}/tags", [
            'tags' => $tags,
        ])->throw()->json();
    }

    public function businessHours(): array
    {
        return $this->client()->get('/admin/settings/business-hours')->throw()->json();
    }

    public function updateBusinessHours(array $data): array
    {
        return $this->client()->put('/admin/settings/business-hours', $data)->throw()->json();
    }
}
