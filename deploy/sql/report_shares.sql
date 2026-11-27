-- Share links (QR code). Same as the migration; use one or the other.
CREATE TABLE IF NOT EXISTS report_shares (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    token_hash          CHAR(64)     NOT NULL,
    report_id           INT UNSIGNED NOT NULL,
    created_by          INT UNSIGNED NULL,
    created_by_username VARCHAR(100) NOT NULL,
    pin_hash            VARCHAR(255) NULL,
    expires_at          DATETIME     NOT NULL,
    max_views           INT UNSIGNED NOT NULL DEFAULT 0,
    view_count          INT UNSIGNED NOT NULL DEFAULT 0,
    failed_attempts     INT UNSIGNED NOT NULL DEFAULT 0,
    revoked_at          DATETIME     NULL,
    last_accessed_at    DATETIME     NULL,
    created_at          DATETIME     NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_token_hash (token_hash),
    KEY idx_report_id (report_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS report_share_access (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    share_id   INT UNSIGNED NOT NULL,
    event      VARCHAR(30)  NOT NULL,
    ip_address VARCHAR(45)  NOT NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME     NOT NULL,
    PRIMARY KEY (id),
    KEY idx_share_id (share_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

