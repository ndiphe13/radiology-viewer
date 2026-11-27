<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Hasil Radiologi / Radiology Result</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container" style="max-width: 420px;">
    <div class="card shadow-sm mt-5">
        <div class="card-body text-center">
            <h5><?= esc($org) ?></h5>
            <p class="mb-2"><?= esc($message) ?></p>
            <p class="text-muted small mb-0">
                Silakan hubungi bagian radiologi untuk link baru.<br>
                Please contact the radiology department for a new link.
            </p>
        </div>
    </div>
</div>
</body>
</html>
