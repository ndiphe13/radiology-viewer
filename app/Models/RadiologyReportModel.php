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
}
