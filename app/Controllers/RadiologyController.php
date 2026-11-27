<?php

namespace App\Controllers;

use App\Models\RadiologyReportModel;

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
        ];

        return view('radiology/search', $data);
    }


    /**
     * Display radiology PDF report
     */
    public function viewPdf($id)
    {
        $reportModel = new RadiologyReportModel();

        // Find report using database ID
        $report = $reportModel
            ->where('id', $id)
            ->where('enabled', 'Y')
            ->first();

        if (!$report) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'Radiology report not found.'
            );
        }

        // SMB mount root
        $basePath = '/mnt/radiology-reports';

        // Windows path from database
        $windowsPath = $report['report_folder_location'];

        // Expected Windows path prefix
        $windowsPrefix = "V:\\Meddiff\\InstaPACS\\reports\\";

        // Validate Windows path
        if (
            strncasecmp(
                $windowsPath,
                $windowsPrefix,
                strlen($windowsPrefix)
            ) !== 0
        ) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'Invalid report folder path.'
            );
        }

        // Extract relative folder path
        $relativePath = substr(
            $windowsPath,
            strlen($windowsPrefix)
        );

        // Convert Windows separators to Linux separators
        $relativePath = str_replace('\\', '/', $relativePath);

        // Validate accession number
        $accession = (string) $report['accession_number'];

        if (!preg_match('/^[A-Za-z0-9_-]+$/', $accession)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'Invalid accession number.'
            );
        }

        // Build PDF directory
        $filePath = $basePath . '/'
            . $relativePath . '/'
            . $accession;

        // Resolve real paths
        $realBase = realpath($basePath);
        $realDirectory = realpath($filePath);

        // Validate directory is inside SMB mount root
        if (
            $realBase === false ||
            $realDirectory === false ||
            strpos(
                $realDirectory,
                $realBase . DIRECTORY_SEPARATOR
            ) !== 0
        ) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'PDF folder not found.'
            );
        }

        // Find PDF files
        $files = glob($realDirectory . '/*.pdf');

        if (
            !$files ||
            !is_file($files[0]) ||
            !is_readable($files[0])
        ) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'PDF file not found.'
            );
        }

        // Read PDF
        $pdfContent = file_get_contents($files[0]);

        if ($pdfContent === false) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'Unable to read PDF file.'
            );
        }

        // Record who opened which report
        db_connect()->table('audit_logs')->insert([
            'user_id'     => session()->get('user_id'),
            'username'    => session()->get('username'),
            'event_type'  => 'REPORT_VIEW',
            'target_type' => 'RADIOLOGY_REPORT',
            'target_id'   => (string) $report['id'],
            'ip_address'  => $this->request->getIPAddress(),
            'user_agent'  => substr(
                $this->request->getUserAgent()->getAgentString(),
                0,
                255
            ),
        ]);

        // Display PDF in browser; never cache patient data
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader(
                'Content-Disposition',
                'inline; filename="radiology-report.pdf"'
            )
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, private')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0')
            ->setBody($pdfContent);
    }
}
