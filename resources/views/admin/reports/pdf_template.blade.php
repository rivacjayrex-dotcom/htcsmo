<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $documentTitle }}</title>
    <style>
        @page { margin: 25px 28px 42px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #29372f; font-family: DejaVu Sans, sans-serif; font-size: 7px; line-height: 1.35; }
        table { width: 100%; border-collapse: collapse; }
        .no-border td { border: 0; }
        .report-header { padding-bottom: 10px; border-bottom: 2px solid #185b3b; }
        .logo-cell { width: 58px; padding-right: 10px; vertical-align: top; }
        .logo { width: 48px; height: 48px; }
        .institution { color: #185b3b; font-size: 12px; font-weight: bold; letter-spacing: .25px; }
        .office { margin-top: 1px; color: #65736a; font-size: 6.5px; font-weight: bold; letter-spacing: .8px; text-transform: uppercase; }
        .report-title { margin: 8px 0 1px; color: #1f2d25; font-size: 15px; font-weight: bold; }
        .report-period { color: #65736a; font-size: 7.5px; }
        .control-box { width: 225px; padding-left: 14px; vertical-align: top; }
        .control-box table { border: 1px solid #ccd8d0; }
        .control-box td { padding: 3px 5px; border-bottom: 1px solid #e0e7e2; vertical-align: top; }
        .control-box tr:last-child td { border-bottom: 0; }
        .control-label { width: 72px; color: #748178; font-size: 5.5px; font-weight: bold; letter-spacing: .25px; text-transform: uppercase; }
        .control-value { color: #2c3a31; font-size: 6.2px; font-weight: bold; overflow-wrap: anywhere; }
        .section { margin-top: 10px; page-break-inside: avoid; }
        .section-title { margin: 0 0 5px; padding-bottom: 3px; border-bottom: 1px solid #ccd7d0; color: #185b3b; font-size: 7.5px; font-weight: bold; letter-spacing: .6px; text-transform: uppercase; }
        .purpose { margin: 0; color: #48574e; font-size: 7px; }
        .scope-table { margin-top: 6px; border: 1px solid #d7e0da; table-layout: fixed; }
        .scope-table td { padding: 5px 7px; border-right: 1px solid #e2e8e4; vertical-align: top; }
        .scope-table td:last-child { border-right: 0; }
        .scope-table span, .metric span { display: block; margin-bottom: 2px; color: #748178; font-size: 5.4px; font-weight: bold; letter-spacing: .35px; text-transform: uppercase; }
        .scope-table strong { color: #2d3b32; font-size: 6.7px; }
        .filter-line { margin-top: 5px; padding: 5px 7px; border-left: 3px solid #739c82; background: #f5f8f6; color: #536159; }
        .filter-line strong { color: #33443a; }
        .metric-table { table-layout: fixed; }
        .metric-table td { padding-right: 6px; vertical-align: top; }
        .metric-table td:last-child { padding-right: 0; }
        .metric { min-height: 40px; padding: 7px 8px; border: 1px solid #d7e0da; background: #f8faf9; }
        .metric strong { display: block; margin-top: 3px; color: #185b3b; font-size: 10.5px; }
        .breakdown-table { table-layout: fixed; }
        .breakdown-table td { padding: 5px 6px; border: 1px solid #d7e0da; vertical-align: top; }
        .breakdown-name { color: #1f5c3b; font-weight: bold; }
        .breakdown-detail { margin-top: 2px; color: #6c7971; font-size: 5.8px; }
        .detail-title { margin: 11px 0 5px; color: #185b3b; font-size: 7.5px; font-weight: bold; letter-spacing: .6px; text-transform: uppercase; }
        .data-table { table-layout: auto; }
        .data-table thead { display: table-header-group; }
        .data-table tr { page-break-inside: avoid; }
        .data-table th { padding: 4px 3px; border: 1px solid #b9c8be; color: #fff; background: #315f43; font-size: 5.2px; font-weight: bold; letter-spacing: .15px; text-align: left; text-transform: uppercase; vertical-align: middle; }
        .data-table td { padding: 3.5px 3px; border: 1px solid #d6dfd9; color: #344139; font-size: 5.7px; vertical-align: top; overflow-wrap: anywhere; }
        .data-table tbody tr:nth-child(even) td { background: #f7f9f8; }
        .data-table tfoot td { padding: 4px; border: 1px solid #b9c7be; background: #edf3ef; font-weight: bold; }
        .data-table .category-row td { padding: 5px 6px; border-color: #9eb4a5; background: #dfeae3; color: #174d32; font-size: 6.2px; font-weight: bold; letter-spacing: .35px; text-transform: uppercase; }
        .category-meta { float: right; color: #536b5b; font-size: 5.4px; font-weight: normal; letter-spacing: 0; text-transform: none; }
        .item-name { color: #26372d; font-weight: bold; }
        .item-spec { margin-top: 1px; color: #718078; font-size: 5.1px; line-height: 1.25; }
        .register-table th:nth-child(1) { width: 21%; }
        .register-table th:nth-child(2) { width: 4.5%; }
        .register-table th:nth-child(3) { width: 5.5%; }
        .register-table th:nth-child(4), .register-table th:nth-child(5) { width: 8%; }
        .register-table th:nth-child(6) { width: 7.5%; }
        .register-table th:nth-child(7) { width: 14%; }
        .register-table th:nth-child(8) { width: 12%; }
        .register-table th:nth-child(9) { width: 11%; }
        .text-right { text-align: right !important; }
        .empty { padding: 20px !important; color: #708078 !important; text-align: center; }
        .notes { margin: 0; padding-left: 14px; color: #536159; }
        .notes li { margin-bottom: 2px; }
        .source-note { margin-top: 5px; padding: 5px 7px; border: 1px solid #dce4df; background: #fafcfb; color: #66746c; font-size: 6px; }
        .signoff { margin-top: 18px; page-break-inside: avoid; table-layout: fixed; }
        .signoff td { width: 33.33%; padding-right: 28px; border: 0; vertical-align: bottom; }
        .signoff td:last-child { padding-right: 0; }
        .signature-line { padding-top: 15px; border-bottom: 1px solid #536159; }
        .signature-label { margin-top: 3px; color: #728078; font-size: 5.5px; text-align: center; text-transform: uppercase; }
        .prepared-name { text-align: center; font-size: 6.5px; font-weight: bold; }
        .footer { position: fixed; right: 0; bottom: -27px; left: 0; padding-top: 5px; border-top: 1px solid #cfd8d2; color: #748178; font-size: 5.5px; }
        .footer-left { float: left; }
        .footer-right { float: right; }
        .page-number:after { content: counter(page); }
    </style>
</head>
<body>
    <div class="footer">
        <span class="footer-left">HTC Supply Management System | {{ $classification }}</span>
        <span class="footer-right">{{ $reportReference }} | Page <span class="page-number"></span></span>
    </div>

    <table class="no-border report-header">
        <tr>
            <td class="logo-cell">
                @if(file_exists(public_path('images/android-chrome-512x5122.png')))
                    <img class="logo" src="{{ public_path('images/android-chrome-512x5122.png') }}" alt="HTC">
                @endif
            </td>
            <td>
                <div class="institution">HOLY TRINITY COLLEGE OF GENERAL SANTOS CITY</div>
                <div class="office">Supply Management Office</div>
                <div class="report-title">{{ $documentTitle }}</div>
                <div class="report-period">Reporting period: {{ $periodLabel }}</div>
            </td>
            <td class="control-box">
                <table>
                    <tr><td class="control-label">Reference</td><td class="control-value">{{ $reportReference }}</td></tr>
                    <tr><td class="control-label">Generated</td><td class="control-value">{{ $generatedDate }}</td></tr>
                    <tr><td class="control-label">Prepared by</td><td class="control-value">{{ $generatedBy }}</td></tr>
                    <tr><td class="control-label">Classification</td><td class="control-value">{{ $classification }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">1. Report Scope</div>
        <p class="purpose">{{ $purpose }}</p>
        <table class="scope-table"><tr>
            <td><span>Coverage</span><strong>{{ $periodLabel }}</strong></td>
            <td><span>Detailed rows</span><strong>{{ number_format($rows->count()) }}</strong></td>
            <td><span>Data as of</span><strong>{{ $dataAsOf }}</strong></td>
            <td><span>Report basis</span><strong>{{ $reportBasis }}</strong></td>
        </tr></table>
        <div class="filter-line"><strong>Applied filters:</strong> {{ $appliedFilters ?: 'No additional filters; all records within the stated coverage are included.' }}</div>
    </div>

    <div class="section">
        <div class="section-title">2. Executive Summary</div>
        <table class="metric-table"><tr>
            @foreach($metrics as $metric)
                <td><div class="metric"><span>{{ $metric['label'] }}</span><strong>
                    @if($metric['type'] === 'money')
                        PHP {{ number_format((float) $metric['value'], 2) }}
                    @else
                        {{ number_format((float) $metric['value']) }}
                    @endif
                </strong></div></td>
            @endforeach
        </tr></table>
    </div>

    @if($breakdown->isNotEmpty())
        <div class="section">
            <div class="section-title">3. Categorized Summary by {{ $breakdownTitle }}</div>
            <table class="breakdown-table">
                @foreach($breakdown->chunk(4) as $chunk)
                    <tr>
                        @foreach($chunk as $group)
                            <td>
                                <div class="breakdown-name">{{ $group['label'] }}</div>
                                <div class="breakdown-detail">
                                    {{ number_format($group['records']) }} row(s)
                                    @if($group['valueLabel'])
                                        | {{ $group['valueLabel'] }}:
                                        {{ $group['type'] === 'money' ? 'PHP '.number_format((float) $group['value'], 2) : number_format((float) $group['value']) }}
                                    @endif
                                </div>
                            </td>
                        @endforeach
                        @for($i = $chunk->count(); $i < 4; $i++)<td>&nbsp;</td>@endfor
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    <div class="detail-title">4. {{ $detailTitle }}</div>
    <table class="data-table {{ $usesCategoryRegister ? 'register-table' : '' }}">
        <thead><tr>
            @foreach($detailColumns as $column)
                <th class="{{ in_array($column['type'], ['money', 'number']) ? 'text-right' : '' }}">{{ $column['label'] }}</th>
            @endforeach
        </tr></thead>
        @if($usesCategoryRegister && $detailGroups->isNotEmpty())
            @foreach($detailGroups as $group)
                <tbody>
                    <tr class="category-row">
                        <td colspan="{{ count($detailColumns) }}">
                            {{ $group['label'] }}
                            <span class="category-meta">
                                {{ number_format($group['requests']) }} request(s) &bull;
                                {{ number_format($group['quantity']) }} unit(s) &bull;
                                PHP {{ number_format((float) $group['total'], 2) }}
                            </span>
                        </td>
                    </tr>
                    @foreach($group['rows'] as $row)
                        <tr>
                            @foreach($detailColumns as $column)
                                @php($value = $row[$column['key']] ?? null)
                                <td class="{{ in_array($column['type'], ['money', 'number']) ? 'text-right' : '' }}">
                                    @if($column['key'] === 'item')
                                        <div class="item-name">{{ filled($value) ? $value : '-' }}</div>
                                        @if(filled($row['specifications'] ?? null) && $row['specifications'] !== 'Not specified')
                                            <div class="item-spec">{{ $row['specifications'] }}</div>
                                        @endif
                                    @elseif($column['type'] === 'money')
                                        PHP {{ number_format((float) $value, 2) }}
                                    @elseif($column['type'] === 'number')
                                        {{ number_format((float) $value) }}
                                    @else
                                        {{ filled($value) ? $value : '-' }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            @endforeach
        @else
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        @foreach($detailColumns as $column)
                            @php($value = $row[$column['key']] ?? null)
                            <td class="{{ in_array($column['type'], ['money', 'number']) ? 'text-right' : '' }}">
                                @if($column['type'] === 'money')
                                    PHP {{ number_format((float) $value, 2) }}
                                @elseif($column['type'] === 'number')
                                    {{ number_format((float) $value) }}
                                @else
                                    {{ filled($value) ? $value : '-' }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($detailColumns) }}" class="empty">No records matched the selected period and filters.</td></tr>
                @endforelse
            </tbody>
        @endif
        @if($rows->isNotEmpty())
            <tfoot><tr>
                <td colspan="{{ count($detailColumns) }}" class="text-right">
                    {{ $summaryLabel }}:
                    {{ $summaryType === 'money' ? 'PHP '.number_format((float) $summaryValue, 2) : number_format((float) $summaryValue) }}
                </td>
            </tr></tfoot>
        @endif
    </table>

    <div class="section">
        <div class="section-title">5. Interpretation and Data Notes</div>
        <ul class="notes">@foreach($notes as $note)<li>{{ $note }}</li>@endforeach</ul>
        <div class="source-note"><strong>Source and limitation:</strong> {{ $sourceNote }}</div>
    </div>

    <table class="signoff"><tr>
        <td><div class="prepared-name">{{ $generatedBy }}</div><div class="signature-line"></div><div class="signature-label">Prepared by / Date</div></td>
        <td><div class="signature-line"></div><div class="signature-label">Reviewed by SMO In-Charge / Date</div></td>
        <td><div class="signature-line"></div><div class="signature-label">Noted by Authorized Official / Date</div></td>
    </tr></table>
</body>
</html>
