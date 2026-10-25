<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sedang Dinonaktifkan — DM Kuliner POS</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
               background: #f8fafc; color: #0f172a; font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; }
        .card { max-width: 420px; margin: 16px; padding: 32px 28px; background: #fff; border: 1px solid #e2e8f0;
                border-radius: 16px; box-shadow: 0 1px 3px rgba(15, 23, 42, .06); text-align: center; }
        h1 { margin: 0 0 8px; font-size: 1.25rem; }
        p { margin: 0 0 20px; color: #475569; line-height: 1.5; }
        button, a.btn { display: inline-block; padding: 10px 18px; border-radius: 10px; border: 0; cursor: pointer;
                        background: #2563eb; color: #fff; font-weight: 600; font-size: .9rem; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Aplikasi sedang dinonaktifkan sementara</h1>
        <p>DM Kuliner POS sedang dalam pemeliharaan. Silakan coba lagi nanti atau hubungi owner.</p>

        @auth
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Keluar</button>
            </form>
        @else
            <a class="btn" href="{{ route('login') }}">Login owner / manager</a>
        @endauth
    </div>
</body>
</html>
