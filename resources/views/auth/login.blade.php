<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container" style="max-width: 400px; margin-top: 100px;">
        <h3 class="mb-4 text-center">CRM Login</h3>
        <div id="error" class="alert alert-danger d-none"></div>
        <form id="login-form">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" id="email" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" id="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
        <hr class="mt-4">
        <div class="small">
            <p class="fw-semibold mb-2">Demo Credentials</p>

            <p class="mb-1 fw-semibold">Manager</p>
            <p class="mb-3 text-muted">manager@crm.test</p>

            <p class="mb-1 fw-semibold">Sales Representatives</p>
            <ul class="text-muted mb-3 ps-3">
                <li>priya.nair@crm.test</li>
                <li>arjun.verma@crm.test</li>
                <li>ananya.iyer@crm.test</li>
                <li>sneha.reddy@crm.test</li>
                <li>karan.malhotra@crm.test</li>
                <li>vikram.singh@crm.test</li>
            </ul>

            <p class="fw-bold mb-0">Password for all accounts: password</p>
        </div>
    </div>

    <script src="/js/api.js"></script>
    <script>
        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const errorBox = document.getElementById('error');
            errorBox.classList.add('d-none');

            try {
                const result = await apiFetch('/login', {
                    method: 'POST',
                    body: JSON.stringify({
                        email: document.getElementById('email').value,
                        password: document.getElementById('password').value,
                    }),
                });
                localStorage.setItem('token', result.data.token);
                localStorage.setItem('user', JSON.stringify(result.data.user));
                window.location.href = '/leads';
            } catch (err) {
                errorBox.textContent = err.body?.errors?.email?.[0] || err.message;
                errorBox.classList.remove('d-none');
            }
        });
    </script>
</body>
</html>
