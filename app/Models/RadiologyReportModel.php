<?php

namespace App\Models;

use CodeIgniter\Model;

class RadiologyReportModel extends Model
{
    protected $table            = 'radiology_reports';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'source_report_id',
        'patient_id',
        'patient_name',
        'accession_number',
        'physician',
        'report_folder_location',
        'report_date',
        'report_time',
        'enabled',
    ];

    protected $useTimestamps = false;

    /**
     * Get an enabled report with its Study ID and image folder.
     * Column names come from Config\Dicom (set them to match the DB schema).
     *
     * @return array{id:int, patient_id:string, patient_name:string, accession_number:string,
     *               report_date:?string, study_id:string, image_folder:?string}|null
     */
    public function findStudyForReport(int $id): ?array
    {
        $config = config(\Config\Dicom::class);
        $db     = $this->db;

        $select = [
            'id', 'patient_id', 'patient_name', 'accession_number', 'report_date',
            $db->protectIdentifiers($config->studyIdColumn) . ' AS study_id',
        ];

        if ($config->folderColumn !== '') {
            $select[] = $db->protectIdentifiers($config->folderColumn) . ' AS image_folder';
        }

        $row = $this->builder()
            ->select(implode(', ', $select), false)
            ->where('id', $id)
            ->where('enabled', 'Y')
            ->get()
            ->getRowArray();

        if (!$row || trim((string) $row['study_id']) === '') {
            return null;
        }

        $row['study_id']     = trim((string) $row['study_id']);
        $row['image_folder'] = $row['image_folder'] ?? null;

        return $row;
    }
}
