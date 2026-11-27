<?php

namespace App\Controllers;

class AuthController extends BaseController
{
    /**
     * Display login page.
     */
    public function login()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to(site_url('radiology'));
        }

        return view('auth/login');
    }

    /**
     * Process login.
     */
    public function attemptLogin()
    {
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');

        if ($username === '' || $password === '') {
            return redirect()->to(site_url('login'))
                ->with('error', 'Username dan password wajib diisi.');
        }

        $db = db_connect();

        $user = $db->table('users')
            ->where('username', $username)
            ->where('is_active', 1)
            ->get()
            ->getRowArray();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return redirect()->to(site_url('login'))
                ->with('error', 'Username atau password tidak valid.');
        }

        // Regenerate session ID after successful authentication.
        session()->regenerate(true);

        session()->set([
            'isLoggedIn' => true,
            'user_id'    => $user['id'],
            'username'   => $user['username'],
            'full_name'  => $user['full_name'],
            'role'       => $user['role'],
        ]);

        // Update last login timestamp.
        $db->table('users')
            ->where('id', $user['id'])
            ->update([
                'last_login_at' => date('Y-m-d H:i:s'),
            ]);

        // Record successful login.
        $db->table('audit_logs')->insert([
            'user_id'    => $user['id'],
            'username'   => $user['username'],
            'event_type' => 'LOGIN',
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => substr(
                $this->request->getUserAgent()->getAgentString(),
                0,
                255
            ),
        ]);

        return redirect()->to(site_url('radiology'));
    }

    /**
     * Logout current user.
     */
    public function logout()
    {
        $userId  = session()->get('user_id');
        $username = session()->get('username');

        if ($userId) {
            db_connect()->table('audit_logs')->insert([
                'user_id'    => $userId,
                'username'   => $username,
                'event_type' => 'LOGOUT',
                'ip_address' => $this->request->getIPAddress(),
                'user_agent' => substr(
                    $this->request->getUserAgent()->getAgentString(),
                    0,
                    255
                ),
            ]);
        }

        session()->destroy();

        return redirect()->to(site_url('login'));
    }
}
