<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} API Documentation</title>

    <!-- Stoplight Elements CSS -->
    <link rel="stylesheet" href="https://unpkg.com/@stoplight/elements@8.0.5/styles.min.css">
</head>
<body>
    <div id="api-docs"></div>

    <!-- Stoplight Elements JS -->
    <script src="https://unpkg.com/@stoplight/elements@8.0.5/web-components.min.js"></script>

    <script>
        const apiDocs = document.getElementById('api-docs');
        apiDocs.innerHTML = `
            <elements-api
                apiDescriptionUrl="{{ route('scramble.openapi') }}"
                router="history"
                layout="sidebar"
                hideTryIt="false"
                hideSchemas="false"
                hideDownloadButton="false"
                hideExport="false"
            />
        `;
    </script>
</body>
</html>
