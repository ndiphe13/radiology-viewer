<?php

namespace App\Controllers;

use App\Libraries\DicomPreview;
use App\Libraries\ReportFiles;
use App\Models\RadiologyReportModel;
use App\Models\ReportShareModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Config\Share;

class ShareController extends BaseController
{
    private Share $config;

    public function __construct()
    {
        $this->config = config(Share::class);
    }

    // =================================================================
    // Staff side (logged in)
    // =================================================================

    /**
     * POST radiology/share/{reportId}: create a link and go to the slip.
     */
    public function create($reportId)
    {
        $this->requireShareRole();

        $hours = (int) $this->request->getPost('expiry_hours');

        if (!array_key_exists($hours, $this->config->expiryOptions)) {
            $hours = $this->config->defaultExpiryHours;
        }

        $report = (new RadiologyReportModel())
            ->where('id', (int) $reportId)
            ->where('enabled', 'Y')
            ->first();

        if (!$report) {
            throw PageNotFoundException::forPageNotFound('Report not found.');
        }

        $token = bin2hex(random_bytes(16));
        $pin   = $this->config->requirePin
            ? str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT)
            : null;

        $model   = new ReportShareModel();
        $shareId = $model->insert([
            'token_hash'          => ReportShareModel::hashToken($token),
            'report_id'           => (int) $report['id'],
            'created_by'          => session()->get('user_id'),
            'created_by_username' => (string) session()->get('username'),
            'pin_hash'            => $pin !== null ? password_hash($pin, PASSWORD_DEFAULT) : null,
            'expires_at'          => date('Y-m-d H:i:s', time() + $hours * 3600),
            'max_views'           => max(0, $this->config->maxViews),
            'created_at'          => date('Y-m-d H:i:s'),
        ]);

        $model->logAccess((int) $shareId, 'created');

        // Token and PIN are stored only as hashes, so they can be shown once.
        session()->setTempdata('share_slip_' . $shareId, ['token' => $token, 'pin' => $pin], 900);

