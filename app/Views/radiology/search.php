<?php
    $shareConfig = config(\Config\Share::class);
    $canShare    = in_array(session()->get('role'), $shareConfig->allowedRoles, true);
?>
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
            margin: 40px auto;
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

            <?php if ($canShare): ?>
                <a href="<?= site_url('radiology/shares') ?>"
                   class="btn btn-outline-light btn-sm">
                    Share Links
                </a>
            <?php endif; ?>

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

    <!-- PAGE TITLE -->
    <div class="mb-4">
        <h3 class="page-title">Radiology Report Viewer</h3>
        <p class="text-muted">
            Search and view radiology examination reports
        </p>
    </div>

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

    <!-- SEARCH FORM -->

<!-- SEARCH FORM -->
<div class="card search-card shadow-sm mb-4">
    <div class="card-body p-4">

        <form action="<?= site_url('radiology') ?>" method="get">

            <!-- Keyword Search -->
            <div class="row g-3 align-items-end mb-3">

                <div class="col-md-3">
                    <label for="type" class="form-label">
                        Search By
                    </label>

                    <select name="type" id="type" class="form-select">
                        <option value="patient_id"
                            <?= ($searchBy ?? '') === 'patient_id' ? 'selected' : '' ?>>
                            Patient ID
                        </option>

                        <option value="patient_name"
                            <?= ($searchBy ?? '') === 'patient_name' ? 'selected' : '' ?>>
                            Patient Name
                        </option>

                        <option value="accession_number"
                            <?= ($searchBy ?? '') === 'accession_number' ? 'selected' : '' ?>>
                            Accession Number
                        </option>
                    </select>
                </div>

                <div class="col-md-7">
                    <label for="q" class="form-label">
                        Search Keyword
                    </label>

                    <input type="text"
                           name="q"
                           id="q"
                           class="form-control"
                           placeholder="Patient ID, Patient Name or Accession Number"
                           value="<?= esc($keyword ?? '') ?>">
                </div>

                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary">
                        Search
                    </button>
                </div>

            </div>

            <hr>

            <!-- Date and Time Filters -->
            <h6 class="mb-3">Date & Time Filter</h6>

            <div class="row g-3">

                <div class="col-md-3">
                    <label for="date_from" class="form-label">
                        Date From
                    </label>

                    <input type="date"
                           name="date_from"
                           id="date_from"
                           class="form-control"
                           value="<?= esc($dateFrom ?? '') ?>">
                </div>

                <div class="col-md-3">
                    <label for="date_to" class="form-label">
                        Date To
                    </label>

                    <input type="date"
                           name="date_to"
                           id="date_to"
                           class="form-control"
                           value="<?= esc($dateTo ?? '') ?>">
                </div>

                <div class="col-md-3">
                    <label for="time_from" class="form-label">
                        Time From
                    </label>

                    <input type="time"
                           name="time_from"
                           id="time_from"
                           class="form-control"
                           value="<?= esc($timeFrom ?? '') ?>">
                </div>

                <div class="col-md-3">
                    <label for="time_to" class="form-label">
                        Time To
                    </label>

                    <input type="time"
                           name="time_to"
                           id="time_to"
                           class="form-control"
                           value="<?= esc($timeTo ?? '') ?>">
                </div>

            </div>

            <?php if (!empty($filterError)): ?>
                <div class="alert alert-danger mt-3 mb-0">
                    <?= esc($filterError) ?>
                </div>
            <?php endif; ?>

            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    Apply Filter
                </button>

                <a href="<?= site_url('radiology') ?>"
                   class="btn btn-outline-secondary">
                    Reset
                </a>
            </div>

        </form>

    </div>
</div>

    <!-- SEARCH RESULTS -->
    <div class="card search-card shadow-sm">

        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">

                <h5 class="mb-0">
                    Search Results
                </h5>

                <span class="badge bg-primary">
                    <?= isset($total) ? number_format($total) : '0' ?> Records
                </span>

            </div>
        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle">

                    <thead>
                        <tr>
                            <th style="width: 60px;">No.</th>
                            <th>Patient ID</th>
                            <th>Patient Name</th>
                            <th>Accession Number</th>
                            <th>Physician</th>
                            <th>Report Date</th>
                            <th style="width: 190px;">Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (!empty($reports)): ?>

                        <?php
                            $no = isset($pager) && $pager
                                ? 1 + ($pager->getCurrentPage() - 1) * $pager->getPerPage()
                                : 1;
                        ?>

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

                                    <?php if ($canShare): ?>
                                        <form method="post"
                                              action="<?= site_url('radiology/share/' . $report['id']) ?>"
                                              class="d-flex gap-1 mt-1"
                                              onsubmit="return confirm('Create a share link (QR code) for this patient?')">
                                            <?= csrf_field() ?>
                                            <select name="expiry_hours" class="form-select form-select-sm" title="Link validity">
                                                <?php foreach ($shareConfig->expiryOptions as $hours => $label): ?>
                                                    <option value="<?= (int) $hours ?>" <?= (int) $hours === $shareConfig->defaultExpiryHours ? 'selected' : '' ?>>
                                                        <?= esc($label) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-dark">Share</button>
                                        </form>
                                    <?php endif; ?>

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
