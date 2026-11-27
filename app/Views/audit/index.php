<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Audit Log - Radiology Viewer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container-fluid p-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Audit Log</h3>
        <a href="<?= site_url('radiology') ?>" class="btn btn-secondary">
            Back to Radiology
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-striped table-bordered table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Username</th>
                            <th>Event</th>
                            <th>IP Address</th>
                            <th>User Agent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= esc($log['created_at']) ?></td>
                                    <td><?= esc($log['username'] ?? '') ?></td>
                                    <td><?= esc($log['event_type']) ?></td>
                                    <td><?= esc($log['ip_address'] ?? '') ?></td>
                                    <td><?= esc($log['user_agent'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">
                                    No audit records found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <small class="text-muted">
                Showing the latest 100 records.
            </small>

        </div>
    </div>

</div>

</body>
</html>
