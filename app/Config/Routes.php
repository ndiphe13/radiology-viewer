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
});

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

