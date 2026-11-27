<?php

namespace App\Controllers;

use App\Libraries\DicomPreview;
use App\Libraries\ReportFiles;
use App\Models\RadiologyReportModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class RadiologyController extends BaseController
{
    /**
     * Search radiology reports
     */
    public function search()
    {
        // Search parameters
        $keyword = trim((string) $this->request->getGet('q'));
        $searchBy = (string) $this->request->getGet('type');

        // Date and time filters
        $dateFrom = trim((string) $this->request->getGet('date_from'));
        $dateTo   = trim((string) $this->request->getGet('date_to'));
        $timeFrom = trim((string) $this->request->getGet('time_from'));
        $timeTo   = trim((string) $this->request->getGet('time_to'));

        // Allowed search fields
        $allowedTypes = [
            'all',
            'patient_id',
            'patient_name',
            'accession_number',
        ];

        if (!in_array($searchBy, $allowedTypes, true)) {
            $searchBy = 'all';
        }

        // Validate date format
        $isValidDate = static function ($value) {
            if ($value === '') {
                return true;
            }

            $date = \DateTime::createFromFormat('!Y-m-d', $value);

            return $date !== false
                && $date->format('Y-m-d') === $value;
        };

        // Validate time format HH:MM
        $isValidTime = static function ($value) {
            return $value === ''
                || preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value);
        };

        $filterError = null;

        // Validate date filters
        if (!$isValidDate($dateFrom) || !$isValidDate($dateTo)) {
            $filterError = 'Invalid date format.';
            $dateFrom = '';
            $dateTo = '';
        }

        // Validate date range
        if (
            $dateFrom !== ''
            && $dateTo !== ''
            && $dateFrom > $dateTo
        ) {
            $filterError = 'Date From cannot be later than Date To.';
            $dateFrom = '';
            $dateTo = '';
        }

        // Validate time filters
        if (!$isValidTime($timeFrom) || !$isValidTime($timeTo)) {
            $filterError = 'Invalid time format.';
            $timeFrom = '';
            $timeTo = '';
        }

        // Initialize model
        $reports = new RadiologyReportModel();

        $builder = $reports
            ->select([
                'id',
                'patient_id',
                'patient_name',
                'accession_number',
                'physician',
                'report_date',
                'report_time',
            ])
            ->where('enabled', 'Y');

        // Apply keyword search
        if ($keyword !== '') {
            if ($searchBy === 'all') {
                // Match the keyword in any of the searchable fields
                $builder->groupStart()
                    ->like('patient_id', $keyword)
                    ->orLike('patient_name', $keyword)
                    ->orLike('accession_number', $keyword)
                    ->groupEnd();
            } else {
                $builder->like($searchBy, $keyword);
            }
        }

        // Apply date range
        if ($dateFrom !== '') {
            $builder->where('report_date >=', $dateFrom);
        }

        if ($dateTo !== '') {
            $builder->where('report_date <=', $dateTo);
        }

        // Apply time range
        if ($timeFrom !== '' && $timeTo !== '') {

            if ($timeFrom <= $timeTo) {

                // Normal time range
                // Example: 08:00 - 17:00

                $builder->where(
                    'report_time >=',
                    $timeFrom . ':00'
                );

                $builder->where(
                    'report_time <=',
                    $timeTo . ':59'
                );

            } else {

                // Overnight time range
                // Example: 22:00 - 06:00

                $builder->groupStart()
                    ->where(
                        'report_time >=',
                        $timeFrom . ':00'
                    )
                    ->orWhere(
                        'report_time <=',
                        $timeTo . ':59'
                    )
                    ->groupEnd();
            }

        } elseif ($timeFrom !== '') {

            // Only start time
            $builder->where(
                'report_time >=',
                $timeFrom . ':00'
            );

        } elseif ($timeTo !== '') {

            // Only end time
            $builder->where(
                'report_time <=',
                $timeTo . ':59'
            );
        }

        // Execute query with pagination
        $result = $builder
            ->orderBy('report_date', 'DESC')
            ->orderBy('report_time', 'DESC')
            ->paginate(20);

        // View data
        $data = [
            'title'       => 'Radiology Report Viewer',
            'keyword'     => $keyword,
            'searchBy'    => $searchBy,
            'dateFrom'    => $dateFrom,
            'dateTo'      => $dateTo,
            'timeFrom'    => $timeFrom,
            'timeTo'      => $timeTo,
            'filterError' => $filterError,
            'reports'     => $result,
            'pager'       => $reports->pager,
            'total'       => $reports->pager->getTotal(),
        ];

        return view('radiology/search', $data);
    }


    /**
     * Display radiology PDF report
     */
    public function viewPdf($id)
    {
        $report = (new RadiologyReportModel())
            ->where('id', $id)
            ->where('enabled', 'Y')
            ->first();

        $pdf = $report ? (new ReportFiles())->findPdf($report) : null;

        if ($pdf === null) {
            throw PageNotFoundException::forPageNotFound('PDF file not found.');
        }

        $this->auditReportAccess('REPORT_VIEW', (int) $report['id']);

        // Never cache patient data in the browser or proxies
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="radiology-report.pdf"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, private')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0')
            ->setBody(file_get_contents($pdf));
    }

    /**
     * Record who opened which report.
     */
    private function auditReportAccess(string $event, int $reportId): void
    {
        db_connect()->table('audit_logs')->insert([
            'user_id'     => session()->get('user_id'),
            'username'    => session()->get('username'),
            'event_type'  => $event,
            'target_type' => 'RADIOLOGY_REPORT',
            'target_id'   => (string) $reportId,
            'ip_address'  => $this->request->getIPAddress(),
            'user_agent'  => substr(
                $this->request->getUserAgent()->getAgentString(),
                0,
                255
            ),
        ]);
    }

    /**
     * DICOM image preview page for a report's study.
     */
    public function images($id)
    {
        [$study, $files] = $this->loadStudyImages((int) $id);

        $this->auditReportAccess('IMAGES_VIEW', (int) $id);

        return view('radiology/images', [
            'title'      => 'Image Preview',
            'study'        => $study,
            'imageCount'   => count($files),
            'imageBaseUrl' => site_url('radiology/images/' . (int) $id),
        ]);
    }

    /**
     * One DICOM image of the study, converted to JPEG.
     */
    public function image($id, $index)
    {
        [, $files] = $this->loadStudyImages((int) $id);

        return (new DicomPreview())->imageResponse($files, (int) $index, $this->response);
    }

    /**
     * @return array{0: array, 1: list<string>}
     */
    private function loadStudyImages(int $id): array
    {
        $study = (new RadiologyReportModel())->findStudyForReport($id);

        if (!$study) {
            throw PageNotFoundException::forPageNotFound('Study not found.');
        }

        $files = (new DicomPreview())->filesForStudy($study);

        if ($files === null) {
            throw PageNotFoundException::forPageNotFound('Study images not found.');
        }

        return [$study, $files];
    }
}
