<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
<<<<<<< ours
        @page { margin: 24px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 5px; vertical-align: top; }
        th { background: #f1f5f9; color: #334155; font-size: 7px; text-transform: uppercase; }
        .header td, .summary td { border: 0; }
        .title { color: #185b3b; font-size: 14px; font-weight: bold; }
        .subtitle { font-size: 10px; font-weight: bold; margin-top: 3px; }
        .muted { color: #64748b; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary { margin: 10px 0; background: #f8fafc; }
        .summary td { padding: 7px; }
        .footer { position: fixed; right: 0; bottom: -10px; left: 0; color: #64748b; text-align: center; font-size: 7px; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td width="75%">
                <div class="title">HOLY TRINITY COLLEGE OF GENERAL SANTOS CITY</div>
                <div class="subtitle">SUPPLY MANAGEMENT OFFICE — {{ strtoupper($title) }}</div>
                <div class="muted">Reporting period: {{ $periodLabel }}</div>
            </td>
            <td width="25%" class="text-right">
                <strong>Generated:</strong> {{ $generatedDate }}<br>
                <strong>Prepared by:</strong> {{ $generatedBy }}
=======
        @page { margin: 25px 28px 42px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #26332b; font-family: DejaVu Sans, sans-serif; font-size: 7.5px; line-height: 1.35; }
        table { width: 100%; border-collapse: collapse; }
        .no-border td { border: 0; }
        .report-header { padding-bottom: 10px; border-bottom: 2px solid #185b3b; }
        .institution { color: #185b3b; font-size: 12px; font-weight: bold; letter-spacing: .4px; }
        .office { margin-top: 2px; color: #526159; font-size: 7px; font-weight: bold; letter-spacing: .8px; text-transform: uppercase; }
        .report-title { margin: 11px 0 2px; color: #1f2d25; font-size: 16px; font-weight: bold; }
        .report-period { color: #65736a; font-size: 8px; }
        .control-box { width: 220px; padding-left: 15px; vertical-align: top; }
        .control-box table { border: 1px solid #cfd9d2; }
        .control-box td { padding: 4px 6px; border-bottom: 1px solid #e0e7e2; }
        .control-box tr:last-child td { border-bottom: 0; }
        .control-label { width: 76px; color: #718078; font-size: 6px; font-weight: bold; letter-spacing: .3px; text-transform: uppercase; }
        .control-value { color: #2c3a31; font-size: 6.8px; font-weight: bold; }
        .section { margin-top: 11px; page-break-inside: avoid; }
        .section-title { margin: 0 0 5px; padding-bottom: 3px; border-bottom: 1px solid #ccd7d0; color: #185b3b; font-size: 8px; font-weight: bold; letter-spacing: .6px; text-transform: uppercase; }
        .purpose { margin: 0; color: #48574e; font-size: 7.2px; }
        .scope-table { margin-top: 6px; border: 1px solid #d7e0da; table-layout: fixed; }
        .scope-table td { padding: 5px 7px; border-right: 1px solid #e2e8e4; vertical-align: top; }
        .scope-table td:last-child { border-right: 0; }
        .scope-table span { display: block; margin-bottom: 2px; color: #748178; font-size: 5.8px; font-weight: bold; letter-spacing: .4px; text-transform: uppercase; }
        .scope-table strong { color: #2d3b32; font-size: 7px; }
        .filter-line { margin-top: 5px; padding: 5px 7px; border-left: 3px solid #739c82; background: #f5f8f6; color: #536159; }
        .filter-line strong { color: #33443a; }
        .metric-table { table-layout: fixed; }
        .metric-table td { padding-right: 6px; vertical-align: top; }
        .metric-table td:last-child { padding-right: 0; }
        .metric { min-height: 42px; padding: 7px 8px; border: 1px solid #d7e0da; background: #f8faf9; }
        .metric span { display: block; color: #758279; font-size: 5.6px; font-weight: bold; letter-spacing: .35px; text-transform: uppercase; }
        .metric strong { display: block; margin-top: 3px; color: #185b3b; font-size: 11px; }
        .detail-title { margin: 12px 0 5px; color: #185b3b; font-size: 8px; font-weight: bold; letter-spacing: .6px; text-transform: uppercase; }
        .data-table { table-layout: auto; }
        .data-table thead { display: table-header-group; }
        .data-table tr { page-break-inside: avoid; }
        .data-table th { padding: 5px 4px; border: 1px solid #bccac1; color: #fff; background: #315f43; font-size: 5.7px; font-weight: bold; letter-spacing: .25px; text-align: left; text-transform: uppercase; vertical-align: middle; }
        .data-table td { padding: 4px; border: 1px solid #d6dfd9; color: #344139; font-size: 6.2px; vertical-align: top; overflow-wrap: break-word; }
        .data-table tbody tr:nth-child(even) td { background: #f7f9f8; }
        .data-table tfoot td { padding: 5px 4px; border: 1px solid #b9c7be; background: #edf3ef; font-weight: bold; }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .empty { padding: 22px !important; color: #708078 !important; text-align: center; }
        .notes { margin: 0; padding-left: 14px; color: #536159; }
        .notes li { margin-bottom: 3px; }
        .source-note { margin-top: 6px; padding: 6px 8px; border: 1px solid #dce4df; background: #fafcfb; color: #66746c; font-size: 6.3px; }
        .signoff { margin-top: 22px; page-break-inside: avoid; table-layout: fixed; }
        .signoff td { width: 33.33%; padding-right: 28px; border: 0; vertical-align: bottom; }
        .signoff td:last-child { padding-right: 0; }
        .signature-line { padding-top: 18px; border-bottom: 1px solid #536159; }
        .signature-label { margin-top: 3px; color: #728078; font-size: 5.8px; text-align: center; text-transform: uppercase; }
        .prepared-name { text-align: center; font-size: 6.8px; font-weight: bold; }
        .footer { position: fixed; right: 0; bottom: -27px; left: 0; padding-top: 5px; border-top: 1px solid #cfd8d2; color: #748178; font-size: 5.8px; }
        .footer-left { float: left; }
        .footer-right { float: right; }
        .page-number::after { content: counter(page); }
    </style>
</head>
<body>
    <div class="footer">
        <span class="footer-left">HTC Supply Management System | {{ $classification }}</span>
        <span class="footer-right">{{ $reportReference }} | Page <span class="page-number"></span></span>
    </div>

    <table class="no-border report-header">
        <tr>
            <td>
                <div class="institution">HOLY TRINITY COLLEGE OF GENERAL SANTOS CITY</div>
                <div class="office">Supply Management Office</div>
                <div class="report-title">{{ $title }}</div>
                <div class="report-period">Reporting period: {{ $periodLabel }}</div>
            </td>
            <td class="control-box">
                <table>
                    <tr><td class="control-label">Report reference</td><td class="control-value">{{ $reportReference }}</td></tr>
                    <tr><td class="control-label">Generated</td><td class="control-value">{{ $generatedDate }}</td></tr>
                    <tr><td class="control-label">Prepared by</td><td class="control-value">{{ $generatedBy }}</td></tr>
                    <tr><td class="control-label">Classification</td><td class="control-value">{{ $classification }}</td></tr>
                </table>
>>>>>>> theirs
            </td>
        </tr>
    </table>

<<<<<<< ours
    <table class="summary">
        <tr>
            <td><strong>Matching records:</strong> {{ number_format($rows->count()) }}</td>
            <td class="text-right">
                <strong>{{ $summaryLabel }}:</strong>
                @if($summaryType === 'money')
                    ₱{{ number_format($summaryValue, 2) }}
                @else
                    {{ number_format($summaryValue) }}
                @endif
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                @foreach($columns as $column)
                    <th class="{{ in_array($column['type'], ['money', 'number']) ? 'text-right' : '' }}">
                        {{ $column['label'] }}
                    </th>
=======
    <div class="section">
        <div class="section-title">1. Report Scope</div>
        <p class="purpose">{{ $purpose }}</p>
        <table class="scope-table">
            <tr>
                <td><span>Coverage</span><strong>{{ $periodLabel }}</strong></td>
                <td><span>Matching records</span><strong>{{ number_format($rows->count()) }}</strong></td>
                <td><span>Data as of</span><strong>{{ $dataAsOf }}</strong></td>
                <td><span>Report basis</span><strong>{{ $reportBasis }}</strong></td>
            </tr>
        </table>
        <div class="filter-line"><strong>Applied filters:</strong> {{ $appliedFilters ?: 'No additional filters; all records within the stated coverage are included.' }}</div>
    </div>

    <div class="section">
        <div class="section-title">2. Executive Summary</div>
        <table class="metric-table">
            <tr>
                @foreach($metrics as $metric)
                    <td>
                        <div class="metric">
                            <span>{{ $metric['label'] }}</span>
                            <strong>
                                @if(($metric['type'] ?? 'number') === 'money')
                                    PHP {{ number_format((float) $metric['value'], 2) }}
                                @elseif(($metric['type'] ?? 'number') === 'decimal')
                                    {{ number_format((float) $metric['value'], 2) }}
                                @else
                                    {{ is_numeric($metric['value']) ? number_format((float) $metric['value']) : $metric['value'] }}
                                @endif
                            </strong>
                        </div>
                    </td>
                @endforeach
            </tr>
        </table>
    </div>

    <div class="detail-title">3. Detailed Records</div>
    <table class="data-table">
        <thead>
            <tr>
                @foreach($columns as $column)
                    <th class="{{ in_array($column['type'], ['money', 'number', 'decimal']) ? 'text-right' : '' }}" @if(!empty($column['width'])) style="width: {{ $column['width'] }}" @endif>{{ $column['label'] }}</th>
>>>>>>> theirs
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($columns as $column)
<<<<<<< ours
                        <td class="{{ in_array($column['type'], ['money', 'number']) ? 'text-right' : '' }}">
                            @if($column['type'] === 'money')
                                ₱{{ number_format((float) ($row[$column['key']] ?? 0), 2) }}
                            @elseif($column['type'] === 'number')
                                {{ number_format((float) ($row[$column['key']] ?? 0)) }}
                            @else
                                {{ $row[$column['key']] ?? '—' }}
=======
                        <td class="{{ in_array($column['type'], ['money', 'number', 'decimal']) ? 'text-right' : '' }}">
                            @php($value = $row[$column['key']] ?? null)
                            @if($column['type'] === 'money')
                                PHP {{ number_format((float) $value, 2) }}
                            @elseif($column['type'] === 'number')
                                {{ number_format((float) $value) }}
                            @elseif($column['type'] === 'decimal')
                                {{ number_format((float) $value, 2) }}
                            @else
                                {{ filled($value) ? $value : '—' }}
>>>>>>> theirs
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
<<<<<<< ours
                <tr>
                    <td colspan="{{ count($columns) }}" class="text-center">No records matched the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Official SMO institutional report • Generated by the HTC Supply Management System</div>
=======
                <tr><td colspan="{{ count($columns) }}" class="empty">No records matched the selected period and filters.</td></tr>
            @endforelse
        </tbody>
        @if($rows->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="{{ max(1, count($columns) - 1) }}" class="text-right">{{ $summaryLabel }}</td>
                    <td class="text-right">{{ $summaryType === 'money' ? 'PHP '.number_format((float) $summaryValue, 2) : number_format((float) $summaryValue) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="section">
        <div class="section-title">4. Interpretation and Notes</div>
        <ul class="notes">
            @foreach($notes as $note)<li>{{ $note }}</li>@endforeach
        </ul>
        <div class="source-note"><strong>Data source and limitation:</strong> {{ $sourceNote }}</div>
    </div>

    <table class="signoff">
        <tr>
            <td><div class="prepared-name">{{ $generatedBy }}</div><div class="signature-line"></div><div class="signature-label">Prepared by / Date</div></td>
            <td><div class="signature-line"></div><div class="signature-label">Reviewed by SMO In-Charge / Date</div></td>
            <td><div class="signature-line"></div><div class="signature-label">Noted by Authorized Official / Date</div></td>
        </tr>
    </table>
>>>>>>> theirs
</body>
</html>
