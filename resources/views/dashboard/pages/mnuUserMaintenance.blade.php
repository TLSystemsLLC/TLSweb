<div class="card shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="h5 fw-bold mb-1">User Maintenance</h3>
                <p class="text-muted small mb-0">Add or edit system users. All fields are required unless noted.</p>
            </div>
            <button class="btn btn-primary btn-sm" id="btn-add-user">
                <i class="bi bi-person-plus"></i> Add New User
            </button>
        </div>

        <div class="mb-3">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="user-search" class="form-control border-start-0" placeholder="Search by name, ID, or email...">
            </div>
        </div>

        <div id="user-list-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle small" id="user-table">
                    <thead class="table-light">
                        <tr>
                            <th>User ID / Name</th>
                            <th>Email</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="user-list-body">
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                Please enter at least 2 characters to search.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Add/Edit -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="userForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="u-key" value="0">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">User ID</label>
                            <input type="text" id="u-userid" class="form-control form-control-sm" required maxlength="20">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">User Name</label>
                            <input type="text" id="u-username" class="form-control form-control-sm" required maxlength="35">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">First Name</label>
                            <input type="text" id="u-firstname" class="form-control form-control-sm" maxlength="35">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Last Name</label>
                            <input type="text" id="u-lastname" class="form-control form-control-sm" maxlength="35">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Email</label>
                            <input type="email" id="u-email" class="form-control form-control-sm" maxlength="50">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Password</label>
                            <input type="password" id="u-password" class="form-control form-control-sm" maxlength="35">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Extension</label>
                            <input type="number" id="u-extension" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Team Key</label>
                            <input type="number" id="u-teamkey" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">User Type</label>
                            <select id="u-type" class="form-select form-select-sm">
                                <option value="1">System</option>
                                <option value="2">Standard</option>
                                <option value="3">Agent</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Company ID</label>
                            <input type="number" id="u-companyid" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Division ID</label>
                            <input type="number" id="u-divisionid" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Department ID</label>
                            <input type="number" id="u-departmentid" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Phone</label>
                            <input type="text" id="u-phone" class="form-control form-control-sm" maxlength="20">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Fax</label>
                            <input type="text" id="u-fax" class="form-control form-control-sm" maxlength="20">
                        </div>
                        <div class="col-md-12">
                            <div class="form-check form-check-inline mt-2">
                                <input class="form-check-input" type="checkbox" id="u-active" checked>
                                <label class="form-check-label small" for="u-active">Active</label>
                            </div>
                            <div class="form-check form-check-inline mt-2">
                                <input class="form-check-input" type="checkbox" id="u-satellite">
                                <label class="form-check-label small" for="u-satellite">Satellite Installs</label>
                            </div>
                            <div class="form-check form-check-inline mt-2">
                                <input class="form-check-input" type="checkbox" id="u-rapidloguser">
                                <label class="form-check-label small" for="u-rapidloguser">RapidLog User</label>
                            </div>
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
    let allUsers = [];
    let modal;
    let form;

    async function init() {
        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap is not loaded globally!');
            return;
        }

        modal = new bootstrap.Modal(document.getElementById('userModal'));
        form = document.getElementById('userForm');

        document.getElementById('btn-add-user').addEventListener('click', () => {
            showModal();
        });

        document.getElementById('user-search').addEventListener('input', (e) => {
            const val = e.target.value.trim();
            if (val.length >= 2) {
                loadUsers(val);
            } else {
                renderTable([]);
            }
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await saveUser();
        });
    }

    async function loadUsers(search = '', maxRows = 100) {
        const res = await window.callSp('spUser_Search', [search, maxRows]);
        if (res.ok) {
            allUsers = res.data;
            renderTable(allUsers);
        } else {
            document.getElementById('user-list-body').innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">Failed to load users (rc: ${res.rc})</td></tr>`;
        }
    }

    function isTruthy(val) {
        if (val === undefined || val === null) return false;
        if (typeof val === 'boolean') return val;
        if (typeof val === 'number') return val !== 0;
        if (typeof val === 'string') {
            const s = val.toLowerCase().trim();
            return s === '1' || s === 'true' || s === 'y' || s === 'yes' || s === 'active';
        }
        return !!val;
    }

    function renderTable(data) {
        const tbody = document.getElementById('user-list-body');
        const searchVal = document.getElementById('user-search').value.trim();

        if (searchVal.length < 2) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">Please enter at least 2 characters to search.</td></tr>';
            return;
        }

        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No users found.</td></tr>';
            return;
        }

        tbody.innerHTML = '';
        data.forEach(u => {
            const userId = u.UserID || u.userid || u.Login || '';
            const userName = u.UserName || u.UserName || u.Name || userId;
            const key = u.UserKey || u.UserKeyID;
            const activeRaw = (u.Active !== undefined) ? u.Active : ((u.active !== undefined) ? u.active : u.IsActive);
            const active = isTruthy(activeRaw);
            const tr = document.createElement('tr');

            tr.innerHTML = `
                <td>
                    <div class="fw-bold">${userId}</div>
                    <div class="x-small text-muted">${userName}</div>
                </td>
                <td>${u.Email || u.email || ''}</td>
                <td>${(u.UserType === 1 || u.usertype === 1) ? 'System' : ((u.UserType === 3 || u.usertype === 3) ? 'Agent' : 'Standard')}</td>
                <td>
                    <span class="badge rounded-pill ${active ? 'bg-success' : 'bg-danger'}">
                        ${active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td class="text-end">
                    <button class="btn btn-outline-secondary btn-sm me-1 btn-edit" data-key="${key}" data-userid="${userId}" title="Edit"><i class="bi bi-pencil"></i></button>
                </td>
            `;

            tr.querySelector('.btn-edit').addEventListener('click', () => showModal(key, userId));
            tbody.appendChild(tr);
        });
    }

    function filterTable(query) {
        const q = query.toLowerCase();
        const filtered = allUsers.filter(u =>
            (u.UserID || '').toLowerCase().includes(q) ||
            (u.UserName || '').toLowerCase().includes(q) ||
            (u.Email || '').toLowerCase().includes(q)
        );
        renderTable(filtered);
    }

    async function showModal(key = 0, userId = '') {
        form.reset();
        document.getElementById('u-key').value = key;
        document.getElementById('userModalLabel').textContent = key === 0 ? 'Add New User' : 'Edit User';

        if (key !== 0 && userId !== '') {
            const res = await window.callSp('spUser_GetByID', [userId]);
            if (res.ok && res.data && res.data.length > 0) {
                const user = res.data[0];
                document.getElementById('u-userid').value = user.UserID || user.userid || '';
                document.getElementById('u-username').value = user.UserName || user.username || '';
                document.getElementById('u-firstname').value = user.FirstName || user.firstname || '';
                document.getElementById('u-lastname').value = user.LastName || user.lastname || '';
                document.getElementById('u-email').value = user.Email || user.email || '';
                document.getElementById('u-password').value = user.Password || user.password || '';
                document.getElementById('u-extension').value = user.Extension || user.extension || 0;
                document.getElementById('u-teamkey').value = user.TeamKey || user.teamkey || 0;
                document.getElementById('u-type').value = user.UserType || user.usertype || 1;
                document.getElementById('u-companyid').value = user.CompanyID || user.companyid || 0;
                document.getElementById('u-divisionid').value = user.DivisionID || user.divisionid || 0;
                document.getElementById('u-departmentid').value = user.DepartmentID || user.departmentid || 0;
                document.getElementById('u-phone').value = user.Phone || user.phone || '';
                document.getElementById('u-fax').value = user.Fax || user.fax || '';
                const activeRaw = (user.Active !== undefined) ? user.Active : ((user.active !== undefined) ? user.active : user.IsActive);
                document.getElementById('u-active').checked = isTruthy(activeRaw);
                const satRaw = (user.SatelliteInstalls !== undefined) ? user.SatelliteInstalls : user.satelliteinstalls;
                document.getElementById('u-satellite').checked = isTruthy(satRaw);
                const rapidRaw = (user.RapidLogUser !== undefined) ? user.RapidLogUser : user.rapidloguser;
                document.getElementById('u-rapidloguser').checked = isTruthy(rapidRaw);
            } else {
                alert(`Failed to load user details (rc: ${res.rc})`);
                return;
            }
        }
        modal.show();
    }

    async function saveUser() {
        try {
            const uKey = parseInt(document.getElementById('u-key').value) || 0;
            const params = [
                uKey,
                document.getElementById('u-userid').value,
                parseInt(document.getElementById('u-teamkey').value) || 0,
                document.getElementById('u-username').value,
                document.getElementById('u-firstname').value,
                document.getElementById('u-lastname').value,
                null, // PasswordChanged
                parseInt(document.getElementById('u-extension').value) || 0,
                document.getElementById('u-satellite').checked ? 1 : 0,
                document.getElementById('u-password').value,
                document.getElementById('u-email').value,
                null, // LastLogin
                null, // HireDate
                document.getElementById('u-rapidloguser').checked ? 1 : 0,
                document.getElementById('u-active').checked ? 1 : 0,
                parseInt(document.getElementById('u-type').value) || 1,
                parseInt(document.getElementById('u-companyid').value) || 0,
                parseInt(document.getElementById('u-divisionid').value) || 0,
                parseInt(document.getElementById('u-departmentid').value) || 0,
                document.getElementById('u-phone').value,
                document.getElementById('u-fax').value
            ];

            console.log('Saving user with params:', params);

            const res = await window.callSp('spUser_Save2', params);
            if (res.ok) {
                console.log('User saved successfully');
                modal.hide();
                const searchVal = document.getElementById('user-search').value.trim();
                if (searchVal.length >= 2) {
                    await loadUsers(searchVal);
                } else {
                    renderTable([]);
                }
            } else {
                console.error('Failed to save user:', res);
                alert(`Failed to save: Error code ${res.rc}. ${res.error || ''}`);
            }
        } catch (err) {
            console.error('Error in saveUser:', err);
            alert('An unexpected error occurred while saving the user. Check the console for details.');
        }
    }

    init();
})();
</script>
