<?php

namespace App\Controllers;

class UserManagementController extends BaseController
{
    private function writeAudit(string $event, int $targetId, ?string $targetLabel = null): void
    {
        db_connect()->table('audit_logs')->insert([
            'user_id'    => session()->get('user_id'),
            'username'   => session()->get('username'),
            'event_type' => $event,
            'target_type'=> 'USER',
            'target_id'  => substr($targetId . ($targetLabel !== null ? " ({$targetLabel})" : ''), 0, 100),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => substr(
                $this->request->getUserAgent()->getAgentString(),
                0,
                255
            ),
        ]);
    }

    public function index()
    {
        $users = db_connect()->table('users')
            ->select('id, username, full_name, role, is_active, last_login_at, created_at')
            ->orderBy('username', 'ASC')
            ->get()
            ->getResultArray();

        return view('users/index', [
            'users' => $users,
        ]);
    }

    public function createForm()
    {
        return view('users/form', [
            'mode' => 'create',
            'user' => null,
        ]);
    }

    public function create()
    {
        $rules = [
            'username'  => 'required|alpha_numeric|min_length[3]|max_length[100]|is_unique[users.username]',
            'full_name' => 'required|max_length[150]',
            'password'  => 'required|min_length[12]',
            'role'      => 'required|in_list[admin,radiology,auditor]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $db = db_connect();

        $db->table('users')->insert([
            'username'      => trim($this->request->getPost('username')),
            'full_name'     => trim($this->request->getPost('full_name')),
            'password_hash' => password_hash(
                $this->request->getPost('password'),
                PASSWORD_DEFAULT
            ),
            'role'          => $this->request->getPost('role'),
            'is_active'     => 1,
        ]);

        $newId = (int) $db->insertID();

        $this->writeAudit('USER_CREATE', $newId);

        return redirect()->to(site_url('users'))
            ->with('success', 'User berhasil dibuat.');
    }

    public function edit($id)
    {
        $user = db_connect()->table('users')
            ->select('id, username, full_name, role, is_active')
            ->where('id', (int) $id)
            ->get()
            ->getRowArray();

        if (!$user) {
            return redirect()->to(site_url('users'))
                ->with('error', 'User tidak ditemukan.');
        }

        return view('users/form', [
            'mode' => 'edit',
            'user' => $user,
        ]);
    }

    public function update($id)
    {
        $id = (int) $id;
        $db = db_connect();

        $user = $db->table('users')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (!$user) {
            return redirect()->to(site_url('users'))
                ->with('error', 'User tidak ditemukan.');
        }

        $role = $this->request->getPost('role');
        $isActive = (string) $this->request->getPost('is_active');

        if ($id === (int) session()->get('user_id')
            && ($role !== 'admin' || $isActive !== '1')) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Anda tidak dapat menurunkan role atau menonaktifkan akun sendiri.');
        }

        if (!$this->validate([
            'full_name' => 'required|max_length[150]',
            'role'      => 'required|in_list[admin,radiology,auditor]',
            'is_active' => 'required|in_list[0,1]',
        ])) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Prevent removing the last active administrator.
        if (
            $user['role'] === 'admin'
            && (int) $user['is_active'] === 1
            && ($role !== 'admin' || $isActive !== '1')
        ) {
            $activeAdmins = $db->table('users')
                ->where('role', 'admin')
                ->where('is_active', 1)
                ->countAllResults();

            if ($activeAdmins <= 1) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Tidak dapat menonaktifkan atau mengubah role Admin terakhir.');
            }
        }

        $db->table('users')
            ->where('id', $id)
            ->update([
                'full_name' => trim($this->request->getPost('full_name')),
                'role'      => $role,
                'is_active' => (int) $isActive,
            ]);

        $this->writeAudit('USER_UPDATE', $id);

        return redirect()->to(site_url('users'))
            ->with('success', 'Data user berhasil diperbarui.');
    }

    public function resetPassword($id)
    {
        $id = (int) $id;
        $db = db_connect();

        $user = $db->table('users')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (!$user) {
            return redirect()->to(site_url('users'))
                ->with('error', 'User tidak ditemukan.');
        }

        if (!$this->validate([
            'password' => 'required|min_length[12]',
        ])) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $db->table('users')
            ->where('id', $id)
            ->update([
                'password_hash' => password_hash(
                    $this->request->getPost('password'),
                    PASSWORD_DEFAULT
                ),
            ]);

        $this->writeAudit('PASSWORD_RESET', $id);

        return redirect()->to(site_url('users'))
            ->with('success', 'Password user berhasil direset.');
    }

    /**
     * Permanently delete a user. Only inactive users can be deleted;
     * an active user must be deactivated (Edit > Status) first.
     */
    public function delete($id)
    {
        $id = (int) $id;
        $db = db_connect();

        $user = $db->table('users')
            ->select('id, username, is_active')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (!$user) {
            return redirect()->to(site_url('users'))
                ->with('error', 'User tidak ditemukan.');
        }

        if ($id === (int) session()->get('user_id')) {
            return redirect()->to(site_url('users'))
                ->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        if ((int) $user['is_active'] === 1) {
            return redirect()->to(site_url('users'))
                ->with('error', 'User "' . $user['username'] . '" masih aktif. Nonaktifkan user terlebih dahulu sebelum menghapus.');
        }

        // Conditional delete guards against the user being re-activated meanwhile.
        $db->table('users')
            ->where('id', $id)
            ->where('is_active', 0)
            ->delete();

        if ($db->affectedRows() !== 1) {
            return redirect()->to(site_url('users'))
                ->with('error', 'User tidak dapat dihapus.');
        }

        $this->writeAudit('USER_DELETE', $id, $user['username']);

        return redirect()->to(site_url('users'))
            ->with('success', 'User "' . $user['username'] . '" berhasil dihapus.');
    }
}
