<div class="card shadow-sm mb-4">
    <div class="card-body p-4">
        <h3 class="card-title h5 fw-bold mb-3">Menu Management</h3>
        <p class="text-muted small mb-4">Select the menu items authorized for your account. Changes are saved automatically.</p>

        <div id="menu-mgmt-status" class="alert d-none py-2 px-3 small mb-3"></div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Menu Item</th>
                        <th>Key</th>
                        <th class="text-center" style="width: 100px;">Authorized</th>
                    </tr>
                </thead>
                <tbody id="menu-mgmt-body">
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">
                            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                            Loading permissions...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
