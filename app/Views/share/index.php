<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><?= esc($title) ?></h4>
        <a href="<?= site_url('radiology') ?>" class="btn btn-sm btn-outline-secondary">&larr; Back to search</a>
    </div>

    <?php if (session()->getFlashdata('message')): ?>
        <div class="alert alert-success py-2"><?= esc(session()->getFlashdata('message')) ?></div>
    <?php endif; ?>

    <div class="table-responsive bg-white shadow-sm rounded">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Created</th>
                    <?php if ($isAdmin): ?><th>By</th><?php endif; ?>
                    <th>Patient</th>
                    <th>Accession</th>
                    <th>Valid until</th>
                    <th>Opened</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($shares)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No share links yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($shares as $s): ?>
                <?php $reason = \App\Models\ReportShareModel::blockedReason($s); ?>
                <tr>
                    <td class="small"><?= esc($s['created_at']) ?></td>
                    <?php if ($isAdmin): ?><td class="small"><?= esc($s['created_by_username']) ?></td><?php endif; ?>
                    <td class="small"><?= esc($s['patient_id']) ?> - <?= esc($s['patient_name']) ?></td>
                    <td class="small"><?= esc($s['accession_number']) ?></td>
                    <td class="small"><?= esc($s['expires_at']) ?></td>
                    <td class="small">
                        <?= (int) $s['view_count'] ?><?= (int) $s['max_views'] > 0 ? ' / ' . (int) $s['max_views'] : '' ?>
                        <?= $s['last_accessed_at'] ? '<br><span class="text-muted">' . esc($s['last_accessed_at']) . '</span>' : '' ?>
                    </td>
                    <td>
                        <?php if ($reason === null): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?= esc(ucfirst($reason)) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <?php if ($reason === null): ?>
                            <form method="post" action="<?= site_url('radiology/shares/revoke/' . $s['id']) ?>" class="d-inline"
                                  onsubmit="return confirm('Revoke this link? The patient will no longer be able to open it.')">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger">Revoke</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-3"><?= $pager ? $pager->links() : '' ?></div>
</div>
</body>
</html>
