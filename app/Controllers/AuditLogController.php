<?php

namespace App\Controllers;

class AuditLogController extends BaseController
{
    private const PER_PAGE = 50;

    public function index()
    {
        $db = db_connect();

        // Filters
        $user     = trim((string) $this->request->getGet('user'));
        $event    = trim((string) $this->request->getGet('event'));
        $patient  = trim((string) $this->request->getGet('patient'));
        $dateFrom = trim((string) $this->request->getGet('date_from'));
        $dateTo   = trim((string) $this->request->getGet('date_to'));
        $page     = max(1, (int) $this->request->getGet('page'));

        $isDate = static fn(string $v): bool => $v !== ''
            && ($d = \DateTime::createFromFormat('!Y-m-d', $v)) !== false
            && $d->format('Y-m-d') === $v;

        // Report events link to radiology_reports through target_id.
        $builder = $db->table('audit_logs a')
            ->join(
                'radiology_reports r',
                "a.target_type = 'RADIOLOGY_REPORT' AND r.id = CAST(a.target_id AS UNSIGNED)",
                'left',
                false // fixed SQL, no user input; keep CAST/literal unescaped
            );

        if ($user !== '') {
            $builder->where('a.username', $user);
        }
        if ($event !== '') {
            $builder->where('a.event_type', $event);
        }
        if ($patient !== '') {
            $builder->groupStart()
                ->like('r.patient_name', $patient)
                ->orLike('r.patient_id', $patient)
                ->orLike('r.accession_number', $patient)
                ->groupEnd();
        }
        if ($isDate($dateFrom)) {
            $builder->where('a.created_at >=', $dateFrom . ' 00:00:00');
        }
        if ($isDate($dateTo)) {
            $builder->where('a.created_at <=', $dateTo . ' 23:59:59');
        }

        $total = $builder->countAllResults(false);

        $logs = $builder
            ->select('a.id, a.created_at, a.username, a.event_type, a.target_type, a.target_id,
                      a.ip_address, a.user_agent,
                      r.patient_id, r.patient_name, r.accession_number, r.report_date')
            ->orderBy('a.created_at', 'DESC')
            ->orderBy('a.id', 'DESC')
            ->limit(self::PER_PAGE, ($page - 1) * self::PER_PAGE)
            ->get()
            ->getResultArray();

        // Dropdown options
        $users  = array_column($db->query('SELECT DISTINCT username FROM audit_logs WHERE username IS NOT NULL ORDER BY username')->getResultArray(), 'username');
        $events = array_column($db->query('SELECT DISTINCT event_type FROM audit_logs ORDER BY event_type')->getResultArray(), 'event_type');

        return view('audit/index', [
            'logs'     => $logs,
            'total'    => $total,
            'pager'    => service('pager')->makeLinks($page, self::PER_PAGE, $total, 'default_full'),
            'users'    => $users,
            'events'   => $events,
            'filters'  => [
                'user'      => $user,
                'event'     => $event,
                'patient'   => $patient,
                'date_from' => $isDate($dateFrom) ? $dateFrom : '',
                'date_to'   => $isDate($dateTo) ? $dateTo : '',
            ],
        ]);
    }
}
