<div class="card shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="h5 fw-bold mb-1">Factoring Companies</h3>
                <p class="text-muted small mb-0">Manage factoring company records and contact information.</p>
            </div>
            <button class="btn btn-primary btn-sm" id="btn-add-factoring">
                <i class="bi bi-plus-lg"></i> Add New Company
            </button>
        </div>

        <div class="mb-3">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="factoring-search" class="form-control border-start-0" placeholder="Search by name, city, or email...">
            </div>
        </div>

        <div id="factoring-list-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle small" id="factoring-table">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Contact</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="factoring-list-body">
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                Loading companies...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Add/Edit -->
<div class="modal fade" id="factoringModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="factoringForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="factoringModalLabel">Factoring Company</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="f-id" value="0">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Company Name</label>
                            <input type="text" id="f-name" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Address</label>
                            <input type="text" id="f-address" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">City</label>
                            <input type="text" id="f-city" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">State</label>
                            <input type="text" id="f-state" class="form-control form-control-sm" maxlength="2">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Zip</label>
                            <input type="text" id="f-zip" class="form-control form-control-sm" maxlength="10">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Email</label>
                            <input type="email" id="f-email" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">ABA / Routing #</label>
                            <input type="text" id="f-aba" class="form-control form-control-sm" maxlength="9">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Account #</label>
                            <input type="text" id="f-account" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link btn-sm text-decoration-none" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    let allCompanies = [];
    let modal;
    let form;

    let debounceTimer;

    async function init() {
        console.log('mnuFactoringCo initializing...');

        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap is not loaded globally!');
            return;
        }

        modal = new bootstrap.Modal(document.getElementById('factoringModal'));
        form = document.getElementById('factoringForm');

        // Initial state: empty table, do NOT load until user searches
        renderTable([]);

        document.getElementById('btn-add-factoring').addEventListener('click', () => {
            showModal();
        });

        document.getElementById('factoring-search').addEventListener('input', (e) => {
            const val = e.target.value.trim();
            // Use server-side search with debounce, only if length >= 2
            clearTimeout(debounceTimer);
            if (val.length >= 2) {
                debounceTimer = setTimeout(() => {
                    loadCompanies(val, 100);
                }, 300);
            } else {
                // Clear table if search is too short
                allCompanies = [];
                renderTable([]);
            }
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await saveCompany();
        });
    }

    async function loadCompanies(search = '', maxRows = 100) {
        const res = await window.callSp('spFactoringCo_Search', [search, maxRows]);
        if (res.ok) {
            allCompanies = res.data;
            renderTable(allCompanies);
        } else {
            document.getElementById('factoring-list-body').innerHTML = `<tr><td colspan="4" class="text-center text-danger py-4">Failed to load companies (rc: ${res.rc})</td></tr>`;
        }
    }

    function renderTable(data) {
        const tbody = document.getElementById('factoring-list-body');
        const searchVal = document.getElementById('factoring-search').value.trim();

        if (searchVal.length < 2) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">Please enter at least 2 characters to search.</td></tr>';
            return;
        }

        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">No records found.</td></tr>';
            return;
        }

        tbody.innerHTML = '';
        data.forEach(item => {
            const name = (item.Name || '').trim() || '<em>Unnamed Company</em>';
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><div class="fw-bold">${name}</div><div class="x-small text-muted">ID: ${item.ID}</div></td>
                <td>${item.City || ''}${item.State ? ', ' + item.State : ''}</td>
                <td>${item.Email || '<span class="text-muted">No email</span>'}</td>
                <td class="text-end">
                    <button class="btn btn-outline-secondary btn-sm me-1 btn-edit" data-id="${item.ID}" title="Edit"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-outline-danger btn-sm btn-delete" data-id="${item.ID}" title="Delete"><i class="bi bi-trash"></i></button>
                </td>
            `;

            tr.querySelector('.btn-edit').addEventListener('click', () => showModal(item.ID));
            tr.querySelector('.btn-delete').addEventListener('click', () => deleteCompany(item.ID, item.Name));

            tbody.appendChild(tr);
        });
    }

    function filterTable(query) {
        const q = query.toLowerCase();
        const filtered = allCompanies.filter(c =>
            (c.Name || '').toLowerCase().includes(q) ||
            (c.City || '').toLowerCase().includes(q) ||
            (c.Email || '').toLowerCase().includes(q)
        );
        renderTable(filtered);
    }

    async function showModal(id = 0) {
        form.reset();
        document.getElementById('f-id').value = id;
        document.getElementById('factoringModalLabel').textContent = id === 0 ? 'Add Factoring Company' : 'Edit Factoring Company';

        if (id !== 0) {
            const res = await window.callSp('spFactoringCo_Get', [id]);
            if (res.ok && res.data.length > 0) {
                const c = res.data[0];
                document.getElementById('f-name').value = c.Name || '';
                document.getElementById('f-address').value = c.Address || '';
                document.getElementById('f-city').value = c.City || '';
                document.getElementById('f-state').value = c.State || '';
                document.getElementById('f-zip').value = c.Zip || '';
                document.getElementById('f-aba').value = c.ABA || '';
                document.getElementById('f-account').value = c.Account || '';
                document.getElementById('f-email').value = c.Email || '';
            }
        }
        modal.show();
    }

    async function saveCompany() {
        const id = parseInt(document.getElementById('f-id').value);
        const params = [
            id,
            document.getElementById('f-name').value,
            document.getElementById('f-address').value,
            document.getElementById('f-city').value,
            document.getElementById('f-state').value,
            document.getElementById('f-zip').value,
            document.getElementById('f-aba').value,
            document.getElementById('f-account').value,
            document.getElementById('f-email').value
        ];

        const res = await window.callSp('spFactoringCo_Save', params);
        if (res.ok) {
            modal.hide();
            // Refresh with current search
            const searchVal = document.getElementById('factoring-search').value;
            loadCompanies(searchVal, 100);
        } else {
            alert(`Failed to save: Error code ${res.rc}`);
        }
    }

    async function deleteCompany(id, name) {
        if (confirm(`Are you sure you want to delete "${name}"?`)) {
            const res = await window.callSp('spFactoringCo_Delete', [id]);
            if (res.ok) {
                // Refresh with current search
                const searchVal = document.getElementById('factoring-search').value;
                loadCompanies(searchVal, 100);
            } else {
                alert(`Failed to delete: Error code ${res.rc}`);
            }
        }
    }

    init();
})();
</script>
