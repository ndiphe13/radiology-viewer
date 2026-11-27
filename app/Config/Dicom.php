<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * DICOM image preview settings.
 *
 * Values can be overridden in .env, e.g.:
 *   dicom.basePath = /mnt/radiology-images
 */
class Dicom extends BaseConfig
{
    // ------------------------------------------------------------------
    // Database mapping  (CONFIRM AGAINST THE DB SCHEMA)
    // ------------------------------------------------------------------

    /**
     * Column in radiology_reports that holds the Study ID
     * (normally the DICOM Study Instance UID, e.g. 1.2.840.113619...).
     */
    public string $studyIdColumn = 'study_instance_uid';

    /**
     * Column holding the Windows folder of the study images.
     * Leave empty ('') if images are found only by Study ID under $basePath.
     */
    public string $folderColumn = 'image_folder_location';

    /**
     * true  = images are in <folder>/<StudyID>/
     * false = images are directly in <folder>/
     */
    public bool $appendStudyIdToFolder = false;

    // ------------------------------------------------------------------
    // File locations
    // ------------------------------------------------------------------

    /** Linux mount point of the SMB share that holds the DICOM files. */
    public string $basePath = '/mnt/radiology-images';

    /** Windows path prefix stored in the database, mapped to $basePath. */
    public string $windowsPrefix = 'V:\\Meddiff\\InstaPACS\\images\\';

    // ------------------------------------------------------------------
    // Conversion
    // ------------------------------------------------------------------

    /** DCMTK converter (Debian/Ubuntu: apt install dcmtk). */
    public string $converter = '/usr/bin/dcmj2pnm';

    /** JPEG quality 1-100. */
    public int $jpegQuality = 90;

    /** Converted JPEGs are cached here (contains patient images: keep private). */
    public string $cacheDir = WRITEPATH . 'cache/dicom';

    /** Upper limit of images listed for one study. */
    public int $maxImages = 2000;

    /** How long the list of files in a study is cached, in seconds. */
    public int $fileListTtl = 600;
}
