<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class WorkspaceVersionService
{
    public function version(string $scope = 'all'): string
    {
        $versions = $this->versions();

        return $versions[$scope] ?? $versions['all'];
    }

    public function versions(): array
    {
        $domains = ['requisitions', 'inventory', 'accounts'];
        $cached = Cache::many(array_map(fn (string $domain) => 'workspace-version:'.$domain, $domains));
        $versions = [];
        foreach ($domains as $domain) {
            $key = 'workspace-version:'.$domain;
            $versions[$domain] = $cached[$key] ?? Cache::rememberForever($key, fn () => (string) Str::uuid());
        }
        $requests = hash('sha256', $versions['requisitions'].':'.$versions['accounts']);

        return [
            'all' => hash('sha256', implode(':', $versions)),
            'requests' => $requests, 'approvals' => $requests,
            'inventory' => hash('sha256', $versions['inventory']),
            'users' => hash('sha256', $versions['accounts']),
            'notifications' => 'notifications', 'none' => 'none',
        ];
    }

    public function touch(string $domain): void
    {
        Cache::forever('workspace-version:'.$domain, (string) Str::uuid());
    }

    public function dashboardVersion(string $dataVersion, int $latestNotificationId): string
    {
        // Deadlines and month boundaries can change without a database write.
        return hash('sha256', $dataVersion.':'.$latestNotificationId.':'.now()->format('Y-m-d H:i'));
    }
}
