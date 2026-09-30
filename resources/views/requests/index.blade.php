<x-app-layout>
    <x-slot name="header">
        {{ __('Active Requisitions') }}
    </x-slot>

    <div class="container-fluid py-2">
        <div class="workflow-page-heading d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-black text-dark mb-0">Active Requisitions</h4>
                <p class="text-muted small mb-0">Track and monitor your in-progress supply requests.</p>
            </div>
        </div>

        <div class="d-flex align-items-center mb-2">
            <div class="p-2 rounded-3 bg-primary-subtle text-primary me-2">
                <i data-lucide="clock" style="width:16px;"></i>
            </div>
            <h6 class="fw-bold mb-0 text-dark">Currently In Progress ({{ $activeRequests->total() }})</h6>
        </div>

        <!-- ACTIVE TABLE -->
        <div class="mb-4">
            @include('requests.partials.history-table', ['requisitions' => $activeRequests, 'type' => 'active'])
        </div>

        <div>
            {{ $activeRequests->withQueryString()->links() }}
        </div>
    </div>
</x-app-layout>
