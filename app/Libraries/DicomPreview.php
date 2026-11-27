<?php

namespace App\Libraries;

use Config\Dicom;
use RuntimeException;

/**
 * Finds the DICOM files of a study on the SMB share and converts
 * single images to JPEG for browser preview (using DCMTK dcmj2pnm).
 *
 * File paths never leave the server: the browser only sees
 * a report ID and an image index.
 */
class DicomPreview
{
    private Dicom $config;

    public function __construct(?Dicom $config = null)
    {
        $this->config = $config ?? config(Dicom::class);
    }

    /**
     * Validate a Study ID. Study Instance UIDs contain digits and dots (max 64).
     * Plain alphanumeric study IDs are accepted as well.
     */
    public function isValidStudyId(string $studyId): bool
    {
        return preg_match('/^[A-Za-z0-9._-]{1,64}$/', $studyId) === 1
            && !str_contains($studyId, '..');
    }

    /**
     * Resolve the study folder on the Linux mount.
     *
     * @param string      $studyId       Study ID from the database
     * @param string|null $windowsFolder Windows folder from the database (optional)
     *
     * @return string|null Real path inside the mount, or null if not found
     */
    public function resolveStudyFolder(string $studyId, ?string $windowsFolder): ?string
    {
        if (!$this->isValidStudyId($studyId)) {
            return null;
        }

        $base = $this->config->basePath;

        if ($this->config->folderColumn !== '') {
            $windowsFolder = (string) $windowsFolder;
            $prefix        = $this->config->windowsPrefix;

            if (strncasecmp($windowsFolder, $prefix, strlen($prefix)) !== 0) {
                return null;
            }

            $relative = str_replace('\\', '/', substr($windowsFolder, strlen($prefix)));
            $folder   = rtrim($base . '/' . trim($relative, '/'), '/');

            if ($this->config->appendStudyIdToFolder) {
                $folder .= '/' . $studyId;
            }
        } else {
            $folder = $base . '/' . $studyId;
        }

        $realBase   = realpath($base);
        $realFolder = realpath($folder);

        if (
            $realBase === false
            || $realFolder === false
            || !is_dir($realFolder)
            || !str_starts_with($realFolder . DIRECTORY_SEPARATOR, $realBase . DIRECTORY_SEPARATOR)
        ) {
            return null;
        }

        return $realFolder;
    }

    /**
     * DICOM files of a study row from RadiologyReportModel::findStudyForReport().
     *
     * @return list<string>|null null when the study folder is not found
     */
    public function filesForStudy(array $study): ?array
    {
        $folder = $this->resolveStudyFolder($study['study_id'], $study['image_folder'] ?? null);

        return $folder === null ? null : $this->listImages($folder);
    }

    /**
     * Send one image of a file list as a JPEG response (or 404/415).
     */
    public function imageResponse(array $files, int $index, \CodeIgniter\HTTP\ResponseInterface $response)
    {
        if (!isset($files[$index])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Image not found.');
        }

        try {
            $jpeg = $this->toJpeg($files[$index]);
        } catch (RuntimeException $e) {
            return $response->setStatusCode(415)
                ->setContentType('text/plain')
                ->setBody('This image could not be displayed.');
        }

        return $response
            ->setHeader('Content-Type', 'image/jpeg')
            ->setHeader('Cache-Control', 'private, no-store')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody(file_get_contents($jpeg));
    }

    /**
     * List DICOM files in the folder (recursive, sorted by path).
     * Files are recognised by the "DICM" marker, so files without
     * an extension (common on PACS storage) are included.
     *
     * @return list<string>
     */
    public function listImages(string $folder): array
    {
        $cacheKey = 'dicomlist_' . sha1($folder);
        $cached   = cache($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $files    = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folder, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getSize() < 132) {
                continue;
            }

            if ($this->hasDicomMarker($file->getPathname())) {
                $files[] = $file->getPathname();

                if (count($files) >= $this->config->maxImages) {
                    break;
                }
            }
        }

        natsort($files);
        $files = array_values($files);

        cache()->save($cacheKey, $files, $this->config->fileListTtl);

        return $files;
    }

    /**
     * Convert one DICOM file to JPEG and return the JPEG path (cached).
     *
     * @throws RuntimeException when conversion fails
     */
    public function toJpeg(string $dicomPath): string
    {
        $stat = @stat($dicomPath);

        if ($stat === false) {
            throw new RuntimeException('DICOM file not readable.');
        }

        $cacheDir = $this->config->cacheDir;

        if (!is_dir($cacheDir) && !mkdir($cacheDir, 0750, true) && !is_dir($cacheDir)) {
            throw new RuntimeException('Cannot create DICOM cache folder.');
        }

        $key  = sha1($dicomPath . '|' . $stat['size'] . '|' . $stat['mtime']);
        $jpeg = $cacheDir . '/' . $key . '.jpg';

        if (is_file($jpeg) && filesize($jpeg) > 0) {
            return $jpeg;
        }

        $tmp = $jpeg . '.' . bin2hex(random_bytes(4)) . '.tmp';

        // Array command = no shell, no injection.
        $command = [
            $this->config->converter,
            '--quiet',
            '+Wn',                                  // automatic window (ignores extreme values)
            '+oj',                                  // JPEG output
            '+Jq', (string) $this->config->jpegQuality,
            $dicomPath,
            $tmp,
        ];

        $process = proc_open(
            $command,
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );

        if (!is_resource($process)) {
            throw new RuntimeException('Cannot start DICOM converter.');
        }

        stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0 || !is_file($tmp) || filesize($tmp) === 0) {
            @unlink($tmp);
            log_message('error', 'DICOM conversion failed for {file}: {error}', [
                'file'  => basename($dicomPath),
                'error' => trim((string) $error),
            ]);

            throw new RuntimeException('DICOM conversion failed.');
        }

        rename($tmp, $jpeg);

        return $jpeg;
    }

    private function hasDicomMarker(string $path): bool
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        fseek($handle, 128);
        $marker = fread($handle, 4);
        fclose($handle);

        return $marker === 'DICM';
    }
}
