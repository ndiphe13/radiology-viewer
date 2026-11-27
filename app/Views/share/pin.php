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
<div class="container" style="max-width: 380px;">
    <div class="card shadow-sm mt-5">
        <div class="card-body text-center">
            <h5><?= esc($org) ?></h5>
            <p class="text-muted small">Masukkan PIN dari slip Anda.<br>Enter the PIN from your slip.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2 small"><?= esc($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= site_url('s/' . $token) ?>">
                <?= csrf_field() ?>
                <input type="text" name="pin" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                       autocomplete="one-time-code" required autofocus
                       class="form-control form-control-lg text-center mb-3" style="letter-spacing: .4rem;">
                <button class="btn btn-primary w-100">Buka / Open</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
