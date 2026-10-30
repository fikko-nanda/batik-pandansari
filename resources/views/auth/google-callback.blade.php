<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menghubungkan Akun Google — Batik Pandansari</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <script>
        const token = @json($token ?? '');
        const user = @json($user ?? null);

        if (token) {
            try {
                localStorage.setItem('pandansari_token', token);
                if (user) {
                    localStorage.setItem('pandansari_user', JSON.stringify(user));
                }
            } catch (e) {
                console.error('Storage error:', e);
            }
        }

        const peran = String(user?.peran || user?.role || '').toLowerCase();
        let redirectTarget = '/';
        if (peran === 'admin') {
            redirectTarget = '/admin/dashboard';
        } else if (peran === 'teknisi') {
            redirectTarget = '/teknisi';
        }

        window.onload = function() {
            setTimeout(function() {
                window.location.href = redirectTarget;
            }, 600);
        };
    </script>
    <style>
        body {
            background-color: #FAF8F5;
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            color: #0F1A24;
        }
        .card {
            background: #ffffff;
            border: 1px solid #E2E8F0;
            border-radius: 1rem;
            padding: 2.5rem;
            text-align: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            max-width: 400px;
            width: 90%;
        }
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #E2E8F0;
            border-top-color: #0284C7;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 1.25rem;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        h2 { font-size: 1.25rem; margin: 0 0 0.5rem; font-weight: 700; }
        p { font-size: 0.875rem; color: #64748B; margin: 0; }
    </style>
</head>
<body>
    <div class="card">
        <div class="spinner"></div>
        <h2>Berhasil Masuk via Google</h2>
        <p>Mengalihkan ke akun Anda...</p>
    </div>
</body>
</html>

