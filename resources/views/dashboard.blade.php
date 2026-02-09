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
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-semibold mb-4 text-gray-800">Welcome to TLS</h2>
            <p class="text-gray-600">You have successfully logged in.</p>
        </div>
    </main>

    <script>
        // Simple placeholder for dashboard logic
        const user = localStorage.getItem('tls_user');
        if (user) {
            document.getElementById('user-display').textContent = `Logged in as: ${user}`;
        }
    </script>
</body>
</html>
