<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Form - Radiology Viewer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="<?= base_url('assets/css/brand.css') ?>" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container p-4" style="max-width: 700px;">

    <?php $isEdit = ($mode === 'edit'); ?>

    <h3 class="mb-4">
        <?= $isEdit ? 'Edit User' : 'Create New User' ?>
    </h3>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger">
            <?= esc(session()->getFlashdata('error')) ?>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger">
            <?php foreach (session()->getFlashdata('errors') as $error): ?>
                <div><?= esc($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="post"
                  action="<?= $isEdit
                      ? site_url('users/update/' . $user['id'])
                      : site_url('users/create') ?>">

                <?= csrf_field() ?>

                <?php if (!$isEdit): ?>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username"
                               class="form-control"
                               value="<?= esc(old('username')) ?>"
                               maxlength="100"
                               required>
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control"
                               value="<?= esc($user['username']) ?>"
                               disabled>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name"
                           class="form-control"
                           value="<?= esc(old('full_name', $user['full_name'] ?? '')) ?>"
                           maxlength="150"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select" required>
                        <?php
                        $selectedRole = old('role', $user['role'] ?? 'radiology');
                        ?>
                        <option value="admin" <?= $selectedRole === 'admin' ? 'selected' : '' ?>>
                            Admin
                        </option>
                        <option value="radiology" <?= $selectedRole === 'radiology' ? 'selected' : '' ?>>
                            Radiology
                        </option>
                        <option value="auditor" <?= $selectedRole === 'auditor' ? 'selected' : '' ?>>
                            Auditor
                        </option>
                    </select>
                </div>

                <?php if ($isEdit): ?>
                    <div class="mb-3">
                        <label class="form-label">Account Status</label>
                        <?php $selectedStatus = old('is_active', (string) $user['is_active']); ?>
                        <select name="is_active" class="form-select" required>
                            <option value="1" <?= (string) $selectedStatus === '1' ? 'selected' : '' ?>>
                                Active
                            </option>
                            <option value="0" <?= (string) $selectedStatus === '0' ? 'selected' : '' ?>>
                                Inactive
                            </option>
                        </select>
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label">Initial Password</label>
                        <input type="password" name="password"
                               class="form-control"
                               minlength="12"
                               autocomplete="new-password"
                               required>
                        <small class="text-muted">
                            Minimum 12 characters.
                        </small>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between">
                    <a href="<?= site_url('users') ?>" class="btn btn-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Save User
                    </button>
                </div>

            </form>

            <?php if ($isEdit): ?>
                <hr class="my-4">

                <h5>Reset Password</h5>

                <form method="post"
                      action="<?= site_url('users/reset-password/' . $user['id']) ?>">

                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password"
                               class="form-control"
                               minlength="12"
                               autocomplete="new-password"
                               required>
                    </div>

                    <button type="submit" class="btn btn-warning">
                        Reset Password
                    </button>
                </form>
            <?php endif; ?>

        </div>
    </div>

</div>

</body>
</html>
