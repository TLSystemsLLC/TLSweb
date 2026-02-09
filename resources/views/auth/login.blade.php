<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TLS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold mb-6 text-center text-gray-800">TLS Login</h1>

        <div id="error-message" class="hidden mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
            Invalid credentials.
        </div>

        <form id="login-form" class="space-y-4">
            @csrf
            <div>
                <label for="login" class="block text-sm font-medium text-gray-700">Login (tenant.username)</label>
                <input type="text" id="login" name="login" required
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="mrwr.tlyle">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="password" name="password" required
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <button type="submit" id="submit-btn"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Sign in
                </button>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            const errorMsg = document.getElementById('error-message');
            const submitBtn = document.getElementById('submit-btn');
            const loginInput = document.getElementById('login').value;
            const passwordInput = document.getElementById('password').value;

            errorMsg.classList.add('hidden');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Signing in...';

            try {
                const response = await fetch('/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: JSON.stringify({
                        login: loginInput,
                        password: passwordInput
                    })
                });

                const result = await response.json();

                if (response.ok && result.ok) {
                    // Success! Store user in local storage for the dashboard display
                    localStorage.setItem('tls_user', loginInput);
                    window.location.href = '/dashboard';
                } else {
                    errorMsg.textContent = result.error || 'Login failed.';
                    errorMsg.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Login error:', error);
                errorMsg.textContent = 'A system error occurred.';
                errorMsg.classList.remove('hidden');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Sign in';
            }
        });
    </script>
</body>
</html>
