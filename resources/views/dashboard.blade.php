<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TLS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-indigo-600 p-4 text-white shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">TLS Dashboard</h1>
            <div class="space-x-4">
                <span id="user-display" class="text-sm opacity-90"></span>
                <form action="/logout" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="text-sm bg-indigo-700 hover:bg-indigo-800 px-3 py-1 rounded">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="container mx-auto p-8">
        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h2 class="text-2xl font-semibold mb-4 text-gray-800">Welcome to TLS</h2>
            <p class="text-gray-600">You have successfully logged in.</p>
        </div>

        <div id="company-info" class="bg-white p-6 rounded-lg shadow-md hidden mb-6">
            <h3 class="text-xl font-semibold mb-4 text-gray-800">Company Information</h3>
            <div id="company-data" class="space-y-2 text-gray-600">
                <!-- Data will be injected here -->
            </div>
        </div>

        <div id="user-info" class="bg-white p-6 rounded-lg shadow-md hidden">
            <h3 class="text-xl font-semibold mb-4 text-gray-800">User Information</h3>
            <div id="user-data" class="space-y-2 text-gray-600">
                <!-- Data will be injected here -->
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
                    document.getElementById('company-info').classList.remove('hidden');

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
                    document.getElementById('user-info').classList.remove('hidden');

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
