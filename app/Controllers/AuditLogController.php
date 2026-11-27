<?php

namespace App\Controllers;

class AuditLogController extends BaseController
{
    public function index()
    {
        $db = db_connect();

        $logs = $db->table('audit_logs')
            ->orderBy('created_at', 'DESC')
            ->limit(100)
            ->get()
            ->getResultArray();

        return view('audit/index', [
            'logs' => $logs,
        ]);
    }
}
