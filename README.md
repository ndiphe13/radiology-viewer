# radiology-viewer

Web viewer for radiology reports (PDF) exported by InstaPACS. Built on CodeIgniter 4.

Features:

- Login with roles: `admin`, `radiology`, `auditor`
- Search reports by patient ID, patient name or accession number, with date/time filters
- Inline PDF viewing from the PACS report share
- Audit log (login, logout, report views, user changes)
- User management (admin only)

## Requirements

- PHP 8.2+ with `intl`, `mbstring`, `mysqli`
- MySQL / MariaDB with the existing `users`, `audit_logs` and `radiology_reports` tables
- Composer
- Linux host with the InstaPACS report share (`V:\Meddiff\InstaPACS\reports\`) mounted read-only at `/mnt/radiology-reports`

## Deployment

1. Clone the repository and install dependencies:

   ```sh
   composer install --no-dev --optimize-autoloader
   ```

2. Copy `env` to `.env` and set at least:

   ```ini
   CI_ENVIRONMENT = production
   app.baseURL = 'https://your-server/'
   app.indexPage = ''
   app.appTimezone = 'Asia/Jakarta'
   app.forceGlobalSecureRequests = true
   cookie.secure = true

   database.default.hostname = localhost
   database.default.database = ...
   database.default.username = ...
   database.default.password = ...
   ```

   If the app runs behind a reverse proxy, also set `app.proxyIPs` so audit logs record the real client IP.

3. Point the web server document root at `public/` (never the project root).

4. Make `writable/` writable by the web server user (e.g. `www-data`).

5. Mount the report share, e.g. in `/etc/fstab`:

   ```
   //pacs-server/reports  /mnt/radiology-reports  cifs  ro,credentials=/etc/smb-radiology.cred,uid=www-data,gid=www-data,iocharset=utf8  0  0
   ```

## Notes

- `report_folder_location` must start with `V:\Meddiff\InstaPACS\reports\`; other paths are rejected.
- Login attempts are limited to 10 per IP per 5 minutes.
- Users are logged out after 30 minutes of inactivity.
