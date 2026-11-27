<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        // Check login status
        if (!$session->get('isLoggedIn')) {
            return redirect()->to(site_url('login'))
                ->with('error', 'Silakan login terlebih dahulu.');
        }

        // Automatic logout after 30 minutes of inactivity
        $idleTimeout = 1800; // 30 minutes
        $now = time();

        $lastActivity = (int) $session->get('last_activity');

        if (
            $lastActivity > 0 &&
            ($now - $lastActivity) >= $idleTimeout
        ) {
            $session->destroy();

            return redirect()->to(site_url('login'))
                ->with(
                    'error',
                    'Sesi Anda telah berakhir karena tidak ada aktivitas selama 30 menit. Silakan login kembali.'
                );
        }

        // Update last activity timestamp
        $session->set('last_activity', $now);

        // Get user ID from session
        $userId = $session->get('user_id');

        // Validate user against database
        $user = db_connect()->table('users')
            ->select('id, username, full_name, role, is_active')
            ->where('id', $userId)
            ->get()
            ->getRowArray();

        // User no longer exists or has been deactivated
        if (!$user || (int) $user['is_active'] !== 1) {
            $session->destroy();

            return redirect()->to(site_url('login'))
                ->with(
                    'error',
                    'Akun tidak aktif atau tidak ditemukan. Silakan hubungi administrator.'
                );
        }

        // Refresh session data from database
        $session->set([
            'username'  => $user['username'],
            'full_name' => $user['full_name'],
            'role'      => $user['role'],
        ]);

        return null;
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {
        // No action required
    }
}