        return redirect()->to(site_url('radiology/shares/slip/' . $shareId));
    }

    /**
     * GET radiology/shares/slip/{id}: printable slip with QR code.
     */
    public function slip($id)
    {
        $this->requireShareRole();

        $share  = $this->findOwnShare((int) $id);
        $secret = session()->getTempdata('share_slip_' . $share['id']);
        $report = (new RadiologyReportModel())->find($share['report_id']);

        return view('share/slip', [
            'share'  => $share,
            'report' => $report,
            'link'   => is_array($secret) ? $this->publicLink($secret['token']) : null,
            'pin'    => is_array($secret) ? $secret['pin'] : null,
            'org'    => $this->config->organizationName,
        ]);
    }

    /**
     * GET radiology/shares: list of links (admin: all, others: own).
     */
    public function index()
    {
        $this->requireShareRole();

        $model    = new ReportShareModel();
        $isAdmin  = session()->get('role') === 'admin';
        $shares   = $model->listWithReports($isAdmin ? null : (int) session()->get('user_id'));

        return view('share/index', [
            'title'   => 'Share Links',
            'shares'  => $shares,
            'pager'   => $model->pager,
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * POST radiology/shares/revoke/{id}
     */
    public function revoke($id)
    {
        $this->requireShareRole();

        $share = $this->findOwnShare((int) $id);
        $model = new ReportShareModel();

        if ($share['revoked_at'] === null) {
            $model->update($share['id'], ['revoked_at' => date('Y-m-d H:i:s')]);
            $model->logAccess((int) $share['id'], 'revoked');
        }

        return redirect()->back()->with('message', 'Link revoked.');
    }

    // =================================================================
    // Public side (link / QR code, no login)
    // =================================================================

    /**
     * GET s/{token}
     */
    public function open($token)
    {
        $share = $this->publicShare($token);

        if (is_string($share)) {
            return $this->blockedPage($share);
        }

        if (!$this->isVerified($share)) {
            return view('share/pin', [
                'token' => $token,
                'org'   => $this->config->organizationName,
                'error' => session()->getFlashdata('pin_error'),
            ]);
        }

        $model = new ReportShareModel();
        $key   = 'share_counted_' . $share['id'];

        if (!session()->get($key)) {
            $model->set('view_count', 'view_count + 1', false)
                ->set('last_accessed_at', date('Y-m-d H:i:s'))
                ->where('id', $share['id'])
                ->update();
            $model->logAccess((int) $share['id'], 'opened');
            session()->set($key, true);
        }

        $study = (new RadiologyReportModel())->findStudyForReport((int) $share['report_id']);
        $files = ($study && $this->config->allowImages)
            ? ((new DicomPreview())->filesForStudy($study) ?? [])
            : [];

        if (!$study) {
            // Report without images: still show the report details.
            $report = (new RadiologyReportModel())->find($share['report_id']);
            $study  = [
                'id'               => $report['id'],
                'patient_id'       => $report['patient_id'],
                'patient_name'     => $report['patient_name'],
                'accession_number' => $report['accession_number'],
                'report_date'      => $report['report_date'] ?? null,
                'study_id'         => '-',
            ];
        }

        return view('radiology/images', [
            'title'        => 'Hasil Radiologi / Radiology Result',
            'study'        => $study,
            'imageCount'   => count($files),
            'imageBaseUrl' => site_url('s/' . $token . '/images'),
            'pdfUrl'       => $this->config->allowPdf ? site_url('s/' . $token . '/pdf') : null,
            'pdfLabel'     => 'Lihat Laporan / View Report (PDF)',
            'topNotice'    => $this->config->organizationName
                . ' — link berlaku sampai / valid until '
                . date('d M Y H:i', strtotime($share['expires_at'])),
        ]);
    }

    /**
     * POST s/{token}: check the PIN.
     */
    public function verify($token)
    {
        $share = $this->publicShare($token);

        if (is_string($share)) {
            return $this->blockedPage($share);
        }

        $throttler = service('throttler');

        if ($throttler->check('sharepin_' . md5($this->request->getIPAddress()), 10, MINUTE) === false) {
            return redirect()->to(site_url('s/' . $token))
                ->with('pin_error', 'Terlalu banyak percobaan. Coba lagi nanti. / Too many attempts, try again later.');
        }

        $pin   = (string) $this->request->getPost('pin');
        $model = new ReportShareModel();

        if ($share['pin_hash'] !== null && password_verify($pin, $share['pin_hash'])) {
            session()->regenerate();
            session()->set('share_ok_' . $share['id'], time() + $this->config->verifiedMinutes * 60);
            $model->logAccess((int) $share['id'], 'pin_ok');

            return redirect()->to(site_url('s/' . $token));
        }

        $attempts = (int) $share['failed_attempts'] + 1;
        $update   = ['failed_attempts' => $attempts];

        if ($attempts >= $this->config->maxPinAttempts) {
            $update['revoked_at'] = date('Y-m-d H:i:s');
        }

        $model->update($share['id'], $update);
        $model->logAccess((int) $share['id'], isset($update['revoked_at']) ? 'locked' : 'pin_failed');

        if (isset($update['revoked_at'])) {
            return $this->blockedPage('locked');
        }

        return redirect()->to(site_url('s/' . $token))
            ->with('pin_error', 'PIN salah. / Wrong PIN.');
    }

    /**
     * GET s/{token}/pdf
     */
    public function pdf($token)
    {
        $share = $this->verifiedShareOr404($token);

        if (!$this->config->allowPdf) {
            throw PageNotFoundException::forPageNotFound();
        }

        $report = (new RadiologyReportModel())
            ->where('id', $share['report_id'])
            ->where('enabled', 'Y')
            ->first();

        $pdf = $report ? (new ReportFiles())->findPdf($report) : null;

        if ($pdf === null) {
            throw PageNotFoundException::forPageNotFound('Report not available.');
        }

        (new ReportShareModel())->logAccess((int) $share['id'], 'view_pdf');

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="radiology-report.pdf"')
            ->setHeader('Cache-Control', 'private, no-store')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody(file_get_contents($pdf));
    }

    /**
     * GET s/{token}/images/{index}
     */
    public function image($token, $index)
    {
        $share = $this->verifiedShareOr404($token);

        if (!$this->config->allowImages) {
            throw PageNotFoundException::forPageNotFound();
        }

        $study = (new RadiologyReportModel())->findStudyForReport((int) $share['report_id']);
        $files = $study ? (new DicomPreview())->filesForStudy($study) : null;

        if ($files === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return (new DicomPreview())->imageResponse($files, (int) $index, $this->response);
    }

    // =================================================================
    // Helpers
    // =================================================================

    private function requireShareRole(): void
    {
        if (!in_array(session()->get('role'), $this->config->allowedRoles, true)) {
            throw PageNotFoundException::forPageNotFound();
        }
    }

    private function findOwnShare(int $id): array
    {
        $share = (new ReportShareModel())->find($id);

        if (
            !$share
            || (session()->get('role') !== 'admin'
                && (int) $share['created_by'] !== (int) session()->get('user_id'))
        ) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $share;
    }

    private function publicLink(string $token): string
    {
        $base = $this->config->publicBaseUrl !== '' ? $this->config->publicBaseUrl : base_url();

        return rtrim($base, '/') . '/s/' . $token;
    }

    /**
     * @return array|string Share row, or the reason it is blocked
     */
    private function publicShare(string $token)
    {
        $share = (new ReportShareModel())->findByToken($token);

        if (!$share) {
            return 'invalid';
        }

        $reason = ReportShareModel::blockedReason($share);

        // A viewer who already opened the link may keep viewing after the last count.
        if ($reason === 'used' && session()->get('share_counted_' . $share['id'])) {
            $reason = null;
        }

        return $reason ?? $share;
    }

    private function isVerified(array $share): bool
    {
        if (!$this->config->requirePin || $share['pin_hash'] === null) {
            return true;
        }

        return (int) session()->get('share_ok_' . $share['id']) > time();
    }

    private function verifiedShareOr404(string $token): array
    {
        $share = $this->publicShare($token);

        if (is_string($share) || !$this->isVerified($share) || !session()->get('share_counted_' . $share['id'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $share;
    }

    private function blockedPage(string $reason)
    {
        $messages = [
            'invalid' => 'Link tidak valid. / This link is not valid.',
            'expired' => 'Link sudah kedaluwarsa. / This link has expired.',
            'revoked' => 'Link sudah dinonaktifkan. / This link has been disabled.',
            'locked'  => 'Link dikunci karena PIN salah terlalu banyak. / This link was locked after too many wrong PINs.',
            'used'    => 'Batas akses link sudah tercapai. / This link has reached its access limit.',
        ];

        return $this->response->setStatusCode($reason === 'invalid' ? 404 : 410)->setBody(view('share/blocked', [
            'message' => $messages[$reason] ?? $messages['invalid'],
            'org'     => $this->config->organizationName,
        ]));
    }
}
