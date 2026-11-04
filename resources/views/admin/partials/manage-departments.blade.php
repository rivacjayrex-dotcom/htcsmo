<div class="modal fade" id="manageDepartmentsModal" tabindex="-1" aria-hidden="true" aria-labelledby="manageDepartmentsTitle"
    @if($errors->getBag('departmentManagement')->any()) x-init="$nextTick(() => bootstrap.Modal.getOrCreateInstance($el).show())" @endif>
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div><h5 class="modal-title fw-bold" id="manageDepartmentsTitle">Manage departments</h5><p class="text-muted small mb-0 mt-2">Edit names and codes, or remove departments without assigned personnel.</p></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body department-management-body">
                @if($errors->getBag('departmentManagement')->any())
                    <div class="alert alert-warning" role="alert"><strong>Department changes were not saved.</strong><ul class="mb-0 mt-2">@foreach($errors->getBag('departmentManagement')->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                <div class="department-list">
                    @forelse($departments as $department)
                        @php($isRetry = (int) old('editing_department_id') === $department->id && $errors->getBag('departmentManagement')->any())
                        <article class="department-entry" data-department-id="{{ $department->id }}" x-data="{ editing: {{ $isRetry ? 'true' : 'false' }} }">
                            <div class="department-entry-header">
                                <div class="department-entry-copy"><strong>{{ $department->dept_code }}</strong><span>{{ $department->dept_name }}</span><small>{{ number_format($department->users_count) }} {{ $department->users_count === 1 ? 'assigned account' : 'assigned accounts' }}</small></div>
                                <div class="department-entry-actions">
                                    <button type="button" class="btn btn-light border btn-sm" @click="editing = !editing" :aria-expanded="editing.toString()" aria-controls="department-edit-{{ $department->id }}" aria-label="Edit {{ $department->dept_code }}"><i data-lucide="pencil" aria-hidden="true"></i> Edit</button>
                                    <form method="POST" action="{{ route('admin.departments.destroy', $department) }}" data-confirm="Remove department {{ $department->dept_code }}? It will no longer be available for registration or personnel assignment." data-confirm-title="Remove this department?" data-confirm-accept="Remove department" data-confirm-tone="danger">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="version" value="{{ $department->managementVersion() }}">
                                        <button type="submit" class="btn btn-outline-danger btn-sm" @disabled($department->users_count > 0) title="{{ $department->users_count > 0 ? 'Reassign all assigned accounts before removing this department.' : 'Remove '.$department->dept_code }}" aria-label="Remove {{ $department->dept_code }}" data-loading-text="Removing…"><i data-lucide="trash-2" aria-hidden="true"></i> Remove</button>
                                    </form>
                                </div>
                            </div>
                            @if($department->users_count > 0)<p class="department-assignment-note">Reassign its personnel before removing this department.</p>@endif
                            <form id="department-edit-{{ $department->id }}" class="department-edit-form" method="POST" action="{{ route('admin.departments.update', $department) }}" x-show="editing" x-cloak>
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="version" value="{{ $department->managementVersion() }}">
                                <div class="department-edit-fields">
                                    <div><label for="department-code-{{ $department->id }}" class="form-label">Department code</label><input id="department-code-{{ $department->id }}" name="dept_code" value="{{ $isRetry ? old('dept_code', $department->dept_code) : $department->dept_code }}" class="form-control" maxlength="20" pattern="[A-Za-z][A-Za-z0-9\-]*" required></div>
                                    <div><label for="department-name-{{ $department->id }}" class="form-label">Department name</label><input id="department-name-{{ $department->id }}" name="dept_name" value="{{ $isRetry ? old('dept_name', $department->dept_name) : $department->dept_name }}" class="form-control" maxlength="255" required></div>
                                </div>
                                <p class="department-assignment-note">Existing personnel stay assigned to this department when you rename it.</p>
                                <div class="department-edit-actions"><button type="button" class="btn btn-light border btn-sm" @click="editing = false">Cancel</button><button type="submit" class="btn btn-htc btn-sm" data-loading-text="Saving…">Save changes</button></div>
                            </form>
                        </article>
                    @empty<p class="text-muted text-center py-4 mb-0">No departments yet. Use Add department to create one.</p>@endforelse
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

@pushOnce('styles', 'department-management-styles')
<style>
    .personnel-department-actions { display:flex; flex-wrap:wrap; gap:var(--space-8); }
    .department-management-body { padding:var(--space-16); }
    .department-list { display:grid; gap:var(--space-12); }
    .department-entry { padding:var(--space-16); border:1px solid #dce7df; border-radius:10px; background:#fff; text-align:left; }
    .department-entry-header { display:flex; align-items:center; justify-content:space-between; gap:var(--space-16); }
    .department-entry-copy { display:grid; gap:var(--space-4); min-width:0; }
    .department-entry-copy strong { color:#265c3b; font-size:var(--text-body); }
    .department-entry-copy span { color:#435d4c; font-size:var(--text-secondary); overflow-wrap:anywhere; }
    .department-entry-copy small,.department-assignment-note { color:#708575; font-size:var(--text-caption); }
    .department-entry-actions,.department-edit-actions { display:flex; align-items:center; flex-wrap:wrap; gap:var(--space-8); flex-shrink:0; }
    .department-entry-actions .btn { display:inline-flex; align-items:center; justify-content:center; gap:var(--space-4); min-height:36px; }
    .department-entry-actions svg { width:14px; height:14px; }
    .department-assignment-note { margin:var(--space-8) 0 0; }
    .department-edit-form { margin-top:var(--space-16); padding-top:var(--space-16); border-top:1px solid #e4ece7; }
    .department-edit-fields { display:grid; grid-template-columns:minmax(120px,.6fr) minmax(0,1.4fr); gap:var(--space-12); }
    .department-edit-fields > div { min-width:0; }
    .department-edit-actions { justify-content:flex-end; margin-top:var(--space-12); }
    @media(max-width:575px) { .department-entry-header { align-items:flex-start; flex-direction:column; gap:var(--space-12); }.department-entry-actions { width:100%; }.department-entry-actions > :is(button,form) { flex:1; }.department-entry-actions form .btn { width:100%; }.department-edit-fields { grid-template-columns:1fr; }.personnel-department-actions { width:100%; }.personnel-department-actions .btn { flex:1 1 auto; } }
</style>
@endPushOnce
