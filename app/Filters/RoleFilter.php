<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $userRole = session()->get('role');

        if (empty($arguments) || !in_array($userRole, $arguments, true)) {
            return redirect()->to(site_url('radiology'))
                ->with('error', 'Anda tidak memiliki hak akses untuk halaman tersebut.');
        }

        return null;
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {
        // No action required.
    }
}
