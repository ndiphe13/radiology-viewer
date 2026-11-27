<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Audit Log - Radiology Viewer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="<?= base_url('assets/css/brand.css') ?>" rel="stylesheet">
    <style>
        .ua { max-width: 260px; font-size: .8rem; color: #6c757d; }
        .patient-name { font-weight: 500; }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid p-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3>Audit Log</h3>
            <span class="text-muted">Who logged in, which patient reports were opened, and user changes</span>
        </div>
        <a href="<?= site_url('radiology') ?>" class="btn btn-secondary">
            Back to Radiology
        </a>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="get" action="<?= site_url('audit') ?>" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">User</label>
                    <select name="user" class="form-select">
                        <option value="">All users</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= esc($u, 'attr') ?>" <?= $filters['user'] === $u ? 'selected' : '' ?>><?= esc($u) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Event</label>
                    <select name="event" class="form-select">
                        <option value="">All events</option>
                        <?php foreach ($events as $e): ?>
                            <option value="<?= esc($e, 'attr') ?>" <?= $filters['event'] === $e ? 'selected' : '' ?>><?= esc($e) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Patient</label>
                    <input type="text" name="patient" class="form-control"
                           placeholder="Name, patient ID or accession"
                           value="<?= esc($filters['patient'], 'attr') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="<?= esc($filters['date_from'], 'attr') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="<?= esc($filters['date_to'], 'attr') ?>">
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
                <div class="col-12">
                    <a href="<?= site_url('audit') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
                    <a href="<?= site_url('audit?event=REPORT_VIEW') ?>" class="btn btn-sm btn-outline-primary">Only report views</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <div class="d-flex justify-content-between mb-2">
                <span class="badge bg-primary"><?= number_format($total) ?> Records</span>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered table-sm align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Username</th>
                            <th>Event</th>
                            <th>Patient Name</th>
                            <th>Patient ID</th>
                            <th>Accession</th>
                            <th>Report Date</th>
                            <th>IP Address</th>
                            <th>User Agent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $log): ?>
                                <?php $isReport = $log['target_type'] === 'RADIOLOGY_REPORT'; ?>
                                <tr>
                                    <td class="text-nowrap"><?= esc($log['created_at']) ?></td>
                                    <td><?= esc($log['username'] ?? '') ?></td>
                                    <td><?= esc($log['event_type']) ?></td>

                                    <?php if ($isReport && $log['patient_name'] !== null): ?>
                                        <td class="patient-name"><?= esc($log['patient_name']) ?></td>
                                        <td class="text-nowrap"><?= esc($log['patient_id']) ?></td>
                                        <td><?= esc($log['accession_number']) ?></td>
                                        <td class="text-nowrap"><?= esc($log['report_date'] ?? '-') ?></td>
                                    <?php elseif ($isReport): ?>
                                        <td colspan="4" class="text-muted">Report #<?= esc($log['target_id']) ?> (no longer in database)</td>
                                    <?php elseif ($log['target_type'] === 'USER'): ?>
                                        <td colspan="4" class="text-muted">User: <?= esc($log['target_id']) ?></td>
                                    <?php else: ?>
                                        <td colspan="4"></td>
                                    <?php endif; ?>

                                    <td class="text-nowrap"><?= esc($log['ip_address'] ?? '') ?></td>
                                    <td class="ua text-truncate" title="<?= esc($log['user_agent'] ?? '', 'attr') ?>"><?= esc($log['user_agent'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">
                                    No audit records found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center">
                <?= $pager ?>
            </div>

        </div>
    </div>

</div>

</body>
</html>
