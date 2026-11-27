<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Secure share links (QR code) for reports and images.
 */
class Share extends BaseConfig
{
    /** Roles allowed to create and manage share links. */
    public array $allowedRoles = ['admin', 'user'];

    /**
     * Validity choices shown when creating a link: hours => label.
     * Edit freely, e.g. add 2 => '2 hours' or 2160 => '90 days'.
     */
    public array $expiryOptions = [
        24  => '1 day',
        72  => '3 days',
        168 => '7 days',
        720 => '30 days',
    ];

    /** Pre-selected validity (must be a key of $expiryOptions). */
    public int $defaultExpiryHours = 168;

    /** Require a 6-digit PIN (printed on the slip, not in the QR code). */
    public bool $requirePin = true;

    /** Wrong PIN attempts before the link is locked. */
    public int $maxPinAttempts = 5;

    /** Maximum openings of a link; 0 = unlimited until it expires. */
    public int $maxViews = 0;

    /** Minutes a viewer stays verified after entering the PIN. */
    public int $verifiedMinutes = 30;

    /** What the recipient may see. */
    public bool $allowPdf    = true;
    public bool $allowImages = false; // image viewer disabled for now

    /**
     * Address used in the link / QR code. Must be reachable by the recipient
     * (e.g. a public HTTPS address if patients open it at home).
     * Empty = use app.baseURL.
     */
    public string $publicBaseUrl = '';

    /** Hospital name printed on the slip and the public page. */
    public string $organizationName = 'Radiology Department';
}
