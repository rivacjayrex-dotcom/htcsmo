<x-app-layout>
    <x-slot name="header">Reports & Analytics</x-slot>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <div class="container-fluid py-2">
        <div class="mb-4">
            <h4 class="fw-black text-dark mb-1">Operational Analytics</h4>
            <p class="text-muted small mb-0">Monitor institutional supply activity, then build and export detailed reports below.</p>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-success">
                    <small class="text-muted fw-bold text-uppercase tracking-widest" style="font-size: 9px;">Semester Spending</small>
                    <h4 class="fw-black mt-2 mb-0 text-success">₱{{ number_format($costs['semester_spent'], 2) }}</h4>
                    <p class="text-muted small mb-0 mt-1">{{ $semesterLabel }}</p>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <small class="text-muted fw-bold text-uppercase tracking-widest" style="font-size: 9px;">Semester Releases</small>
                    <h4 class="fw-black mt-2 mb-0">{{ number_format($stats['semester']) }}</h4>
                    <p class="text-muted small mb-0 mt-1">Completed requisitions</p>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <small class="text-muted fw-bold text-uppercase tracking-widest" style="font-size: 9px;">Annual Releases</small>
                    <h4 class="fw-black mt-2 mb-0">{{ number_format($stats['year']) }}</h4>
                    <p class="text-muted small mb-0 mt-1">Completed in {{ date('Y') }}</p>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <small class="text-muted fw-bold text-uppercase tracking-widest" style="font-size: 9px;">Average Request</small>
                    <h4 class="fw-black mt-2 mb-0">₱{{ number_format($costs['avg_per_req'], 2) }}</h4>
                    <p class="text-muted small mb-0 mt-1">Per released transaction</p>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <small class="text-muted fw-bold text-uppercase tracking-widest" style="font-size: 9px;">Approved Users</small>
                    <h4 class="fw-black mt-2 mb-0">{{ number_format($stats['users']) }}</h4>
                    <p class="text-muted small mb-0 mt-1">Active personnel accounts</p>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-dark text-white h-100">
                    <small class="text-white-50 fw-bold text-uppercase tracking-widest" style="font-size: 9px;">Inventory Value</small>
                    <h4 class="fw-black mt-2 mb-0">₱{{ number_format($costs['inventory_value'], 2) }}</h4>
                    <p class="text-white-50 small mb-0 mt-1">{{ number_format($stats['inventory_items']) }} catalog items</p>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100">
                    <h6 class="fw-bold mb-4">Fulfillment Volume ({{ date('Y') }})</h6>
                    <canvas id="monthlyChart" style="max-height: 300px;"></canvas>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100">
                    <h6 class="fw-bold mb-4">All-Time Spending by Department</h6>
                    <canvas id="deptChart"></canvas>
                </div>
            </div>

            <div class="col-lg-12 mt-4">
                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                    <div class="card-header bg-white border-0 p-4 pb-0">
                        <h6 class="fw-bold">Top 5 Distributed Items</h6>
                    </div>
                    <div class="table-responsive p-4 pt-2">
                        <table class="table table-sm align-middle">
                            <thead class="bg-light small text-uppercase">
                                <tr>
                                    <th>Item Description</th>
                                    <th class="text-center">Total Quantity Distributed</th>
                                    <th>Relative Volume</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php($maxTopQuantity = max(1, (int) $topItems->max('total_qty')))
                                @forelse($topItems as $item)
                                    <tr>
                                        <td class="fw-bold">{{ $item->item_name }}</td>
                                        <td class="text-center">{{ number_format($item->total_qty) }}</td>
                                        <td>
                                            <div class="progress" style="height: 6px; width: 120px;">
                                                <div class="progress-bar bg-primary" style="width: {{ ($item->total_qty / $maxTopQuantity) * 100 }}%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">No released item data is available yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        @include('admin.reports.index')
    </div>

    <script>
        const monthlyLabels = @json($monthlyData->pluck('month'));
        const monthlyValues = @json($monthlyData->pluck('count'));
        const deptLabels = @json($deptSpending->pluck('department'));
        const deptValues = @json($deptSpending->pluck('total'));

        new Chart(document.getElementById('monthlyChart'), {
            type: 'line',
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: 'Requisitions Released',
                    data: monthlyValues,
                    borderColor: '#185b3b',
                    backgroundColor: 'rgba(24, 91, 59, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: { plugins: { legend: { display: false } } }
        });

        new Chart(document.getElementById('deptChart'), {
            type: 'doughnut',
            data: {
                labels: deptLabels,
                datasets: [{
                    data: deptValues,
                    backgroundColor: ['#185b3b', '#28a745', '#ffc107', '#17a2b8', '#6c757d']
                }]
            },
            options: { cutout: '70%' }
        });
    </script>
</x-app-layout>
