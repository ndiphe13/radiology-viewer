<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>
        body {
            background: #f4f6f9;
        }

        .main-container {
            max-width: 1400px;
            margin: 16px auto;
        }

        .page-title {
            font-weight: 600;
            color: #263445;
        }

        .search-card {
            border: none;
            border-radius: 10px;
        }

        .table th {
            background: #eef2f7;
            white-space: nowrap;
        }

        .table td {
            vertical-align: middle;
        }

        .navbar-brand {
            font-weight: 600;
        }

        .user-info {
            color: #ffffff;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="<?= base_url('assets/css/brand.css') ?>" rel="stylesheet">
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container-fluid px-4">

        <a class="navbar-brand" href="<?= site_url('radiology') ?>">
            Radiology Report Viewer
        </a>

        <div class="d-flex align-items-center gap-3">

            <div class="user-info text-end">
                <div>
                    <?= esc(session()->get('full_name')) ?>
                </div>
                <small class="text-secondary">
                    <?= esc(ucfirst(session()->get('role'))) ?>
                </small>
            </div>

            <?php if (session()->get('role') === 'admin' || session()->get('role') === 'auditor'): ?>
                <a href="<?= site_url('audit') ?>"
                   class="btn btn-outline-info btn-sm">
                    Audit Log
                </a>
            <?php endif; ?>

            <?php if (session()->get('role') === 'admin'): ?>
                <a href="<?= site_url('users') ?>"
                   class="btn btn-outline-warning btn-sm">
                    User Management
                </a>
            <?php endif; ?>

            <form action="<?= site_url('logout') ?>"
                  method="post"
                  class="mb-0">

                <?= csrf_field() ?>

                <button type="submit"
                        class="btn btn-outline-light btn-sm"
                        onclick="return confirm('Apakah Anda yakin ingin logout?')">
                    Logout
                </button>

            </form>

        </div>
    </div>
</nav>

<!-- MAIN CONTENT -->
<div class="container-fluid main-container">

    <!-- FLASH MESSAGE -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= esc(session()->getFlashdata('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= esc(session()->getFlashdata('error')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

<?php
    $activeFilters = count(array_filter([$dateFrom ?? '', $dateTo ?? '', $timeFrom ?? '', $timeTo ?? ''], static fn($v) => $v !== ''));
    $showFilters   = $activeFilters > 0 || !empty($filterError);
?>
    <!-- SEARCH -->
    <div class="card search-card shadow-sm mb-3">
        <div class="card-body py-3">
            <form action="<?= site_url('radiology') ?>" method="get">

                <div class="row g-2 align-items-center">
                    <div class="col-md-3 col-xl-2">
                        <select name="type" class="form-select" aria-label="Search by">
                            <option value="all" <?= ($searchBy ?? 'all') === 'all' ? 'selected' : '' ?>>All Fields</option>
                            <option value="patient_id" <?= ($searchBy ?? '') === 'patient_id' ? 'selected' : '' ?>>Patient ID</option>
                            <option value="patient_name" <?= ($searchBy ?? '') === 'patient_name' ? 'selected' : '' ?>>Patient Name</option>
                            <option value="accession_number" <?= ($searchBy ?? '') === 'accession_number' ? 'selected' : '' ?>>Accession Number</option>
                        </select>
                    </div>

                    <div class="col">
                        <input type="search" name="q" class="form-control"
                               placeholder="Search patient ID, name or accession number"
                               aria-label="Search keyword"
                               value="<?= esc($keyword ?? '') ?>" autofocus>
                    </div>

                    <div class="col-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">Search</button>
                        <button type="button" class="btn btn-outline-primary"
                                data-bs-toggle="collapse" data-bs-target="#searchFilters"
                                aria-expanded="<?= $showFilters ? 'true' : 'false' ?>" aria-controls="searchFilters">
                            Filters<?php if ($activeFilters > 0): ?> <span class="badge bg-primary"><?= $activeFilters ?></span><?php endif; ?>
                        </button>
                        <a href="<?= site_url('radiology') ?>" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>

                <div class="collapse<?= $showFilters ? ' show' : '' ?>" id="searchFilters">
                    <div class="row g-2 mt-1">
                        <div class="col-lg-6">
                            <div class="input-group">
                                <span class="input-group-text">Date</span>
                                <input type="date" name="date_from" class="form-control" aria-label="Date from" value="<?= esc($dateFrom ?? '') ?>">
                                <span class="input-group-text">to</span>
                                <input type="date" name="date_to" class="form-control" aria-label="Date to" value="<?= esc($dateTo ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="input-group">
                                <span class="input-group-text">Time</span>
                                <input type="time" name="time_from" class="form-control" aria-label="Time from" value="<?= esc($timeFrom ?? '') ?>">
                                <span class="input-group-text">to</span>
                                <input type="time" name="time_to" class="form-control" aria-label="Time to" value="<?= esc($timeTo ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($filterError)): ?>
                        <div class="alert alert-danger py-2 mt-2 mb-0">
                            <?= esc($filterError) ?>
                        </div>
                    <?php endif; ?>
                </div>

            </form>
        </div>
    </div>

    <!-- SEARCH RESULTS -->
    <div class="card search-card shadow-sm">

        <div class="card-header bg-white py-2">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Search Results</h6>
                <span class="badge bg-primary">
                    <?= isset($total) ? number_format($total) : '0' ?> Records
                </span>
            </div>
        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-sm table-bordered table-hover align-middle">

                    <thead>
                        <tr>
                            <th style="width: 60px;">No.</th>
                            <th>Patient ID</th>
                            <th>Patient Name</th>
                            <th>Accession Number</th>
                            <th>Physician</th>
                            <th>Report Date</th>
                            <th style="width: 120px;">Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (!empty($reports)): ?>

                        <?php $no = 1; ?>

                        <?php foreach ($reports as $report): ?>

                            <tr>
                                <td><?= $no++ ?></td>

                                <td>
                                    <?= esc($report['patient_id']) ?>
                                </td>

                                <td>
                                    <?= esc($report['patient_name']) ?>
                                </td>

                                <td>
                                    <?= esc($report['accession_number']) ?>
                                </td>

                                <td>
                                    <?= esc($report['physician'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= esc($report['report_date'] ?? '-') ?>
                                </td>

                                <td class="text-center">

                                    <a href="<?= site_url('radiology/view/' . $report['id']) ?>"
                                       target="_blank"
                                       class="btn btn-sm btn-outline-primary">
                                        View PDF
                                    </a>

                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No records found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <!-- PAGINATION -->
            <?php if (isset($pager) && $pager): ?>
                <div class="d-flex justify-content-end mt-3">
                    <?= $pager->links() ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- FOOTER -->
    <div class="text-center text-muted mt-4 small">
        Radiology Report Viewer
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>
</html>
