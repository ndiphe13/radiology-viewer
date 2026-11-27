<?php

namespace App\Libraries;

/**
 * Locates the PDF report of a radiology_reports row on the SMB mount.
 * (Logic moved unchanged from RadiologyController::viewPdf.)
 */
class ReportFiles
{
    private string $basePath      = '/mnt/radiology-reports';
    private string $windowsPrefix = "V:\\Meddiff\\InstaPACS\\reports\\";

    /**
     * @return string|null Readable PDF path, or null when not found
     */
    public function findPdf(array $report): ?string
    {
        $windowsPath = (string) ($report['report_folder_location'] ?? '');

        if (strncasecmp($windowsPath, $this->windowsPrefix, strlen($this->windowsPrefix)) !== 0) {
            return null;
        }

        $relativePath = str_replace('\\', '/', substr($windowsPath, strlen($this->windowsPrefix)));
        $accession    = (string) ($report['accession_number'] ?? '');

        if (!preg_match('/^[A-Za-z0-9_-]+$/', $accession)) {
            return null;
        }

        $realBase      = realpath($this->basePath);
        $realDirectory = realpath($this->basePath . '/' . $relativePath . '/' . $accession);

        if (
            $realBase === false
            || $realDirectory === false
            || strpos($realDirectory, $realBase . DIRECTORY_SEPARATOR) !== 0
        ) {
            return null;
        }

        $files = glob($realDirectory . '/*.pdf');

        if (!$files || !is_file($files[0]) || !is_readable($files[0])) {
            return null;
        }

        return $files[0];
    }
}
