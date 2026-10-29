<div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-hidden="true" aria-labelledby="addDepartmentTitle"
    @if($errors->getBag('departmentForm')->any()) x-init="$nextTick(() => bootstrap.Modal.getOrCreateInstance($el).show())" @endif>
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content" method="POST" action="{{ route('admin.departments.store') }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="addDepartmentTitle">Add department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">New departments are available during registration and personnel assignment.</p>
                <div class="mb-3">
                    <label class="form-label" for="newDepartmentCode">Department code</label>
                    <input id="newDepartmentCode" name="dept_code" value="{{ old('dept_code') }}" class="form-control @error('dept_code', 'departmentForm') is-invalid @enderror" placeholder="e.g. CETE" maxlength="20" pattern="[A-Za-z][A-Za-z0-9-]*" required>
                    @error('dept_code', 'departmentForm')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="newDepartmentName">Department name</label>
                    <input id="newDepartmentName" name="dept_name" value="{{ old('dept_name') }}" class="form-control @error('dept_name', 'departmentForm') is-invalid @enderror" placeholder="Full department or office name" maxlength="255" required>
                    @error('dept_name', 'departmentForm')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-htc" data-loading-text="Adding department…">Add department</button>
            </div>
        </form>
    </div>
</div>
