<section class="dashboard-hero">
    <div class="dashboard-hero-copy">
        @if($isSmo)<span class="dashboard-kicker">Supply Management Office</span>@endif
        <h2>{{ 'Welcome back, '.$firstName.'.' }}</h2>
        <div class="dashboard-hero-meta">
            <span><i data-lucide="calendar-days"></i>{{ now()->format('l, F d, Y') }}</span>
            <span><i data-lucide="building-2"></i>{{ Auth::user()->department ?: 'Institutional Office' }}</span>
        </div>
    </div>

    <div class="dashboard-hero-actions">
        @if($isSmo)
            <a href="{{ route('admin.approvals') }}" class="hero-btn primary"><i data-lucide="package-check"></i> Fulfillment queue</a>
            <a href="{{ route('inventory.index') }}" class="hero-btn secondary"><i data-lucide="boxes"></i> Manage stock</a>
        @else
            <button type="button" class="hero-btn primary" data-bs-toggle="modal" data-bs-target="#globalRequestModal"><i data-lucide="plus"></i> New requisition</button>
            @if(Auth::user()->role === 'employee')<a href="{{ route('requests.index') }}" class="hero-btn secondary">View all requests <i data-lucide="arrow-right"></i></a>@endif
            <a href="{{ route('requests.archive') }}" class="hero-btn secondary"><i data-lucide="archive"></i> View archive</a>
        @endif
    </div>
</section>
