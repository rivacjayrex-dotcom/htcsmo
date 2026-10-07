<x-app-layout>
    <x-slot name="header">
        {{ Auth::user()->role == 'smo' ? __('Release Queue') : __('Pending Request Approvals') }}
    </x-slot>

    <div class="container-fluid py-2 approval-workspace"
         x-data="approvalManager()"
         @if($focusedRequest) data-focused-request-id="{{ $focusedRequest->id }}" @endif>
        <!-- ALERTS -->
        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm mb-4 rounded-4 d-flex align-items-center">
                <i data-lucide="check-circle" class="me-2" style="width: 20px;"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger border-0 shadow-sm mb-4 rounded-4 d-flex align-items-center">
                <i data-lucide="alert-triangle" class="me-2" style="width: 20px;"></i>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <div class="card-header bg-white border-0 p-4 pb-2">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold mb-0">Requisition Queue</h5>
                        <p class="text-muted small mb-0">Monitor, evaluate, and process institutional procurement requests.</p>
                    </div>
                </div>

                <!-- TABS NAVIGATION -->
                <ul class="nav nav-pills flex-nowrap gap-2" id="approvalsTabs" role="tablist">
                    @if(Auth::user()->role == 'smo')
                        <li class="nav-item">
                            <button class="nav-link active rounded-pill px-4 fw-bold small text-uppercase" id="minor-tab" data-bs-toggle="pill" data-bs-target="#minor" type="button">
                                Minor Requests <span class="badge bg-white text-primary ms-2">{{ $pendingMinor->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link rounded-pill px-4 fw-bold small text-uppercase" id="major-tab" data-bs-toggle="pill" data-bs-target="#major" type="button">
                                Major Requests <span class="badge bg-white text-warning ms-2">{{ $pendingMajor->count() }}</span>
                            </button>
                        </li>
                    @else
                        <li class="nav-item">
                            <button class="nav-link active rounded-pill px-4 fw-bold small text-uppercase" id="pending-tab" data-bs-toggle="pill" data-bs-target="#pending" type="button">
                                Active Queue <span class="badge bg-white text-dark ms-2">{{ $pendingRequests->count() }}</span>
                            </button>
                        </li>
                    @endif

                    <!-- DEDICATED INQUIRY / OFFICE CLARIFICATION TAB -->
                    <li class="nav-item">
                        <button class="nav-link rounded-pill px-4 fw-bold small text-uppercase text-warning" id="clarification-tab" data-bs-toggle="pill" data-bs-target="#clarification" type="button">
                            <i data-lucide="help-circle" class="me-1" style="width: 14px; vertical-align: middle;"></i>
                            For Clarification / Inquiry
                            <span class="badge {{ $clarificationRequests->count() > 0 ? 'bg-warning text-dark' : 'bg-light text-muted' }} ms-2">
                                {{ $clarificationRequests->count() }}
                            </span>
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-0">
                <div class="tab-content">
                    @if(Auth::user()->role == 'smo')
                        <!-- TAB 1: MINOR (SMO ONLY) -->
                        <div class="tab-pane fade show active" id="minor">
                            @include('admin.partials.approvals-table', ['requests' => $pendingMinor])
                        </div>
                        <!-- TAB 2: MAJOR (SMO ONLY) -->
                        <div class="tab-pane fade" id="major">
                            @include('admin.partials.approvals-table', ['requests' => $pendingMajor])
                        </div>
                    @else
                        <!-- STANDARD VIEW FOR SIGNATORIES -->
                        <div class="tab-pane fade show active" id="pending">
                            @include('admin.partials.approvals-table', ['requests' => $pendingRequests])
                        </div>
                    @endif

                    <!-- TAB 3: FOR OFFICE CLARIFICATION (BOTH SMO & SIGNATORIES) -->
                    <div class="tab-pane fade" id="clarification">
                        @if($clarificationRequests->count() > 0)
                            <div class="p-3 bg-warning-subtle text-dark border-bottom small fw-semibold d-flex align-items-center">
                                <i data-lucide="alert-circle" class="me-2 text-warning" style="width: 18px;"></i>
                                The items below have been flagged for inquiry. The requestor and the SMO In-Charge have been asked to appear at the signatory's office for a consultation.
                            </div>
                        @endif
                        @include('admin.partials.approvals-table', ['requests' => $clarificationRequests])
                    </div>
                </div>
            </div>
        </div>

        <!-- REVIEW MODAL -->
        <div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true" aria-labelledby="reviewModalTitle">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden bg-white">
                    <div class="modal-header border-0 bg-light p-4">
                        <div>
                            <h5 class="modal-title fw-bold" id="reviewModalTitle">Review Requisition
                                <span class="text-success font-monospace" x-text="(selectedReq.request_type === 'major' ? 'MAJ' : 'MIN') + '-' + requestorDept + '-' + (selectedReq.request_type === 'major' ? '10' : '20') + String(selectedReq.id).padStart(6, '0')"></span>
                            </h5>
                            <span class="badge bg-dark rounded-pill px-3 mt-1" style="font-size: 9px;" x-text="(selectedReq.request_type || '').toUpperCase() + ' TIER'"></span>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body p-4">
                        <!-- REQUESTOR INFO -->
                        <div class="row mb-4 bg-light p-3 rounded-4 g-2">
                            <div class="col-md-6">
                                <small class="text-muted text-uppercase fw-bold" style="font-size: 9px;">Official Requestor</small>
                                <div class="fw-bold fs-6 text-dark" x-text="requestorName"></div>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <small class="text-muted text-uppercase fw-bold" style="font-size: 9px;">Department / Unit</small>
                                <div class="fw-bold fs-6 text-success" x-text="requestorDept"></div>
                            </div>
                        </div>

                        <!-- REQUESTOR'S STATED PURPOSE -->
                        <div class="approval-purpose mb-4">
                            <div class="approval-purpose-icon"><i data-lucide="message-square-text"></i></div>
                            <div class="flex-grow-1">
                                <small class="text-uppercase fw-bold">Reason for Request</small>
                                <p class="mb-0" x-text="selectedReq.purpose || 'No reason was recorded for this requisition.'"></p>
                            </div>
                        </div>

                        <!-- DYNAMIC TIMELINE (Alpine Real-Time for all Signatories) -->
                        <div class="workflow-stepper-scroll mb-4 px-2" tabindex="0" aria-label="Approval flow; scroll horizontally to see every step">
                            <div class="tracking-stepper">
                                <!-- Step 1 -->
                                <div class="step-item completed">
                                    <div class="step-icon"><i data-lucide="send"></i></div>
                                    <div class="step-label">Submitted</div>
                                </div>

                                <!-- Step 2: Dept Head -->
                                <div class="step-item"
                                    :class="{
                                        'completed': ['approved_dept', 'approved_vp', 'approved_provost', 'approved_president', 'released'].includes(selectedReq.status)
                                            || (selectedReq.status === 'for_clarification' && ['vp_finance', 'vp_admin', 'provost', 'president'].includes(questioningRole)),
                                        'questioning': selectedReq.status === 'for_clarification' && (!questioningRole || questioningRole === 'dept_head'),
                                        'active': selectedReq.status === 'pending'
                                    }">
                                    <div class="step-icon">
                                        <i :data-lucide="selectedReq.status === 'for_clarification' && (!questioningRole || questioningRole === 'dept_head') ? 'help-circle' : 'user-check'"></i>
                                    </div>
                                    <div class="step-label" x-text="selectedReq.status === 'for_clarification' && (!questioningRole || questioningRole === 'dept_head') ? 'Dept Head (Questioning)' : 'Dept Head'"></div>
                                </div>

                                <!-- Step 3: VP Finance / Admin -->
                                <div class="step-item"
                                    :class="{
                                        'completed': ['approved_vp', 'approved_provost', 'approved_president', 'released'].includes(selectedReq.status)
                                            || (selectedReq.status === 'for_clarification' && ['provost', 'president'].includes(questioningRole)),
                                        'questioning': selectedReq.status === 'for_clarification' && ['vp_finance', 'vp_admin'].includes(questioningRole),
                                        'active': selectedReq.status === 'approved_dept'
                                    }">
                                    <div class="step-icon">
                                        <i :data-lucide="selectedReq.status === 'for_clarification' && ['vp_finance', 'vp_admin'].includes(questioningRole) ? 'help-circle' : 'shield-check'"></i>
                                    </div>
                                    <div class="step-label" x-text="(selectedReq.request_type == 'minor' ? 'VP Finance' : 'VP Admin') + (selectedReq.status === 'for_clarification' && ['vp_finance', 'vp_admin'].includes(questioningRole) ? ' (Questioning)' : '')"></div>
                                </div>

                                <!-- Step 4: Provost (Major Only) -->
                                <template x-if="selectedReq.request_type == 'major'">
                                    <div class="step-item"
                                        :class="{
                                            'completed': ['approved_provost', 'approved_president', 'released'].includes(selectedReq.status)
                                                || (selectedReq.status === 'for_clarification' && questioningRole === 'president'),
                                            'questioning': selectedReq.status === 'for_clarification' && questioningRole === 'provost',
                                            'active': selectedReq.status === 'approved_vp'
                                        }">
                                        <div class="step-icon">
                                            <i :data-lucide="selectedReq.status === 'for_clarification' && questioningRole === 'provost' ? 'help-circle' : 'graduation-cap'"></i>
                                        </div>
                                        <div class="step-label" x-text="'Provost' + (selectedReq.status === 'for_clarification' && questioningRole === 'provost' ? ' (Questioning)' : '')"></div>
                                    </div>
                                </template>

                                <!-- Step 5: President (Major Only) -->
                                <template x-if="selectedReq.request_type == 'major'">
                                    <div class="step-item"
                                        :class="{
                                            'completed': ['approved_president', 'released'].includes(selectedReq.status),
                                            'questioning': selectedReq.status === 'for_clarification' && questioningRole === 'president',
                                            'active': selectedReq.status === 'approved_provost'
                                        }">
                                        <div class="step-icon">
                                            <i :data-lucide="selectedReq.status === 'for_clarification' && questioningRole === 'president' ? 'help-circle' : 'award'"></i>
                                        </div>
                                        <div class="step-label" x-text="'President' + (selectedReq.status === 'for_clarification' && questioningRole === 'president' ? ' (Questioning)' : '')"></div>
                                    </div>
                                </template>

                                <!-- Step 6: Released -->
                                <div class="step-item" :class="selectedReq.status == 'released' ? 'completed' : ''">
                                    <div class="step-icon"><i data-lucide="package"></i></div>
                                    <div class="step-label">Released</div>
                                </div>
                            </div>
                        </div>

                        <!-- REQUESTED ITEMS TABLE -->
                        <h6 class="fw-bold mb-3 text-dark">Requested Item Breakdown</h6>
                        <div class="table-responsive border rounded-3 mb-4">
                            <table class="table table-sm mb-0 workflow-responsive-table approval-items-table">
                                <thead class="bg-light text-muted small text-uppercase">
                                    <tr>
                                        <th class="ps-3 py-2">Item Name & Specs</th>
                                        <th class="py-2 text-center">Qty</th>
                                        <th class="text-end pe-3 py-2">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="item in selectedItems" :key="item.id">
                                        <tr>
                                            <td class="ps-3 py-2" data-label="Item">
                                                <div class="fw-bold text-dark" x-text="item.item_name"></div>
                                                <small class="text-muted" x-text="item.specifications"></small>
                                            </td>
                                            <td class="text-center py-2" data-label="Quantity" x-text="item.quantity + ' ' + (item.unit || 'pc')"></td>
                                            <td class="text-end pe-3 py-2 fw-bold text-dark" data-label="Subtotal" x-text="'₱' + parseFloat(item.subtotal).toLocaleString(undefined, {minimumFractionDigits:2})"></td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tfoot class="bg-light fw-bold">
                                    <tr>
                                        <td colspan="2" class="ps-3 py-2 text-uppercase small text-dark">Grand Total</td>
                                        <td class="text-end pe-3 py-2 text-success h6 mb-0 fw-black" x-text="'₱' + parseFloat(selectedReq.grand_total || 0).toLocaleString(undefined, {minimumFractionDigits:2})"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- ACTION FORMS -->
                        <div class="mt-4">
                            @if(Auth::user()->role == 'smo')
                                <!-- SMO FULFILLMENT FORM -->
                                <template x-if="selectedReq.status !== 'for_clarification'">
                                    <form :action="'{{ url('/admin/requests') }}/' + selectedReq.id + '/release'" method="POST" @submit="handleReleaseSubmit($event)">
                                        @csrf
                                        <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center">
                                            <i data-lucide="package-check" class="me-2" style="width:18px;"></i> CONFIRM RELEASE
                                        </button>
                                    </form>
                                </template>
                            @else
                                <!-- SIGNATORY 3-BUTTON DECISION FORM -->
                                <!-- SIGNATORY DECISION FORM -->
                                <form :action="'{{ url('/admin/requests') }}/' + selectedReq.id + '/status'" method="POST">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-muted text-uppercase tracking-widest" style="font-size: 9px;">
                                            Signatory Remarks / Decision Notes
                                        </label>
                                        <textarea name="remarks" class="form-control border-0 bg-light rounded-3 shadow-none" rows="2" placeholder="State reason if rejecting or requesting office inquiry..."></textarea>
                                    </div>

                                    <div class="row g-2">
                                        <!-- 1. REJECT BUTTON -->
                                        <div :class="selectedReq.status === 'for_clarification' ? 'col-6' : 'col-md-4'">
                                            <button name="status" value="rejected" type="submit" class="btn btn-outline-danger fw-bold w-100 py-2 rounded-3">
                                                <i data-lucide="x-circle" class="me-1" style="width:14px; vertical-align: middle;"></i> Reject
                                            </button>
                                        </div>

                                        <!-- 2. OFFICE INQUIRY (Hides automatically if ALREADY under clarification) -->
                                        <div class="col-md-4" x-show="selectedReq.status !== 'for_clarification'">
                                            <button name="status" value="clarification" type="submit" class="btn btn-warning fw-bold text-dark w-100 py-2 rounded-3 shadow-sm">
                                                <i data-lucide="help-circle" class="me-1" style="width:14px; vertical-align: middle;"></i> Office Inquiry
                                            </button>
                                        </div>

                                        <!-- 3. APPROVE BUTTON -->
                                        <div :class="selectedReq.status === 'for_clarification' ? 'col-6' : 'col-md-4'">
                                            <button name="status" value="approved" type="submit" class="btn btn-success fw-bold w-100 py-2 shadow-sm rounded-3">
                                                <i data-lucide="check-circle" class="me-1" style="width:14px; vertical-align: middle;"></i>
                                                <span x-text="selectedReq.status === 'for_clarification' ? 'Approve (Satisfied)' : 'Approve'"></span>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(Auth::user()->role == 'smo')
            <!-- RELEASE CONFIRMATION MODAL -->
            <div class="modal fade" id="releaseConfirmationModal" tabindex="-1" aria-hidden="true" aria-labelledby="releaseConfirmationTitle">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                        <div class="modal-body p-4 p-md-5 text-center">
                            <div class="release-confirmation-icon mx-auto mb-3" aria-hidden="true">
                                <i data-lucide="package-check"></i>
                            </div>
                            <h5 class="fw-bold text-dark mb-2" id="releaseConfirmationTitle">Confirm supply release?</h5>
                            <p class="text-muted small mb-3">
                                You are about to release
                                <strong class="text-dark" x-text="selectedReq.tracking_code || ('Requisition #' + selectedReq.id)"></strong>
                                for <strong class="text-dark" x-text="requestorName"></strong>.
                            </p>
                            <div class="alert alert-warning border-0 rounded-3 small text-start mb-4">
                                <i data-lucide="triangle-alert" class="me-2" style="width:16px; vertical-align:middle;"></i>
                                Inventory quantities will be deducted and this requisition will be marked as released.
                            </div>
                            <div class="d-flex flex-column-reverse flex-sm-row gap-2 justify-content-center">
                                <button type="button" class="btn btn-light border rounded-3 px-4" @click="cancelReleaseConfirmation()">
                                    Cancel
                                </button>
                                <button type="button" class="btn btn-primary rounded-3 px-4 fw-bold" @click="submitConfirmedRelease()">
                                    <i data-lucide="package-check" class="me-2" style="width:16px;"></i>
                                    Yes, release supplies
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <style>
        .approval-purpose { display: flex; align-items: flex-start; gap: .85rem; padding: 1rem; border: 1px solid #dce9e1; border-radius: 14px; background: #f5faf7; }
        .approval-purpose-icon { width: 36px; height: 36px; flex: 0 0 36px; display: grid; place-items: center; border-radius: 10px; background: #dff2e6; color: var(--htc-green); }
        .approval-purpose-icon svg { width: 17px; height: 17px; }
        .approval-purpose small { display: block; margin-bottom: .25rem; color: #718078; font-size: 9px; letter-spacing: .08em; }
        .approval-purpose p { color: #26382e; font-size: .82rem; line-height: 1.6; white-space: pre-line; overflow-wrap: anywhere; }
        .release-confirmation-icon { width: 58px; height: 58px; display: grid; place-items: center; border-radius: 18px; background: #e3f4e9; color: #187044; }
        .release-confirmation-icon svg { width: 28px; height: 28px; }
    </style>

    <script>
        const focusedApprovalRequest = {{ Illuminate\Support\Js::from($focusedRequest?->toArray()) }};

        function approvalManager() {
            return {
                selectedReq: {},
                selectedItems: [],
                requestorName: '',
                requestorDept: '',
                questioningRole: null,
                releaseForm: null,
                releaseConfirmed: false,

                init() {
                    if (!focusedApprovalRequest) return;

                    this.$nextTick(() => {
                        if (focusedApprovalRequest.request_type === 'major') {
                            const majorTab = document.getElementById('major-tab');
                            if (majorTab) bootstrap.Tab.getOrCreateInstance(majorTab).show();
                        }

                        this.openDetails(
                            focusedApprovalRequest,
                            focusedApprovalRequest.items || [],
                            focusedApprovalRequest.user?.name || 'Requestor',
                            focusedApprovalRequest.user?.department || 'General'
                        );
                    });
                },

                openDetails(req, items, name, dept) {
                    this.selectedReq = req;
                    this.selectedItems = items;
                    this.requestorName = name;
                    this.requestorDept = dept;
                    this.releaseForm = null;
                    this.releaseConfirmed = false;

                    // Identify the signatory that initiated the clarification from logs
                    if (req.status === 'for_clarification' && req.logs && req.logs.length > 0) {
                        const lastLog = [...req.logs]
                            .sort((a, b) => Number(b.id) - Number(a.id))
                            .find(log => log.action.includes('Clarification'));
                        this.questioningRole = lastLog ? lastLog.role : null;
                    } else {
                        this.questioningRole = null;
                    }

                    const modalEl = document.getElementById('reviewModal');
                    const modal = new bootstrap.Modal(modalEl);
                    modal.show();

                    // Re-render icons cleanly
                    setTimeout(() => lucide.createIcons(), 100);
                    setTimeout(() => lucide.createIcons(), 350);
                },

                handleReleaseSubmit(event) {
                    if (this.releaseConfirmed) return;

                    event.preventDefault();
                    this.releaseForm = event.currentTarget;

                    const reviewElement = document.getElementById('reviewModal');
                    const confirmationElement = document.getElementById('releaseConfirmationModal');
                    if (!reviewElement || !confirmationElement) return;

                    reviewElement.addEventListener('hidden.bs.modal', () => {
                        bootstrap.Modal.getOrCreateInstance(confirmationElement).show();
                        setTimeout(() => lucide.createIcons(), 100);
                    }, {once: true});

                    bootstrap.Modal.getOrCreateInstance(reviewElement).hide();
                },

                cancelReleaseConfirmation() {
                    const reviewElement = document.getElementById('reviewModal');
                    const confirmationElement = document.getElementById('releaseConfirmationModal');
                    if (!reviewElement || !confirmationElement) return;

                    confirmationElement.addEventListener('hidden.bs.modal', () => {
                        bootstrap.Modal.getOrCreateInstance(reviewElement).show();
                    }, {once: true});

                    bootstrap.Modal.getOrCreateInstance(confirmationElement).hide();
                },

                submitConfirmedRelease() {
                    if (!this.releaseForm || this.releaseConfirmed) return;

                    this.releaseConfirmed = true;
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('releaseConfirmationModal')).hide();
                    this.$nextTick(() => this.releaseForm.requestSubmit());
                }
            }
        }
    </script>
</x-app-layout>
