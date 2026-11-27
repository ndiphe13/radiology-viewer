<?php

namespace App\Models;

use CodeIgniter\Model;

class ReportShareModel extends Model
{
    protected $table         = 'report_shares';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'token_hash', 'report_id', 'created_by', 'created_by_username', 'pin_hash',
        'expires_at', 'max_views', 'view_count', 'failed_attempts',
        'revoked_at', 'last_accessed_at', 'created_at',
    ];

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function findByToken(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            return null;
        }

        return $this->where('token_hash', self::hashToken($token))->first();
    }

    /**
     * Why a link can no longer be used, or null when it is valid.
     */
    public static function blockedReason(array $share): ?string
    {
        if ($share['revoked_at'] !== null) {
            return 'revoked';
        }

        if (strtotime($share['expires_at']) <= time()) {
            return 'expired';
        }

        if ((int) $share['max_views'] > 0 && (int) $share['view_count'] >= (int) $share['max_views']) {
            return 'used';
        }

        return null;
    }

    public function logAccess(int $shareId, string $event): void
    {
        $request = service('request');

        $this->db->table('report_share_access')->insert([
            'share_id'   => $shareId,
            'event'      => $event,
            'ip_address' => $request->getIPAddress(),
            'user_agent' => mb_substr((string) $request->getUserAgent(), 0, 255),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Shares with report details, newest first.
     */
    public function listWithReports(?int $createdBy, int $perPage = 25): array
    {
        $builder = $this->select(
            'report_shares.*, r.patient_id, r.patient_name, r.accession_number'
        )->join('radiology_reports r', 'r.id = report_shares.report_id', 'left')
            ->orderBy('report_shares.id', 'DESC');

        if ($createdBy !== null) {
            $builder->where('report_shares.created_by', $createdBy);
        }

        return $builder->paginate($perPage);
    }
}
