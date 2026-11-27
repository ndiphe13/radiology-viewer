<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?> - <?= esc($study['accession_number']) ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>
        body { background: #111; color: #ddd; }
        .info-bar { background: #1c1c1c; border-bottom: 1px solid #333; }
        .viewer {
            height: calc(100vh - 150px);
            display: flex; align-items: center; justify-content: center;
            position: relative;
        }
        .viewer img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .viewer .status { position: absolute; color: #888; }
        .controls { background: #1c1c1c; border-top: 1px solid #333; }
        .form-range { max-width: 600px; }
    </style>
</head>
<body>
<?php if (!empty($topNotice)): ?>
    <div class="px-4 py-2 small" style="background:#0b3d2e;color:#cfe;"><?= esc($topNotice) ?></div>
<?php endif; ?>

<div class="info-bar px-4 py-2 d-flex flex-wrap gap-4 small">
    <div><span class="text-secondary">Patient ID</span><br><?= esc($study['patient_id']) ?></div>
    <div><span class="text-secondary">Patient Name</span><br><?= esc($study['patient_name']) ?></div>
    <div><span class="text-secondary">Accession</span><br><?= esc($study['accession_number']) ?></div>
    <div><span class="text-secondary">Report Date</span><br><?= esc($study['report_date'] ?? '-') ?></div>
    <div><span class="text-secondary">Study ID</span><br><?= esc($study['study_id']) ?></div>
    <?php if (!empty($pdfUrl)): ?>
        <div class="ms-auto align-self-center">
            <a href="<?= esc($pdfUrl, 'attr') ?>" target="_blank" class="btn btn-sm btn-primary">
                <?= esc($pdfLabel ?? 'View Report (PDF)') ?>
            </a>
        </div>
    <?php endif; ?>
</div>

<?php if ($imageCount === 0): ?>

    <div class="viewer"><span class="status">No DICOM images found for this study.</span></div>

<?php else: ?>

    <div class="viewer">
        <span class="status" id="status">Loading…</span>
        <img id="image" alt="DICOM image">
    </div>

    <div class="controls px-4 py-3 d-flex align-items-center justify-content-center gap-3">
        <button type="button" class="btn btn-outline-light btn-sm" id="prev">&larr; Prev</button>
        <input type="range" class="form-range" id="slider"
               min="1" max="<?= (int) $imageCount ?>" value="1">
        <button type="button" class="btn btn-outline-light btn-sm" id="next">Next &rarr;</button>
        <span id="counter" class="small text-nowrap">1 / <?= (int) $imageCount ?></span>
    </div>

    <script>
    (function () {
        const total   = <?= (int) $imageCount ?>;
        const baseUrl = <?= json_encode($imageBaseUrl) ?>;
        const img     = document.getElementById('image');
        const status  = document.getElementById('status');
        const slider  = document.getElementById('slider');
        const counter = document.getElementById('counter');
        let current = 0;
        let timer   = null;

        function show(index) {
            current = Math.max(0, Math.min(total - 1, index));
            slider.value = current + 1;
            counter.textContent = (current + 1) + ' / ' + total;

            // Wait briefly while the slider is dragged, then load one image.
            clearTimeout(timer);
            timer = setTimeout(function () {
                status.textContent = 'Loading…';
                status.style.display = '';
                img.src = baseUrl + '/' + current;
            }, 120);
        }

        img.addEventListener('load', function () { status.style.display = 'none'; });
        img.addEventListener('error', function () {
            img.removeAttribute('src');
            status.textContent = 'This image could not be displayed.';
            status.style.display = '';
        });

        document.getElementById('prev').addEventListener('click', function () { show(current - 1); });
        document.getElementById('next').addEventListener('click', function () { show(current + 1); });
        slider.addEventListener('input', function () { show(parseInt(slider.value, 10) - 1); });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowLeft' || e.key === 'ArrowUp')   { show(current - 1); e.preventDefault(); }
            if (e.key === 'ArrowRight' || e.key === 'ArrowDown') { show(current + 1); e.preventDefault(); }
        });

        document.querySelector('.viewer').addEventListener('wheel', function (e) {
            e.preventDefault();
            show(current + (e.deltaY > 0 ? 1 : -1));
        }, { passive: false });

        show(0);
    })();
    </script>

<?php endif; ?>

</body>
</html>
