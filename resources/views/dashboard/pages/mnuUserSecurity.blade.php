<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h3 class="h5 fw-bold mb-1">User Security</h3>
        <p class="text-muted small mb-0">Manage user menu authorization. Changes are saved automatically.</p>
    </div>
    <div class="col-md-4">
        <label for="user-select" class="form-label small fw-bold mb-1">Select User:</label>
        <select id="user-select" class="form-select form-select-sm shadow-sm" disabled>
            <option value="">Loading users...</option>
        </select>
    </div>
</div>

<div id="menu-mgmt-status" class="alert d-none py-2 px-3 small mb-3"></div>

<div id="menu-mgmt-container" class="row">
    <div id="loading-placeholder" class="col-12 text-center py-5 text-muted">
        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
        <span id="loading-text">Select a user to manage permissions...</span>
    </div>
</div>
