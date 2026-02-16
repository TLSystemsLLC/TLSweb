<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TLS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
    <div class="bg-white p-5 rounded shadow-sm w-100" style="max-width: 400px;">
        <h1 class="h3 fw-bold mb-4 text-center text-dark">TLS Login</h1>

        <div id="error-message" class="d-none mb-3 p-3 bg-danger-subtle border border-danger text-danger rounded">
            Invalid credentials.
        </div>

        <form id="login-form" class="vstack gap-3">
            @csrf
            <div>
                <label for="login" class="form-label small fw-medium text-secondary">Login</label>
                <input type="text" id="login" name="login" required
                    class="form-control">
            </div>
            <div>
                <label for="password" class="form-label small fw-medium text-secondary">Password</label>
                <input type="password" id="password" name="password" required
                    class="form-control">
            </div>
            <div class="mt-2">
                <button type="submit" id="submit-btn"
                    class="btn btn-primary w-100 py-2">
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

            errorMsg.classList.add('d-none');
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
                    errorMsg.classList.remove('d-none');
                }
            } catch (error) {
                console.error('Login error:', error);
                errorMsg.textContent = 'A system error occurred.';
                errorMsg.classList.remove('d-none');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Sign in';
            }
        });
    </script>
</body>
</html>
