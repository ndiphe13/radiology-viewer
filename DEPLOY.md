# Deploying Radiology Viewer (Nginx + PHP-FPM)

Tested with Ubuntu 24.04, PHP 8.3, CodeIgniter 4.7, DCMTK 3.6.7.
Replace `radiology.example.local`, passwords and share names with your own values.

## 1. Install packages

```bash
sudo apt update
sudo apt install -y nginx mariadb-server cifs-utils dcmtk \
    php8.3-fpm php8.3-cli php8.3-intl php8.3-mbstring php8.3-mysql php8.3-curl php8.3-xml
```

`dcmtk` provides `dcmj2pnm`, which converts DICOM images to JPEG for the preview.

## 2. Copy the project

```bash
sudo mkdir -p /var/www
sudo tar -xzf radiology-viewer.tar.gz -C /var/www
cd /var/www/radiology-viewer

# Optional, removes PHPUnit and other test packages from vendor/
sudo composer install --no-dev --optimize-autoloader

sudo chown -R root:www-data /var/www/radiology-viewer
sudo find . -type d -exec chmod 750 {} \;
sudo find . -type f -exec chmod 640 {} \;
sudo chown -R www-data:www-data writable
sudo chmod -R 770 writable
```

## 3. Database

Create the database and an app user, then import your schema:

```sql
CREATE DATABASE radiology_viewer CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'radiology_app'@'localhost' IDENTIFIED BY 'CHANGE_ME';
GRANT SELECT, INSERT, UPDATE ON radiology_viewer.* TO 'radiology_app'@'localhost';
FLUSH PRIVILEGES;
```

```bash
mysql -u root -p radiology_viewer < schema.sql

# Tables for share links (QR code)
mysql -u root -p radiology_viewer < deploy/sql/report_shares.sql
# or: php spark migrate
```

## 4. Configuration

```bash
sudo cp deploy/env.production.example .env
sudo nano .env            # baseURL, database password
sudo chown root:www-data .env && sudo chmod 640 .env
```

Then open `app/Config/Dicom.php` and confirm:

- `studyIdColumn` is the column in `radiology_reports` holding the Study ID.
- `folderColumn` is the column holding the image folder (or `''` if images are found by Study ID only).
- `appendStudyIdToFolder` says whether images sit in a sub-folder named after the Study ID.
- `windowsPrefix` is the Windows path prefix stored in the database.

Share links (QR code) are set in `app/Config/Share.php`:

- `expiryOptions` / `defaultExpiryHours`: the validity choices staff can pick from.
- `requirePin`, `maxPinAttempts`: PIN printed on the slip; the link locks after too many wrong PINs.
- `maxViews`: limit how many times a link can be opened (0 = unlimited until expiry).
- `allowPdf`, `allowImages`: what the recipient can see.
- `allowedRoles`: who can create links.
- `publicBaseUrl`: the address in the QR code. **Patients can only open the link if this address is reachable from where they are.** If the site is intranet-only, links work only inside the hospital network. For use from home, publish the server (or only the `/s/` path through a reverse proxy) on a public HTTPS address.
- `organizationName`: printed on the slip and the patient page.

## 5. Mount the SMB shares (read-only)

```bash
sudo mkdir -p /mnt/radiology-reports /mnt/radiology-images
sudo nano /etc/samba/radiology.cred
#   username=pacs_reader
#   password=CHANGE_ME
#   domain=HOSPITAL
sudo chmod 600 /etc/samba/radiology.cred
```

Add to `/etc/fstab` (adjust server and share names):

```
//PACS-SERVER/Meddiff/InstaPACS/reports  /mnt/radiology-reports  cifs  ro,credentials=/etc/samba/radiology.cred,uid=www-data,gid=www-data,file_mode=0440,dir_mode=0550,vers=3.0,_netdev,nofail  0 0
//PACS-SERVER/Meddiff/InstaPACS/images   /mnt/radiology-images   cifs  ro,credentials=/etc/samba/radiology.cred,uid=www-data,gid=www-data,file_mode=0440,dir_mode=0550,vers=3.0,_netdev,nofail  0 0
```

```bash
sudo mount -a
sudo -u www-data ls /mnt/radiology-images | head
```

## 6. Nginx

```bash
sudo cp deploy/nginx-radiology-viewer.conf /etc/nginx/sites-available/radiology-viewer
sudo ln -s /etc/nginx/sites-available/radiology-viewer /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
```

Check the PHP-FPM socket name in the config matches your PHP version (`ls /run/php/`).

## 7. Clear old preview images daily

Converted JPEGs are cached in `writable/cache/dicom`. They contain patient images, so remove them regularly:

```bash
echo '30 2 * * * www-data find /var/www/radiology-viewer/writable/cache/dicom -type f -mtime +1 -delete' \
  | sudo tee /etc/cron.d/radiology-viewer-cache
```

## 8. Test

1. Open `https://radiology.example.local/` and log in.
2. Search a patient: the record count badge should show the real total.
3. Click **View PDF**, then **View Images**.
4. Click **Share**, print the slip, scan the QR code with a phone and enter the PIN.
5. Open **Share Links** and revoke the test link; the phone should now show "link disabled".
6. If images do not appear, check `writable/logs/` and run the converter by hand:

```bash
sudo -u www-data dcmj2pnm +Wn +oj /mnt/radiology-images/<path>/<file> /tmp/test.jpg
```

## Notes

- Images are shown as JPEG previews (first frame of each file), not for diagnosis.
- Images are sorted by file name.
- DCMTK cannot decode JPEG 2000 compressed DICOM. If your PACS stores images that way, those images show "could not be displayed"; installing GDCM can be added for that case.
