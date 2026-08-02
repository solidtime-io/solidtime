{{-- Self-contained on purpose: this page is rendered for a request on an
     untrusted host, so it must not call url()/route()/asset(), which would
     re-trigger Host validation and throw again. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Untrusted host</title>
    <style>
        html, body { height: 100%; margin: 0; }
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f5f5;
            color: #1f2937;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        .card {
            max-width: 32rem;
            margin: 1.5rem;
            padding: 2rem;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }
        h1 { margin: 0 0 0.75rem; font-size: 1.25rem; }
        p { margin: 0; line-height: 1.6; color: #4b5563; }
        code {
            padding: 0.1rem 0.35rem;
            background: #f3f4f6;
            border-radius: 0.25rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Untrusted host</h1>
        <p>
            This hostname is not configured for this instance. Set
            <code>APP_URL</code>, or add the host to <code>TRUSTED_HOSTS</code>.
        </p>
    </div>
</body>
</html>
