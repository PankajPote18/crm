<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'CRM')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="/leads">CRM</a>
            <div class="navbar-nav me-auto">
                <a class="nav-link" href="/leads">Leads</a>
                <a class="nav-link" href="/reports">Reports</a>
            </div>
            <span id="current-user" class="text-light me-3 small"></span>
            <button class="btn btn-outline-light btn-sm" onclick="logout()">Logout</button>
        </div>
    </nav>

    <div class="container">
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/api.js"></script>
    <script>
        requireAuth();
        const currentUser = getUser();
        if (currentUser) {
            document.getElementById('current-user').textContent = `${currentUser.name} (${currentUser.role})`;
        }
    </script>
    @yield('scripts')
</body>
</html>
