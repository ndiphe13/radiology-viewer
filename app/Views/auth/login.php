<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Radiology Viewer - Login</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>

<body class="bg-light">

<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-5 col-lg-4">

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">

                    <h4 class="text-center mb-2">
                        CA SRG Meddiff - Radiology Report Viewer
                    </h4>

                    <p class="text-center text-muted mb-4">
                        Please sign in to continue
                    </p>

                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger">
                            <?= esc(session()->getFlashdata('error')) ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?= site_url('login') ?>">

                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input
                                type="text"
                                name="username"
                                class="form-control"
                                value="<?= esc(old('username')) ?>"
                                autocomplete="username"
                                required
                                autofocus
                            >
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Password</label>
                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                autocomplete="current-password"
                                required
                            >
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Sign In
                        </button>

                    </form>

                </div>
            </div>

            <p class="text-center text-muted mt-3 small">
                Authorized personnel only
            </p>

        </div>
    </div>
</div>

</body>
</html>
