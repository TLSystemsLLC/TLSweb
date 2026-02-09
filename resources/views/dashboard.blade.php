<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TLS</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-light min-vh-100">
    <nav class="navbar navbar-expand navbar-dark bg-primary shadow">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">TLS Dashboard</a>
            <div class="navbar-nav ms-auto align-items-center">
                <span id="user-display" class="nav-link text-white-50 small me-3"></span>
                <form action="/logout" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-light btn-sm">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="container py-5">
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

    <script>
        const login = "{{ session('user_login') }}";
        if (login) {
            document.getElementById('user-display').textContent = `Logged in as: ${login}`;

            const [tenant, ...userParts] = login.split('.');
            const username = userParts.join('.');

            // Fetch company information using spCompany_Get
            // For now we assume CompanyID 1 is the default for the tenant
            fetch('/api/sp', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    login: login,
                    proc: 'spCompany_Get',
                    params: [1]
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.ok && result.data.length > 0) {
                    const company = result.data[0];
                    const container = document.getElementById('company-data');
                    document.getElementById('company-info').classList.remove('d-none');

                    container.innerHTML = Object.entries(company)
                        .map(([key, value]) => `<div><strong>${key}:</strong> ${value}</div>`)
                        .join('');
                }
            })
            .catch(err => console.error('Failed to fetch company info:', err));

            // Fetch user information using spUser_GetByID
            fetch('/api/sp', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    login: login,
                    proc: 'spUser_GetByID',
                    params: [username]
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.ok && result.data.length > 0) {
                    const user = result.data[0];
                    const container = document.getElementById('user-data');
                    document.getElementById('user-info').classList.remove('d-none');

                    container.innerHTML = Object.entries(user)
                        .map(([key, value]) => `<div><strong>${key}:</strong> ${value}</div>`)
                        .join('');
                }
            })
            .catch(err => console.error('Failed to fetch user info:', err));
        }
    </script>
</body>
</html>
