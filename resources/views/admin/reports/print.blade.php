<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — {{ $periodLabel }}</title>
    <style>
        @page { size:A4 landscape; margin:12mm; }
        * { box-sizing:border-box; }
        body { max-width:1400px; margin:24px auto; padding:0 20px; color:#24362b; background:#fff; font:14px/1.45 Arial,sans-serif; }
        .print-actions { display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom:24px; padding:12px 16px; border:1px solid #d5e2d9; border-radius:8px; background:#f4f8f5; }
        button { padding:8px 20px; border:0; border-radius:6px; color:#fff; background:#1b6843; cursor:pointer; }
        header { padding-bottom:16px; margin-bottom:16px; border-bottom:2px solid #1b6843; }
        .institution { margin:0 0 8px; color:#55715e; font-weight:bold; }
        h1 { margin:0 0 6px; font-size:24px; }
        p { margin:4px 0; }
        .meta { color:#65786b; font-size:12px; }
        table { width:100%; border-collapse:collapse; font-size:12px; }
        thead { display:table-header-group; }
        th,td { padding:6px 8px; border:1px solid #d5dfd7; text-align:left; overflow-wrap:anywhere; }
        th { color:#36513f; background:#eef4ef; }
        tr { break-inside:avoid; }
        .numeric { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
        .total { margin-top:16px; text-align:right; font-weight:bold; }
        footer { margin-top:24px; color:#6a7c6f; font-size:12px; }
        @media print { body { max-width:none; margin:0; padding:0; }.print-actions { display:none; } table { font-size:10px; } }
    </style>
</head>
<body>
    <div class="print-actions"><span>{{ number_format($rows->count()) }} rows · {{ $periodLabel }}</span><button type="button" onclick="window.print()">Print report</button></div>
    <header><p class="institution">Holy Trinity College of General Santos City · Supply Management Office</p><h1>{{ $title }}</h1><p>{{ $periodLabel }}</p>@if($appliedFilters)<p>{{ $appliedFilters }}</p>@endif<p class="meta">Prepared by {{ $generatedBy }} · {{ $generatedDate }}</p></header>
    <table><thead><tr>@foreach($columns as $column)<th class="{{ in_array($column['type'], ['money', 'number']) ? 'numeric' : '' }}">{{ $column['label'] }}</th>@endforeach</tr></thead><tbody>
        @forelse($rows as $row)<tr>@foreach($columns as $column)<td class="{{ in_array($column['type'], ['money', 'number']) ? 'numeric' : '' }}">@if($column['type'] === 'money')PHP {{ number_format((float) ($row[$column['key']] ?? 0), 2) }}@elseif($column['type'] === 'number'){{ number_format((float) ($row[$column['key']] ?? 0)) }}@else{{ $row[$column['key']] ?? '—' }}@endif</td>@endforeach</tr>@empty<tr><td colspan="{{ count($columns) }}">No records match the selected filters.</td></tr>@endforelse
    </tbody></table>
    <p class="total">{{ $summaryLabel }}: {{ $summaryType === 'money' ? 'PHP '.number_format($summaryValue, 2) : number_format($summaryValue) }}</p>
    @if($basisLabel)<footer>{{ $basisLabel }}</footer>@endif
    <script>window.addEventListener('load', () => window.print(), {once:true});</script>
</body>
</html>
