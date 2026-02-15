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
                    <a class="navbar-brand fw-bold" href="#">TLS Dashboard</a>
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
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="card-title h4 fw-bold mb-3">Welcome to TLS</h2>
                        <p class="card-text text-muted">You have successfully logged in.</p>
                    </div>
                </div>

                <div id="company-info" class="card shadow-sm d-none mb-4">
                    <div class="card-body p-4">
                        <h3 class="card-title h5 fw-bold mb-3">Company Information</h3>
                        <div id="company-data" class="vstack gap-2 text-muted">
                            <!-- Data will be injected here -->
                        </div>
                    </div>
                </div>

                <div id="user-info" class="card shadow-sm d-none">
                    <div class="card-body p-4">
                        <h3 class="card-title h5 fw-bold mb-3">User Information</h3>
                        <div id="user-data" class="vstack gap-2 text-muted">
                            <!-- Data will be injected here -->
                        </div>
                    </div>
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

            function renderMenu(allItems, userPerms) {
                const menuContainer = document.getElementById('main-menu');
                menuContainer.innerHTML = '';

                // userPerms is array of { MenuName: '...' }
                const allowedKeys = new Set(userPerms.map(p => (p.MenuName || p.MenuKey || '').trim()));

                // Filter out security keys (sec*) and unauthorized keys
                const visibleItems = allItems.filter(item => {
                    const key = (item.MenuKey || '').trim();
                    if (key.startsWith('sec')) return false; // Security only
                    return allowedKeys.has(key);
                });

                if (visibleItems.length === 0) {
                    menuContainer.innerHTML = '<li class="nav-item"><span class="nav-link disabled small">No authorized items.</span></li>';
                    return;
                }

                // Map items by ParentMenuItemId for recursive building
                const itemsByParent = {};
                visibleItems.forEach(item => {
                    const pid = item.ParentMenuItemId || 'root';
                    if (!itemsByParent[pid]) itemsByParent[pid] = [];
                    itemsByParent[pid].push(item);
                });

                // Get true roots (no parent OR mnuMain at top level)
                const roots = visibleItems.filter(item => !item.ParentMenuItemId || item.MenuKey.startsWith('mnuMain'));

                // Track which items have already been rendered as roots to avoid duplicates
                const renderedRootIds = new Set();

                roots.forEach(item => {
                    if (renderedRootIds.has(item.MenuItemId)) return;
                    renderedRootIds.add(item.MenuItemId);

                    const children = itemsByParent[item.MenuItemId] || [];
                    const li = document.createElement('li');

                    if (children.length > 0) {
                        li.className = 'nav-item dropdown';
                        li.appendChild(createDropdownToggle(item, 'nav-link'));
                        li.appendChild(buildDropdownMenu(item.MenuItemId, itemsByParent));
                    } else {
                        li.className = 'nav-item';
                        li.appendChild(createNavLink(item, 'nav-link'));
                    }
                    menuContainer.appendChild(li);
                });

                // Initialize multi-level dropdown logic
                setupMultiLevelDropdowns();
            }

            function buildDropdownMenu(parentId, itemsByParent) {
                const ul = document.createElement('ul');
                ul.className = 'dropdown-menu shadow-sm';

                const children = itemsByParent[parentId] || [];
                children.forEach(child => {
                    const li = document.createElement('li');
                    const grandChildren = itemsByParent[child.MenuItemId] || [];

                    if (child.MenuKey && child.MenuKey.startsWith('sep')) {
                        const hr = document.createElement('hr');
                        hr.className = 'dropdown-divider';
                        li.appendChild(hr);
                    } else if (grandChildren.length > 0) {
                        li.className = 'dropdown-submenu';
                        li.appendChild(createDropdownToggle(child, 'dropdown-item'));
                        li.appendChild(buildDropdownMenu(child.MenuItemId, itemsByParent));
                    } else {
                        li.appendChild(createNavLink(child, 'dropdown-item'));
                    }
                    ul.appendChild(li);
                });
                return ul;
            }

            function createNavLink(item, className) {
                const a = document.createElement('a');
                a.className = className;
                a.href = '#';
                a.textContent = item.Caption || item.MenuKey;
                return a;
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

            loadMenu();

            // Fetch company information using spCompany_Get
            callSp('spCompany_Get', [1])
            .then(result => {
                if (result.ok && result.data.length > 0) {
                    const company = result.data[0];
                    const container = document.getElementById('company-data');
                    document.getElementById('company-info').classList.remove('d-none');

                    container.innerHTML = Object.entries(company)
                        .map(([key, value]) => `<div><strong>${key}:</strong> ${value}</div>`)
                        .join('');
                }
            });

            // Fetch user information using spUser_GetByID
            callSp('spUser_GetByID', [username])
            .then(result => {
                if (result.ok && result.data.length > 0) {
                    const user = result.data[0];
                    const container = document.getElementById('user-data');
                    document.getElementById('user-info').classList.remove('d-none');

                    container.innerHTML = Object.entries(user)
                        .map(([key, value]) => `<div><strong>${key}:</strong> ${value}</div>`)
                        .join('');
                }
            });
        }
    </script>
</body>
</html>
