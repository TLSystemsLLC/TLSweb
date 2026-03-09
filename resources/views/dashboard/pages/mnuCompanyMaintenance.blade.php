<div class="card shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="h5 fw-bold mb-1">Company Maintenance</h3>
                <p class="text-muted small mb-0">Add or edit system companies. All fields are required unless noted.</p>
            </div>
            <button class="btn btn-primary btn-sm" id="btn-add-company">
                <i class="bi bi-plus-lg"></i> Add New Company
            </button>
        </div>

        <div class="mb-3 d-flex gap-2 align-items-center">
            <div class="input-group input-group-sm flex-grow-1">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="company-search" class="form-control border-start-0" placeholder="Search by name, ID, or city (use * for all)...">
            </div>
            <div id="pagination-controls" class="d-none d-flex gap-2 align-items-center">
                <button class="btn btn-outline-secondary btn-sm" id="btn-prev-page" title="Previous Page"><i class="bi bi-chevron-left"></i></button>
                <span id="page-info" class="small text-muted text-nowrap">Page 1 of 1</span>
                <button class="btn btn-outline-secondary btn-sm" id="btn-next-page" title="Next Page"><i class="bi bi-chevron-right"></i></button>
            </div>
        </div>

        <div id="company-list-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle small" id="company-table">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Short Name</th>
                            <th>Mailing City/ST</th>
                            <th>Shipping City/ST</th>
                            <th>SCAC/DUNS</th>
                            <th>MC/DOT/FID</th>
                            <th>Active</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="company-list-body">
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
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
<div class="modal fade" id="companyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="companyForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="companyModalLabel">Company Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="c-CompanyID" value="0">
                    <div class="row g-3">
                        <div class="col-md-9">
                            <label class="form-label small fw-bold">Company Name</label>
                            <input type="text" id="c-CompanyName" class="form-control form-control-sm" required maxlength="50">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Active</label>
                            <div class="form-check form-switch pt-1">
                                <input class="form-check-input" type="checkbox" id="c-Active" checked>
                                <label class="form-check-label small" for="c-Active">Is Active</label>
                            </div>
                        </div>

                        <!-- Mailing Address -->
                        <div class="col-12"><h6 class="border-bottom pb-1 mb-2 small fw-bold text-primary">Mailing Address</h6></div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Address</label>
                            <input type="text" id="c-MailingAddress" class="form-control form-control-sm" maxlength="40">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">City</label>
                            <input type="text" id="c-MailingCity" class="form-control form-control-sm" maxlength="25">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">State</label>
                            <input type="text" id="c-MailingState" class="form-control form-control-sm" maxlength="2">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Zip</label>
                            <input type="text" id="c-MailingZip" class="form-control form-control-sm" maxlength="10">
                        </div>

                        <!-- Shipping Address -->
                        <div class="col-12"><h6 class="border-bottom pb-1 mb-2 mt-2 small fw-bold text-primary">Shipping Address</h6></div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Address</label>
                            <input type="text" id="c-ShippingAddress" class="form-control form-control-sm" maxlength="40">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">City</label>
                            <input type="text" id="c-ShippingCity" class="form-control form-control-sm" maxlength="25">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">State</label>
                            <input type="text" id="c-ShippingState" class="form-control form-control-sm" maxlength="2">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Zip</label>
                            <input type="text" id="c-ShippingZip" class="form-control form-control-sm" maxlength="10">
                        </div>

                        <!-- Contact Info -->
                        <div class="col-12"><h6 class="border-bottom pb-1 mb-2 mt-2 small fw-bold text-primary">Contact & Identifiers</h6></div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Phone</label>
                            <input type="text" id="c-MainPhone" class="form-control form-control-sm" maxlength="15">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Fax</label>
                            <input type="text" id="c-MainFax" class="form-control form-control-sm" maxlength="15">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">SCAC</label>
                            <input type="text" id="c-SCAC" class="form-control form-control-sm" maxlength="4">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">DUNS</label>
                            <input type="text" id="c-DUNS" class="form-control form-control-sm" maxlength="15">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">ICC</label>
                            <input type="text" id="c-ICC" class="form-control form-control-sm" maxlength="15">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">DOT</label>
                            <input type="text" id="c-DOT" class="form-control form-control-sm" maxlength="15">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">FID</label>
                            <input type="text" id="c-FID" class="form-control form-control-sm" maxlength="15">
                        </div>

                        <!-- Accounting -->
                        <div class="col-12"><h6 class="border-bottom pb-1 mb-2 mt-2 small fw-bold text-primary">Accounting Setup</h6></div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">AP Account</label>
                            <input type="text" id="c-APAccount" class="form-control form-control-sm" maxlength="20">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">AR Account</label>
                            <input type="text" id="c-ARAccount" class="form-control form-control-sm" maxlength="20">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Retained Earnings</label>
                            <input type="text" id="c-RetainedEarningsAccount" class="form-control form-control-sm" maxlength="20">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Bad Debt</label>
                            <input type="text" id="c-BadDebtAccount" class="form-control form-control-sm" maxlength="20">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Misc Account</label>
                            <input type="text" id="c-MiscAccount" class="form-control form-control-sm" maxlength="20">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Short Name</label>
                            <input type="text" id="c-ShortName" class="form-control form-control-sm" maxlength="15">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Freight Rev</label>
                            <input type="text" id="c-FreightRevAccount" class="form-control form-control-sm" maxlength="20">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Broker Rev</label>
                            <input type="text" id="c-BrokerRevAccount" class="form-control form-control-sm" maxlength="20">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Freight Payable</label>
                            <input type="text" id="c-FreightPayableAccount" class="form-control form-control-sm" maxlength="20">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Gen Bank</label>
                            <input type="text" id="c-GeneralBankAccount" class="form-control form-control-sm" maxlength="20">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Settlement Bank</label>
                            <input type="text" id="c-SettlementBankAccount" class="form-control form-control-sm" maxlength="20">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Settlement Clear</label>
                            <input type="text" id="c-SettlementClearingAccount" class="form-control form-control-sm" maxlength="20">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Inter-Co Clear</label>
                            <input type="text" id="c-InterCompanyClearing" class="form-control form-control-sm" maxlength="20">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Inter-Co AR</label>
                            <input type="text" id="c-InterCompanyAR" class="form-control form-control-sm" maxlength="20">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Inter-Co AP</label>
                            <input type="text" id="c-InterCompanyAP" class="form-control form-control-sm" maxlength="20">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">System Remit Vendor</label>
                            <input type="text" id="c-SystemRemitVendor" class="form-control form-control-sm" maxlength="15">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Driver AR</label>
                            <input type="text" id="c-DriverAR" class="form-control form-control-sm" maxlength="20">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Freight Rev/Exp</label>
                            <input type="text" id="c-FrieghtRevExp" class="form-control form-control-sm" maxlength="20">
                        </div>

                        <!-- Revenue/Expense Breakdown -->
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Co Frt Rev</label>
                            <input type="text" id="c-CompanyFreightRevenue" class="form-control form-control-sm" maxlength="20">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Co Frt Exp</label>
                            <input type="text" id="c-CompanyFreightExpense" class="form-control form-control-sm" maxlength="20">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Truck Fuel</label>
                            <input type="text" id="c-CompanyTruckFuelExpense" class="form-control form-control-sm" maxlength="20">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Reefer Fuel</label>
                            <input type="text" id="c-CompanyReeferFuelExpense" class="form-control form-control-sm" maxlength="20">
                        </div>

                        <!-- Flags & Fees -->
                        <div class="col-12"><h6 class="border-bottom pb-1 mb-2 mt-2 small fw-bold text-primary">Flags</h6></div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Freight Det Post</label>
                            <div class="form-check form-switch pt-1">
                                <input class="form-check-input" type="checkbox" id="c-FreightDetailPost">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Comdata</label>
                            <div class="form-check form-switch pt-1">
                                <input class="form-check-input" type="checkbox" id="c-ComdataInterface">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Transflo</label>
                            <div class="form-check form-switch pt-1">
                                <input class="form-check-input" type="checkbox" id="c-TranfloMobileInterface">
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
    let modal;
    let form;
    let currentPage = 1;
    let totalPages = 1;
    let currentSearch = '';
    const pageSize = 100;

    async function init() {
        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap is not loaded globally!');
            return;
        }

        modal = new bootstrap.Modal(document.getElementById('companyModal'));
        form = document.getElementById('companyForm');

        document.getElementById('btn-add-company').addEventListener('click', () => {
            showModal();
        });

        document.getElementById('company-search').addEventListener('input', (e) => {
            const val = e.target.value.trim();
            if (val.length >= 2 || val === '*') {
                currentSearch = val;
                currentPage = 1;
                loadCompanies(val, currentPage);
            } else {
                renderTable([]);
                document.getElementById('pagination-controls').classList.add('d-none');
            }
        });

        document.getElementById('btn-prev-page').addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                loadCompanies(currentSearch, currentPage);
            }
        });

        document.getElementById('btn-next-page').addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                loadCompanies(currentSearch, currentPage);
            }
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await saveCompany();
        });
    }

    async function loadCompanies(search = '', page = 1) {
        try {
            const res = await window.callSp('webCompanySearch', [search, parseInt(page) || 1, parseInt(pageSize) || 100]);
            if (res.ok) {
                const data = res.data || [];
                if (data.length > 0) {
                    const first = data[0];
                    currentPage = first.CurrentPage || page;
                    totalPages = first.TotalPages || 1;
                    updatePagination(currentPage, totalPages);
                } else {
                    document.getElementById('pagination-controls').classList.add('d-none');
                }
                renderTable(data);
            } else {
                document.getElementById('company-list-body').innerHTML = `<tr><td colspan="9" class="text-center text-danger py-4">Failed to load companies (rc: ${res.rc})</td></tr>`;
                document.getElementById('pagination-controls').classList.add('d-none');
            }
        } catch (err) {
            console.error('Error loading companies:', err);
            document.getElementById('company-list-body').innerHTML = `<tr><td colspan="9" class="text-center text-danger py-4">An unexpected error occurred.</td></tr>`;
        }
    }

    function updatePagination(current, total) {
        const controls = document.getElementById('pagination-controls');
        const info = document.getElementById('page-info');
        const btnPrev = document.getElementById('btn-prev-page');
        const btnNext = document.getElementById('btn-next-page');

        if (total <= 1 && current <= 1) {
            controls.classList.add('d-none');
            return;
        }

        controls.classList.remove('d-none');
        info.textContent = `Page ${current} of ${total}`;
        btnPrev.disabled = (current <= 1);
        btnNext.disabled = (current >= total);
    }

    function renderTable(data) {
        const tbody = document.getElementById('company-list-body');
        const searchVal = document.getElementById('company-search').value.trim();

        if (searchVal.length < 2 && searchVal !== '*') {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted">Please enter at least 2 characters to search.</td></tr>';
            return;
        }

        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted">No companies found.</td></tr>';
            return;
        }

        tbody.innerHTML = '';
        data.forEach(c => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${c.CompanyID || ''}</td>
                <td><div class="fw-bold text-primary">${c.CompanyName || ''}</div></td>
                <td>${c.ShortName || ''}</td>
                <td class="small">${c.MailingCity || ''}, ${c.MailingState || ''}</td>
                <td class="small">${c.ShippingCity || ''}, ${c.ShippingState || ''}</td>
                <td class="small">
                    <div>S: ${c.SCAC || ''}</div>
                    <div>D: ${c.DUNS || ''}</div>
                </td>
                <td class="small">
                    <div>M: ${c.MC || ''}</div>
                    <div>D: ${c.DOT || ''}</div>
                    <div>F: ${c.FID || ''}</div>
                </td>
                <td>
                    <span class="badge ${c.Active ? 'bg-success' : 'bg-secondary'}">
                        ${c.Active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td class="text-end">
                    <button class="btn btn-outline-secondary btn-sm me-1 btn-edit" data-id="${c.CompanyID || 0}" title="Edit"><i class="bi bi-pencil"></i></button>
                </td>
            `;

            tr.querySelector('.btn-edit').addEventListener('click', () => showModal(c.CompanyID));
            tbody.appendChild(tr);
        });
    }

    async function showModal(id = 0) {
        form.reset();
        document.getElementById('c-CompanyID').value = id;
        document.getElementById('companyModalLabel').textContent = id === 0 ? 'Add New Company' : 'Edit Company';

        if (id !== 0) {
            const res = await window.callSp('webCompanyGet', [parseInt(id)]);
            if (res.ok && res.data && res.data.length > 0) {
                const c = res.data[0];
                document.getElementById('c-CompanyName').value = c.CompanyName || '';
                document.getElementById('c-Active').checked = !!c.Active;

                // Mailing Address
                document.getElementById('c-MailingAddress').value = c.MailingAddress || '';
                document.getElementById('c-MailingCity').value = c.MailingCity || '';
                document.getElementById('c-MailingState').value = c.MailingState || '';
                document.getElementById('c-MailingZip').value = c.MailingZip || '';

                // Shipping Address
                document.getElementById('c-ShippingAddress').value = c.ShippingAddress || '';
                document.getElementById('c-ShippingCity').value = c.ShippingCity || '';
                document.getElementById('c-ShippingState').value = c.ShippingState || '';
                document.getElementById('c-ShippingZip').value = c.ShippingZip || '';

                // Contact & Identifiers
                document.getElementById('c-MainPhone').value = c.MainPhone || '';
                document.getElementById('c-MainFax').value = c.MainFax || '';
                document.getElementById('c-SCAC').value = c.SCAC || '';
                document.getElementById('c-DUNS').value = c.DUNS || '';
                document.getElementById('c-ICC').value = c.ICC || '';
                document.getElementById('c-DOT').value = c.DOT || '';
                document.getElementById('c-FID').value = c.FID || '';

                // Accounting
                document.getElementById('c-APAccount').value = c.APAccount || '';
                document.getElementById('c-ARAccount').value = c.ARAccount || '';
                document.getElementById('c-RetainedEarningsAccount').value = c.RetainedEarningsAccount || '';
                document.getElementById('c-BadDebtAccount').value = c.BadDebtAccount || '';
                document.getElementById('c-MiscAccount').value = c.MiscAccount || '';
                document.getElementById('c-ShortName').value = c.ShortName || '';
                document.getElementById('c-FreightRevAccount').value = c.FreightRevAccount || '';
                document.getElementById('c-BrokerRevAccount').value = c.BrokerRevAccount || '';
                document.getElementById('c-FreightPayableAccount').value = c.FreightPayableAccount || '';
                document.getElementById('c-GeneralBankAccount').value = c.GeneralBankAccount || '';
                document.getElementById('c-SettlementBankAccount').value = c.SettlementBankAccount || '';
                document.getElementById('c-SettlementClearingAccount').value = c.SettlementClearingAccount || '';
                document.getElementById('c-InterCompanyClearing').value = c.InterCompanyClearing || '';
                document.getElementById('c-InterCompanyAR').value = c.InterCompanyAR || '';
                document.getElementById('c-InterCompanyAP').value = c.InterCompanyAP || '';
                document.getElementById('c-SystemRemitVendor').value = c.SystemRemitVendor || '';
                document.getElementById('c-DriverAR').value = c.DriverAR || '';
                document.getElementById('c-FrieghtRevExp').value = c.FrieghtRevExp || '';

                // Revenue/Expense Breakdown
                document.getElementById('c-CompanyFreightRevenue').value = c.CompanyFreightRevenue || '';
                document.getElementById('c-CompanyFreightExpense').value = c.CompanyFreightExpense || '';
                document.getElementById('c-CompanyTruckFuelExpense').value = c.CompanyTruckFuelExpense || '';
                document.getElementById('c-CompanyReeferFuelExpense').value = c.CompanyReeferFuelExpense || '';

                // Flags
                document.getElementById('c-FreightDetailPost').checked = !!c.FreightDetailPost;
                document.getElementById('c-ComdataInterface').checked = !!c.ComdataInterface;
                document.getElementById('c-TranfloMobileInterface').checked = !!c.TranfloMobileInterface;
            }
        } else {
            document.getElementById('c-Active').checked = true;
        }

        modal.show();
    }

    async function saveCompany() {
        const id = parseInt(document.getElementById('c-CompanyID').value) || 0;
        const params = [
            id,
            document.getElementById('c-CompanyName').value,
            document.getElementById('c-ShortName').value,
            document.getElementById('c-MailingAddress').value,
            document.getElementById('c-MailingCity').value,
            document.getElementById('c-MailingState').value,
            document.getElementById('c-MailingZip').value,
            document.getElementById('c-ShippingAddress').value,
            document.getElementById('c-ShippingCity').value,
            document.getElementById('c-ShippingState').value,
            document.getElementById('c-ShippingZip').value,
            document.getElementById('c-MainPhone').value,
            document.getElementById('c-MainFax').value,
            document.getElementById('c-SCAC').value,
            document.getElementById('c-DUNS').value,
            document.getElementById('c-ICC').value, // Maps to @MC in webCompanySave config
            document.getElementById('c-DOT').value,
            document.getElementById('c-FID').value,
            document.getElementById('c-Active').checked ? 1 : 0,
            document.getElementById('c-FreightDetailPost').checked ? 1 : 0,
            document.getElementById('c-ComdataInterface').checked ? 1 : 0,
            document.getElementById('c-TranfloMobileInterface').checked ? 1 : 0,
            parseInt(document.getElementById('c-SystemRemitVendor').value) || 0,
            parseFloat(document.getElementById('c-APAccount').value) || 0,
            parseFloat(document.getElementById('c-ARAccount').value) || 0,
            parseFloat(document.getElementById('c-BadDebtAccount').value) || 0,
            parseFloat(document.getElementById('c-MiscAccount').value) || 0,
            parseFloat(document.getElementById('c-FreightRevAccount').value) || 0,
            parseFloat(document.getElementById('c-BrokerRevAccount').value) || 0,
            parseFloat(document.getElementById('c-FreightPayableAccount').value) || 0,
            parseFloat(document.getElementById('c-GeneralBankAccount').value) || 0,
            parseFloat(document.getElementById('c-SettlementBankAccount').value) || 0,
            parseFloat(document.getElementById('c-SettlementClearingAccount').value) || 0,
            parseFloat(document.getElementById('c-InterCompanyClearing').value) || 0,
            parseFloat(document.getElementById('c-InterCompanyAR').value) || 0,
            parseFloat(document.getElementById('c-InterCompanyAP').value) || 0,
            parseFloat(document.getElementById('c-FrieghtRevExp').value) || 0,
            parseFloat(document.getElementById('c-CompanyFreightRevenue').value) || 0,
            parseFloat(document.getElementById('c-CompanyFreightExpense').value) || 0,
            parseFloat(document.getElementById('c-CompanyTruckFuelExpense').value) || 0,
            parseFloat(document.getElementById('c-CompanyReeferFuelExpense').value) || 0,
            parseFloat(document.getElementById('c-DriverAR').value) || 0,
            parseFloat(document.getElementById('c-RetainedEarningsAccount').value) || 0,
            null // @Logo image parameter - currently not supported in UI
        ];

        try {
            const res = await window.callSp('webCompanySave', params);
            if (res.ok) {
                modal.hide();
                loadCompanies(currentSearch, currentPage);
            } else {
                alert('Failed to save company (rc: ' + res.rc + ')');
            }
        } catch (err) {
            console.error('Error saving company:', err);
            alert('An unexpected error occurred while saving.');
        }
    }

    init();
})();
</script>
