<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Slip - <?= esc($report['accession_number'] ?? '') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .slip { max-width: 420px; margin: 30px auto; background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 24px; }
        #qr svg { width: 220px; height: 220px; }
        .pin { font-size: 2rem; letter-spacing: .4rem; font-weight: 700; }
        .link { word-break: break-all; font-size: .8rem; }
        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .slip { border: 1px dashed #999; margin: 0 auto; }
        }
    </style>
</head>
<body>

<div class="no-print text-center mt-3">
    <a href="<?= site_url('radiology') ?>" class="btn btn-sm btn-outline-secondary">&larr; Back to search</a>
    <a href="<?= site_url('radiology/shares') ?>" class="btn btn-sm btn-outline-secondary">All share links</a>
    <?php if ($link): ?>
        <button type="button" class="btn btn-sm btn-primary" onclick="window.print()">Print slip</button>
        <button type="button" class="btn btn-sm btn-outline-primary" id="copy">Copy link</button>
    <?php endif; ?>
</div>

<div class="slip text-center">
    <h5 class="mb-0"><?= esc($org) ?></h5>
    <div class="text-muted small mb-3">Hasil Radiologi / Radiology Result</div>

    <table class="table table-sm small text-start mb-3">
        <tr><th>Patient ID</th><td><?= esc($report['patient_id'] ?? '') ?></td></tr>
        <tr><th>Nama / Name</th><td><?= esc($report['patient_name'] ?? '') ?></td></tr>
        <tr><th>Accession</th><td><?= esc($report['accession_number'] ?? '') ?></td></tr>
        <tr><th>Berlaku sampai / Valid until</th><td><?= esc(date('d M Y H:i', strtotime($share['expires_at']))) ?></td></tr>
    </table>

    <?php if ($link): ?>
        <div id="qr" class="mb-2"></div>
        <div class="small text-muted mb-2">Pindai kode QR / Scan the QR code</div>
        <div class="link text-muted mb-3"><?= esc($link) ?></div>

        <?php if ($pin): ?>
            <div class="small">PIN</div>
            <div class="pin mb-2"><?= esc($pin) ?></div>
        <?php endif; ?>

        <div class="small text-muted">
            Jaga kerahasiaan slip ini. Jangan bagikan PIN kepada orang lain.<br>
            Keep this slip private. Do not share the PIN.
        </div>

        <script src="<?= base_url('assets/js/qrcode.min.js') ?>"></script>
        <script>
            (function () {
                const link = <?= json_encode($link) ?>;
                const qr = qrcode(0, 'M');
                qr.addData(link);
                qr.make();
                document.getElementById('qr').innerHTML = qr.createSvgTag({ cellSize: 5, margin: 2 });
                const copy = document.getElementById('copy');
                copy.addEventListener('click', function () {
                    navigator.clipboard.writeText(link).then(function () { copy.textContent = 'Copied'; });
                });
            })();
        </script>
    <?php else: ?>
        <div class="alert alert-warning small text-start">
            For security, the link and PIN are shown only for 15 minutes after the link is created.
            Revoke this link and create a new one if the slip was lost.
        </div>
    <?php endif; ?>
</div>

</body>
</html>
