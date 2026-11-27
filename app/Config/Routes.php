<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Authentication
$routes->get('login', 'AuthController::login');
$routes->post('login', 'AuthController::attemptLogin');
$routes->post('logout', 'AuthController::logout');

// Home - Redirect to Radiology Viewer
$routes->get('/', static function () {
    if (session()->get('isLoggedIn')) {
        return redirect()->to(site_url('radiology'));
    }
    return redirect()->to(site_url('login'));
});

// Radiology Report Viewer
$routes->group('radiology', ['filter' => 'auth'], static function ($routes) {
    $routes->get('', 'RadiologyController::search');
    $routes->get('view/(:num)', 'RadiologyController::viewPdf/$1');
    $routes->get('images/(:num)', 'RadiologyController::images/$1');
    $routes->get('images/(:num)/(:num)', 'RadiologyController::image/$1/$2');

    // Share links (roles set in Config\Share::$allowedRoles)
    $routes->post('share/(:num)', 'ShareController::create/$1');
    $routes->get('shares', 'ShareController::index');
    $routes->get('shares/slip/(:num)', 'ShareController::slip/$1');
    $routes->post('shares/revoke/(:num)', 'ShareController::revoke/$1');
});

// Public share link / QR code (no login; protected by token, PIN and expiry)
$routes->get('s/([a-f0-9]{32})', 'ShareController::open/$1');
$routes->post('s/([a-f0-9]{32})', 'ShareController::verify/$1');
$routes->get('s/([a-f0-9]{32})/pdf', 'ShareController::pdf/$1');
$routes->get('s/([a-f0-9]{32})/images/(:num)', 'ShareController::image/$1/$2');

// Audit Log - Admin and Auditor only
$routes->get(
    'audit',
    'AuditLogController::index',
    ['filter' => ['auth', 'role:admin,auditor']]
);

// User Management - Admin only
$routes->group('users', ['filter' => ['auth', 'role:admin']], static function ($routes) {
    $routes->get('', 'UserManagementController::index');
    $routes->get('new', 'UserManagementController::createForm');
    $routes->post('create', 'UserManagementController::create');
    $routes->get('edit/(:num)', 'UserManagementController::edit/$1');
    $routes->post('update/(:num)', 'UserManagementController::update/$1');
    $routes->post('reset-password/(:num)', 'UserManagementController::resetPassword/$1');
});

