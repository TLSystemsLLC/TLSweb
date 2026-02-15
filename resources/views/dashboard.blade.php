<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TLS</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <style>
        /* Multi-level dropdown CSS */
        .dropdown-submenu {
            position: relative;
        }
        .dropdown-submenu .dropdown-menu {
            top: 0;
            left: 100%;
            margin-top: -1px;
            display: none;
        }
        /* Hover support for desktop */
        @media (min-width: 992px) {
            .dropdown-submenu:hover > .dropdown-menu {
                display: block;
            }
        }
        /* Click/Show support */
        .dropdown-submenu .dropdown-menu.show {
            display: block !important;
        }
        .dropdown-item.dropdown-toggle::after {
            transform: rotate(-90deg);
            position: absolute;
            right: 10px;
            top: 50%;
            margin-top: -4px;
        }
        .x-small {
            font-size: 0.7rem;
        }
        /* Mobile adjustments */
        @media (max-width: 991px) {
            .dropdown-submenu .dropdown-menu {
                left: 0;
                margin-left: 15px;
                position: static;
                float: none;
                background-color: rgba(0,0,0,0.05);
                border: none;
            }
        }
    </style>
</head>
<body class="bg-light min-vh-100">
    <div id="wrapper">
        <!-- Page Content -->
        <div id="page-content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow">
                <div class="container-fluid">
                    <a class="navbar-brand fw-bold" href="#" id="brand-link">TLS Dashboard</a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav me-auto mb-2 mb-lg-0" id="main-menu">
                            <!-- Menu items will be injected here -->
                            <li class="nav-item">
                                <span class="nav-link disabled small">Loading menu...</span>
                            </li>
                        </ul>
                        <div class="navbar-nav align-items-center">
                            <span id="user-display" class="nav-link text-white-50 small me-3"></span>
                            <form action="/logout" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-light btn-sm">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </nav>

            <main class="container py-4">
                <div id="content-area">
                    <!-- Dynamic content will be injected here -->
                    <div class="text-center py-5" id="content-loading">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading page...</p>
                    </div>
                    <div id="dynamic-content"></div>
                </div>
            </main>
        </div>
    </div>

    <script>
        const login = "{{ session('user_login') }}";
        const csrfToken = '{{ csrf_token() }}';

        if (login) {
            document.getElementById('user-display').textContent = `Logged in as: ${login}`;

            const [tenant, ...userParts] = login.split('.');
            const username = userParts.join('.');

            // Helper for SP calls
            async function callSp(proc, params = [], loginStr = login) {
                const response = await fetch('/api/sp', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        login: loginStr,
                        proc: proc,
                        params: params
                    })
                });
                return await response.json();
            }

            // Menu Rendering Logic
            async function loadMenu() {
                try {
                    // 1. Fetch full menu metadata (Global)
                    const menuRes = await callSp('GetMenuItems', [], null);
                    // 2. Fetch user permissions (Tenant)
                    const permRes = await callSp('spUser_Menus', [username]);

                    if (menuRes.ok && permRes.ok) {
                        renderMenu(menuRes.data, permRes.data);
                    } else {
                        document.getElementById('main-menu').innerHTML = '<div class="p-3 text-danger small">Failed to load menu.</div>';
                    }
                } catch (err) {
                    console.error('Menu load error:', err);
                }
            }

            function renderMenuManagement(allItems, userPerms) {
                const container = document.getElementById('menu-mgmt-body');
                container.innerHTML = '';

                const allowedKeys = new Set(userPerms.map(p => (p.MenuName || p.MenuKey || '').trim()));

                // Map items by ParentMenuItemId for recursive building
                const itemsByParent = {};
                allItems.forEach(item => {
                    const pid = item.ParentMenuItemId || 'root';
                    if (!itemsByParent[pid]) itemsByParent[pid] = [];
                    itemsByParent[pid].push(item);
                });

                // Sort children of each parent by SortPath
                Object.keys(itemsByParent).forEach(pid => {
                    itemsByParent[pid].sort((a, b) => {
                        const pathA = (a.SortPath || '').toString();
                        const pathB = (b.SortPath || '').toString();
                        return pathA.localeCompare(pathB);
                    });
                });

                // Identify roots (items whose parent is not in the set of all items)
                const allIds = new Set(allItems.map(item => item.MenuItemId));
                const roots = allItems.filter(item => {
                    if (!item.ParentMenuItemId) return true;
                    return !allIds.has(item.ParentMenuItemId);
                }).sort((a, b) => {
                    const pathA = (a.SortPath || '').toString();
                    const pathB = (b.SortPath || '').toString();
                    return pathA.localeCompare(pathB);
                });

                const renderedIds = new Set();

                function renderManagementRow(item, depth = 0) {
                    if (renderedIds.has(item.MenuItemId)) return;
                    renderedIds.add(item.MenuItemId);

                    const key = (item.MenuKey || '').trim();
                    if (!key) return;

                    const isAuthorized = allowedKeys.has(key);
                    const isSecurity = key.startsWith('sec');
                    const isSeparator = key.startsWith('sep');

                    const tr = document.createElement('tr');
                    if (isSecurity) tr.className = 'table-info';
                    if (isSeparator) tr.className = 'table-light text-muted small';

                    // Use indentation for hierarchy
                    const indentation = depth * 20;
                    const chevron = itemsByParent[item.MenuItemId] ? '<i class="bi bi-chevron-down small me-1"></i>' : '<span class="me-3"></span>';

                    tr.innerHTML = `
                        <td>
                            <div style="padding-left: ${indentation}px">
                                ${chevron}
                                <span class="fw-semibold">${item.Caption || '<em>No Caption</em>'}</span>
                                ${isSecurity ? '<span class="badge bg-info text-dark x-small ms-1">Security</span>' : ''}
                                ${isSeparator ? '<span class="badge bg-secondary x-small ms-1">Sep</span>' : ''}
                            </div>
                        </td>
                        <td><code class="x-small">${key}</code></td>
                        <td class="text-center">
                            <div class="form-check form-switch d-inline-block">
                                <input class="form-check-input menu-toggle-switch" type="checkbox"
                                    data-key="${key}" ${isAuthorized ? 'checked' : ''}>
                            </div>
                        </td>
                    `;
                    container.appendChild(tr);

                    // Render children immediately after parent
                    const children = itemsByParent[item.MenuItemId] || [];
                    children.forEach(child => renderManagementRow(child, depth + 1));
                }

                roots.forEach(root => renderManagementRow(root, 0));

                // Attach event listeners to switches
                document.querySelectorAll('.menu-toggle-switch').forEach(sw => {
                    sw.addEventListener('change', async function() {
                        const key = this.getAttribute('data-key');
                        const isAllowed = this.checked ? 1 : 0;
                        const statusEl = document.getElementById('menu-mgmt-status');

                        this.disabled = true;

                        try {
                            const res = await callSp('spUser_Menu_Save', [username, key, isAllowed]);

                            statusEl.classList.remove('d-none', 'alert-success', 'alert-danger');
                            if (res.ok) {
                                statusEl.classList.add('alert-success');
                                statusEl.textContent = `Successfully updated: ${key}`;
                                // Refresh the top menu to reflect changes
                                loadMenu();
                            } else {
                                statusEl.classList.add('alert-danger');
                                statusEl.textContent = `Failed to update ${key}. Error code: ${res.rc}`;
                                this.checked = !this.checked; // Revert
                            }
                        } catch (err) {
                            statusEl.classList.remove('d-none', 'alert-success');
                            statusEl.classList.add('alert-danger');
                            statusEl.textContent = `Server error while updating ${key}.`;
                            this.checked = !this.checked; // Revert
                        } finally {
                            this.disabled = false;
                            // Auto-hide status after 3 seconds
                            setTimeout(() => statusEl.classList.add('d-none'), 3000);
                        }
                    });
                });
            }

            function renderMenu(allItems, userPerms) {
                const menuContainer = document.getElementById('main-menu');
                menuContainer.innerHTML = '';

                // userPerms is array of { MenuName: '...' }
                const allowedKeys = new Set(userPerms.map(p => (p.MenuName || p.MenuKey || '').trim()));

                // Filter out security keys (sec*) and unauthorized keys
                let visibleItems = allItems.filter(item => {
                    const key = (item.MenuKey || '').trim();
                    if (key.startsWith('sec')) return false; // Security only
                    return allowedKeys.has(key);
                });

                // Sort by SortPath to ensure proper ordering
                visibleItems.sort((a, b) => {
                    const pathA = (a.SortPath || '').toString();
                    const pathB = (b.SortPath || '').toString();
                    return pathA.localeCompare(pathB);
                });

                if (visibleItems.length === 0) {
                    menuContainer.innerHTML = '<li class="nav-item"><span class="nav-link disabled small">No authorized items.</span></li>';
                    return;
                }

                // Map items by ParentMenuItemId for recursive building
                // Items are already sorted by SortPath, so they will be added in order
                const itemsByParent = {};
                visibleItems.forEach(item => {
                    const pid = item.ParentMenuItemId || 'root';
                    if (!itemsByParent[pid]) itemsByParent[pid] = [];
                    itemsByParent[pid].push(item);
                });

                // Get true roots (items that are NOT children of any other visible item)
                const visibleIds = new Set(visibleItems.map(item => item.MenuItemId));
                const roots = visibleItems.filter(item => {
                    // If it has no parent, it's a root
                    if (!item.ParentMenuItemId) return true;
                    // If its parent is not in our visible set, it acts as a root
                    return !visibleIds.has(item.ParentMenuItemId);
                });

                // Track which items have already been rendered to avoid duplicates
                const renderedIds = new Set();

                console.log('Roots to render:', roots.map(r => r.Caption));

                roots.forEach(item => {
                    if (renderedIds.has(item.MenuItemId)) return;
                    // renderedIds.add(item.MenuItemId); // Wait to add until after children are checked

                    const children = itemsByParent[item.MenuItemId] || [];
                    const li = document.createElement('li');

                    if (children.length > 0) {
                        li.className = 'nav-item dropdown';
                        li.appendChild(createDropdownToggle(item, 'nav-link'));
                        li.appendChild(buildDropdownMenu(item.MenuItemId, itemsByParent, renderedIds));
                    } else {
                        li.className = 'nav-item';
                        li.appendChild(createNavLink(item, 'nav-link'));
                    }
                    menuContainer.appendChild(li);
                    renderedIds.add(item.MenuItemId);
                });

                // Initialize multi-level dropdown logic
                setupMultiLevelDropdowns();
            }

            function buildDropdownMenu(parentId, itemsByParent, renderedIds) {
                const ul = document.createElement('ul');
                ul.className = 'dropdown-menu shadow-sm';

                const children = itemsByParent[parentId] || [];
                console.log(`Building sub-menu for ID ${parentId}, children:`, children.length);

                children.forEach(child => {
                    if (renderedIds.has(child.MenuItemId)) return;
                    // renderedIds.add(child.MenuItemId); // Wait

                    const li = document.createElement('li');
                    const grandChildren = itemsByParent[child.MenuItemId] || [];

                    if (child.MenuKey && child.MenuKey.startsWith('sep')) {
                        const hr = document.createElement('hr');
                        hr.className = 'dropdown-divider';
                        li.appendChild(hr);
                    } else if (grandChildren.length > 0) {
                        li.className = 'dropdown-submenu';
                        li.appendChild(createDropdownToggle(child, 'dropdown-item'));
                        li.appendChild(buildDropdownMenu(child.MenuItemId, itemsByParent, renderedIds));
                    } else {
                        li.appendChild(createNavLink(child, 'dropdown-item'));
                    }
                    ul.appendChild(li);
                    renderedIds.add(child.MenuItemId);
                });
                return ul;
            }

            function createNavLink(item, className) {
                const a = document.createElement('a');
                a.className = className;
                a.href = '#';
                a.textContent = item.Caption || item.MenuKey;

                // Handle specific menu actions
                const key = (item.MenuKey || '').trim();
                a.addEventListener('click', (e) => {
                    e.preventDefault();
                    navigateToPage(key, item.Caption);
                });

                return a;
            }

            async function navigateToPage(key, caption) {
                console.log('Navigating to:', key);

                const loading = document.getElementById('content-loading');
                const dynamicContent = document.getElementById('dynamic-content');

                // Normalize key for home
                let pageKey = key;
                if (key === 'mnuMainDashboard' || key === 'mnuHome' || !key) {
                    pageKey = 'home';
                }

                loading.classList.remove('d-none');
                dynamicContent.innerHTML = '';

                try {
                    const response = await fetch(`/dashboard/page/${pageKey}`, {
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'text/html'
                        }
                    });

                    if (response.ok) {
                        const html = await response.text();
                        dynamicContent.innerHTML = html;

                        // Re-initialize specific page logic
                        if (pageKey === 'home') {
                            await initializeHomePage();
                        } else if (pageKey === 'mnuUserSecurity') {
                            // We need access to menu data for management UI
                            const menuRes = await callSp('GetMenuItems', [], null);
                            const permRes = await callSp('spUser_Menus', [username]);
                            if (menuRes.ok && permRes.ok) {
                                renderMenuManagement(menuRes.data, permRes.data);
                            }
                        }
                    } else {
                        dynamicContent.innerHTML = `
                            <div class="card shadow-sm mb-4">
                                <div class="card-body p-4 text-center py-5">
                                    <h3 class="card-title h5 fw-bold mb-3">${caption || key}</h3>
                                    <p class="text-muted mb-0">The page for "${key}" is currently under development.</p>
                                </div>
                            </div>`;
                    }
                } catch (err) {
                    console.error('Navigation error:', err);
                    dynamicContent.innerHTML = '<div class="alert alert-danger">An error occurred while loading the page.</div>';
                } finally {
                    loading.classList.add('d-none');
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            }

            async function initializeHomePage() {
                // Fetch company information using spCompany_Get
                callSp('spCompany_Get', [1])
                .then(result => {
                    if (result.ok && result.data.length > 0) {
                        const company = result.data[0];
                        const container = document.getElementById('company-data');
                        const info = document.getElementById('company-info');
                        if (container && info) {
                            info.classList.remove('d-none');

                            // Extract Company Name
                            const name = company.CompanyName || company.Name || company.Description || 'Unknown Company';

                            // Extract Address parts
                            const addr1 = company.ShippingAddress || company.Address1 || company.Address || '';
                            const addr2 = company.Address2 || '';
                            const city = company.ShippingCity || company.City || '';
                            const state = company.ShippingState || company.State || company.Province || '';
                            const zip = company.ShippingZip || company.Zip || company.PostalCode || company.ZipCode || '';

                            let addressHtml = '';
                            if (addr1) addressHtml += `<div>${addr1}</div>`;
                            if (addr2) addressHtml += `<div>${addr2}</div>`;

                            let cityLine = '';
                            if (city) cityLine += city;
                            if (state) cityLine += (cityLine ? ', ' : '') + state;
                            if (zip) cityLine += (cityLine ? ' ' : '') + zip;
                            if (cityLine) addressHtml += `<div>${cityLine}</div>`;

                            // Extract Phone
                            const phone = company.MainPhone || company.Phone || company.PhoneNumber || company.Telephone || '';
                            let phoneHtml = phone ? `<div class="mt-2 small text-muted"><i class="bi bi-telephone"></i> ${phone}</div>` : '';

                            container.innerHTML = `
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-dark mb-1">${name}</div>
                                        <div class="small text-muted">${addressHtml}</div>
                                        ${phoneHtml}
                                    </div>
                                </div>
                            `;
                        }
                    }
                });

                // Fetch user information using spUser_GetByID
                callSp('spUser_GetByID', [username])
                .then(result => {
                    if (result.ok && result.data.length > 0) {
                        const user = result.data[0];
                        const container = document.getElementById('user-data');
                        const info = document.getElementById('user-info');
                        if (container && info) {
                            info.classList.remove('d-none');
                            // Simple display: Name and Email
                            const name = user.UserName || user.Name || user.UserID || 'Unknown User';
                            const email = user.Email || 'No email provided';
                            container.innerHTML = `
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-dark">${name}</div>
                                        <div class="small text-muted">${email}</div>
                                    </div>
                                </div>
                            `;
                        }
                    }
                });
            }

            function createDropdownToggle(item, className) {
                const a = document.createElement('a');
                a.className = `${className} dropdown-toggle`;
                a.href = '#';
                // ONLY add data-bs-toggle for top-level nav-links
                // Bootstrap 5 will break nested menus if we add it to dropdown-items
                if (className.includes('nav-link')) {
                    a.setAttribute('data-bs-toggle', 'dropdown');
                }
                a.setAttribute('aria-expanded', 'false');
                a.textContent = item.Caption || item.MenuKey;
                return a;
            }

            function setupMultiLevelDropdowns() {
                console.log('Initializing multi-level dropdowns...');

                // Multi-level dropdown trigger
                document.querySelectorAll('.dropdown-submenu .dropdown-toggle').forEach(element => {
                    element.addEventListener('click', function (e) {
                        console.log('Submenu toggle clicked:', this.textContent);
                        e.preventDefault();
                        e.stopPropagation();

                        const submenu = this.nextElementSibling;
                        if (!submenu) {
                            console.warn('No submenu found for:', this.textContent);
                            return;
                        }

                        // Close other open submenus at the SAME level
                        const parentMenu = this.closest('.dropdown-menu');
                        if (parentMenu) {
                            parentMenu.querySelectorAll('.dropdown-menu.show').forEach(openMenu => {
                                if (openMenu !== submenu) {
                                    openMenu.classList.remove('show');
                                }
                            });
                        }

                        submenu.classList.toggle('show');
                        console.log('Submenu toggled. Visible:', submenu.classList.contains('show'));
                    });
                });

                // Ensure top-level dropdowns don't close when clicking a submenu toggle
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.addEventListener('click', function(e) {
                        if (e.target.classList.contains('dropdown-toggle')) {
                            e.stopPropagation();
                        }
                    });
                });

                // Close all submenus when any top-level dropdown is closed by Bootstrap
                document.querySelectorAll('.nav-item.dropdown').forEach(dropdown => {
                    dropdown.addEventListener('hidden.bs.dropdown', function () {
                        this.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                            menu.classList.remove('show');
                        });
                    });
                });
            }

            document.getElementById('brand-link').addEventListener('click', (e) => {
                e.preventDefault();
                navigateToPage('mnuHome');
            });

            loadMenu();
            navigateToPage('mnuHome');
        }
    </script>
</body>
</html>
