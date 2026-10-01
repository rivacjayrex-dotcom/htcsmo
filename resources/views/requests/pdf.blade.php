<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Requisition {{ $request->tracking_code }}</title>
    <style>
        @page { margin: 30px 32px 45px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #26342c; font-family: DejaVu Sans, sans-serif; font-size: 8px; line-height: 1.35; }
        table { width: 100%; border-collapse: collapse; }
        .no-border td { border: 0; }
        .header { padding-bottom: 10px; border-bottom: 2px solid #185b3b; }
        .logo-cell { width: 62px; padding-right: 10px; vertical-align: top; }
        .logo { width: 52px; height: 52px; }
        .institution { color: #185b3b; font-size: 12px; font-weight: bold; }
        .office { color: #68776e; font-size: 6.5px; font-weight: bold; letter-spacing: .8px; text-transform: uppercase; }
        .title { margin-top: 8px; color: #1f2d25; font-size: 15px; font-weight: bold; }
        .subtitle { color: #6c7971; font-size: 7px; }
        .control { width: 190px; padding-left: 12px; vertical-align: top; }
        .control table { border: 1px solid #cad6ce; }
        .control td { padding: 4px 5px; border-bottom: 1px solid #e0e7e2; }
        .control tr:last-child td { border-bottom: 0; }
        .label { width: 65px; color: #75827a; font-size: 5.5px; font-weight: bold; text-transform: uppercase; }
        .value { color: #28362d; font-size: 6.5px; font-weight: bold; }
        .status { display: inline-block; padding: 2px 5px; color: #185b3b; background: #e8f3ec; font-weight: bold; text-transform: uppercase; }
        .section { margin-top: 11px; }
        .keep { page-break-inside: avoid; }
        .section-title { margin-bottom: 5px; padding: 4px 6px; border-left: 3px solid #185b3b; color: #185b3b; background: #f2f7f4; font-size: 7px; font-weight: bold; letter-spacing: .55px; text-transform: uppercase; }
        .info-table { border: 1px solid #d6e0d9; table-layout: fixed; }
        .info-table td { padding: 5px 7px; border-right: 1px solid #e0e7e2; border-bottom: 1px solid #e0e7e2; vertical-align: top; }
        .info-table tr:last-child td { border-bottom: 0; }
        .info-table td:last-child { border-right: 0; }
        .info-table span { display: block; margin-bottom: 2px; color: #79867e; font-size: 5.5px; font-weight: bold; text-transform: uppercase; }
        .info-table strong { color: #2c3a31; font-size: 7px; }
        .purpose { min-height: 30px; padding: 7px; border: 1px solid #d6e0d9; color: #3f4e45; }
        .items thead { display: table-header-group; }
        .items tr { page-break-inside: avoid; }
        .items th { padding: 5px 4px; border: 1px solid #b6c7bc; color: #fff; background: #315f43; font-size: 5.8px; letter-spacing: .2px; text-align: left; text-transform: uppercase; }
        .items td { padding: 5px 4px; border: 1px solid #d5dfd8; vertical-align: top; }
        .items tbody tr:nth-child(even) td { background: #f8faf9; }
        .items tfoot td { padding: 5px 4px; border: 1px solid #b9c8be; background: #edf4ef; font-weight: bold; }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .muted { color: #718078; }
        .timeline { table-layout: fixed; }
        .timeline th { padding: 4px; border: 1px solid #c1d0c6; color: #445249; background: #edf3ef; font-size: 5.7px; text-align: left; text-transform: uppercase; }
        .timeline td { padding: 5px 4px; border: 1px solid #d8e1db; vertical-align: top; }
        .signed { color: #185b3b; font-weight: bold; }
        .unsigned { color: #7b6750; }
        .verification { font-family: DejaVu Sans Mono, monospace; font-size: 6px; overflow-wrap: anywhere; }
        .attestation { margin-top: 10px; padding: 7px 8px; border: 1px solid #d8e1db; background: #fafcfb; color: #65736a; font-size: 6.5px; }
        .footer { position: fixed; right: 0; bottom: -28px; left: 0; padding-top: 5px; border-top: 1px solid #d0d9d3; color: #75827a; font-size: 5.8px; }
        .footer-left { float: left; }
        .footer-right { float: right; }
        .page-number:after { content: counter(page); }
    </style>
</head>
<body>
    @php
        $statusLabel = ucwords(str_replace('_', ' ', $request->status));
        $documentId = $request->request_type === 'major' ? 'QR-HTSM-040' : 'QR-HTSM-041';
        $totalQuantity = $request->items->sum('quantity');
        $signedLogs = $request->logs->filter(fn ($log) => $log->digitalSignature);
    @endphp

    <div class="footer">
        <span class="footer-left">{{ $documentId }} | Official Supply Management Office Record</span>
        <span class="footer-right">{{ $request->tracking_code }} | Page <span class="page-number"></span></span>
    </div>

    <table class="no-border header">
        <tr>
            <td class="logo-cell">
                @if(file_exists(public_path('images/android-chrome-512x5122.png')))
                    <img class="logo" src="{{ public_path('images/android-chrome-512x5122.png') }}" alt="HTC">
                @endif
            </td>
            <td>
                <div class="institution">HOLY TRINITY COLLEGE OF GENERAL SANTOS CITY</div>
                <div class="office">Supply Management Office</div>
                <div class="title">Supply and Equipment Requisition</div>
                <div class="subtitle">{{ strtoupper($request->request_type) }} request | {{ $request->request_type === 'major' ? 'Above PHP 1,000.00' : 'Not more than PHP 1,000.00' }}</div>
            </td>
            <td class="control">
                <table>
                    <tr><td class="label">Control No.</td><td class="value">{{ $request->tracking_code }}</td></tr>
                    <tr><td class="label">Submitted</td><td class="value">{{ $request->created_at->format('M d, Y - h:i A') }}</td></tr>
                    <tr><td class="label">Status</td><td class="value"><span class="status">{{ $statusLabel }}</span></td></tr>
                    <tr><td class="label">Document ID</td><td class="value">{{ $documentId }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section keep">
        <div class="section-title">1. Request Information</div>
        <table class="info-table">
            <tr>
                <td><span>Requestor</span><strong>{{ $request->user?->name ?? 'Unknown user' }}</strong></td>
                <td><span>Institution ID</span><strong>{{ $request->user?->school_id ?? '-' }}</strong></td>
                <td><span>Department</span><strong>{{ $request->user?->department ?: 'Unassigned' }}</strong></td>
                <td><span>Request Tier</span><strong>{{ strtoupper($request->request_type) }}</strong></td>
            </tr>
            <tr>
                <td><span>Line Items</span><strong>{{ number_format($request->items->count()) }}</strong></td>
                <td><span>Total Quantity</span><strong>{{ number_format($totalQuantity) }}</strong></td>
                <td><span>Last Updated</span><strong>{{ $request->updated_at->format('M d, Y - h:i A') }}</strong></td>
                <td><span>Total Value</span><strong>PHP {{ number_format((float) $request->grand_total, 2) }}</strong></td>
            </tr>
        </table>
        <div class="section-title" style="margin-top: 7px;">Purpose / Justification</div>
        <div class="purpose">{{ $request->purpose ?: 'No purpose or justification was recorded.' }}</div>
        @if($request->remarks)
            <div class="section-title" style="margin-top: 7px;">Latest Request Remark</div>
            <div class="purpose">{{ $request->remarks }}</div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">2. Requested Items and Cost Details</div>
        <table class="items">
            <thead><tr>
                <th style="width: 5%;" class="text-center">No.</th>
                <th style="width: 18%;">Item</th>
                <th>Specifications</th>
                <th style="width: 8%;" class="text-right">Qty</th>
                <th style="width: 9%;">Unit</th>
                <th style="width: 13%;" class="text-right">Unit Price</th>
                <th style="width: 14%;" class="text-right">Line Total</th>
            </tr></thead>
            <tbody>
                @forelse($request->items as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td><strong>{{ $item->item_name }}</strong></td>
                        <td>{{ $item->specifications ?: 'Not specified' }}</td>
                        <td class="text-right">{{ number_format($item->quantity) }}</td>
                        <td>{{ $item->unit ?: '-' }}</td>
                        <td class="text-right">PHP {{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="text-right">PHP {{ number_format((float) $item->subtotal, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center muted">No item lines are recorded for this requisition.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr><td colspan="3" class="text-right">Totals</td><td class="text-right">{{ number_format($totalQuantity) }}</td><td colspan="2"></td><td class="text-right">PHP {{ number_format((float) $request->grand_total, 2) }}</td></tr>
            </tfoot>
        </table>
    </div>

    <div class="section">
        <div class="section-title">3. Approval, Clarification, and Fulfillment History</div>
        <table class="timeline">
            <thead><tr>
                <th style="width: 16%;">Date and Time</th>
                <th style="width: 15%;">Acting Role</th>
                <th style="width: 25%;">Action</th>
                <th>Remarks</th>
                <th style="width: 17%;">Signature Status</th>
            </tr></thead>
            <tbody>
                <tr>
                    <td>{{ $request->created_at->format('M d, Y - h:i A') }}</td>
                    <td>Requestor</td>
                    <td>Requisition submitted</td>
                    <td>{{ $request->purpose ?: '-' }}</td>
                    <td class="signed">System-authenticated</td>
                </tr>
                @forelse($request->logs->sortBy('created_at') as $log)
                    <tr>
                        <td>{{ $log->created_at->format('M d, Y - h:i A') }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $log->role)) }}</td>
                        <td>{{ $log->action }}</td>
                        <td>{{ $log->remarks ?: '-' }}</td>
                        <td class="{{ $log->digitalSignature ? 'signed' : 'unsigned' }}">
                            {{ $log->digitalSignature ? 'Digitally signed' : 'Audit log only' }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center muted">No approval or fulfillment action has been recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">4. Digital Signature Verification</div>
        <table class="timeline">
            <thead><tr>
                <th style="width: 20%;">Signer</th>
                <th style="width: 19%;">Official Role</th>
                <th style="width: 18%;">Signed At</th>
                <th style="width: 15%;">IP Address</th>
                <th>SHA-256 Verification Token</th>
            </tr></thead>
            <tbody>
                @forelse($signedLogs as $log)
                    <tr>
                        <td><strong>{{ $log->digitalSignature->signer_name }}</strong></td>
                        <td>{{ $log->digitalSignature->signer_role }}</td>
                        <td>{{ $log->digitalSignature->signed_at->format('M d, Y - h:i A') }}</td>
                        <td>{{ $log->digitalSignature->ip_address }}</td>
                        <td class="verification">{{ $log->digitalSignature->signature_token }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center muted">No cryptographic digital signatures are attached to this requisition yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="attestation">
            This PDF is a system-generated record of the requisition and its recorded workflow history as of {{ now()->format('F d, Y - h:i A') }}. Verify approval claims against the signature token and the source record in the HTC Supply Management System.
        </div>
    </div>
</body>
</html>
