<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Management - Radiology Viewer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="<?= base_url('assets/css/brand.css') ?>" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container-fluid p-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3>User Management</h3>
            <span class="text-muted">
                Manage Radiology Viewer users
            </span>
        </div>

        <div>
            <a href="<?= site_url('users/new') ?>" class="btn btn-primary">
                + Add User
            </a>
            <a href="<?= site_url('radiology') ?>" class="btn btn-secondary">
                Back to Radiology
            </a>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success">
            <?= esc(session()->getFlashdata('success')) ?>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger">
            <?= esc(session()->getFlashdata('error')) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= esc($user['id']) ?></td>
                            <td><?= esc($user['username']) ?></td>
                            <td><?= esc($user['full_name']) ?></td>
                            <td>
                                <span class="badge bg-info text-dark">
                                    <?= esc(ucfirst($user['role'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ((int) $user['is_active'] === 1): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?= esc($user['last_login_at'] ?? '-') ?></td>
                            <td class="text-nowrap">
                                <a href="<?= site_url('users/edit/' . $user['id']) ?>"
                                   class="btn btn-sm btn-primary">
                                    Edit
                                </a>

                                <?php if ((int) $user['id'] === (int) session()->get('user_id')): ?>
                                    <?php // own account: no delete button ?>
                                <?php elseif ((int) $user['is_active'] === 1): ?>
                                    <span class="d-inline-block" tabindex="0"
                                          title="Nonaktifkan user terlebih dahulu untuk menghapus">
                                        <button type="button" class="btn btn-sm btn-outline-danger" disabled>
                                            Delete
                                        </button>
                                    </span>
                                <?php else: ?>
                                    <form method="post"
                                          action="<?= site_url('users/delete/' . $user['id']) ?>"
                                          class="d-inline"
                                          onsubmit="return confirm(<?= esc(json_encode('Hapus user "' . $user['username'] . '" secara permanen? Tindakan ini tidak dapat dibatalkan.'), 'attr') ?>)">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            Delete
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="text-center">
                                No users found.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <small class="text-muted">
                Total users: <?= count($users) ?>
            </small>

        </div>
    </div>

</div>

</body>
</html>
