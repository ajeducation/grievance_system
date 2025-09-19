## SSO Configuration
## SSO Integration (Microsoft Entra)

1. **Register your application in Microsoft Entra (Azure Portal):**
  - Go to [Azure Portal > App registrations](https://portal.azure.com/#blade/Microsoft_AAD_RegisteredApps/ApplicationsListBlade)
  - Click **New registration**
  - Set a name (e.g., Grievance System)
  - Set **Redirect URI** to:
    - `https://yourdomain.com/sso/callback.php` (production)
    - or `http://localhost/grievance_system/public/sso/callback.php` (local)
  - After registration, note the **Application (client) ID** and **Directory (tenant) ID**
  - Go to **Certificates & secrets** and create a new client secret
  - Go to **API permissions** and add:
    - `openid`, `profile`, `email` (for SSO)
    - `Files.ReadWrite.All` (for OneDrive integration)
  - Grant admin consent for your tenant

2. **Configure your app:**
  - Update `src/sso/microsoft.php` and `config/storage.php` with your client ID, secret, and redirect URI

3. **Test SSO login:**
  - Visit the login page and use Microsoft login

---

## Google Drive Integration

1. **Enable Google Drive API:**
  - Go to [Google Cloud Console](https://console.developers.google.com/)
  - Create a new project (or select existing)
  - Enable the **Google Drive API**
  - Go to **Credentials** and create an OAuth client ID (Web application)
  - Set the redirect URI (can use `urn:ietf:wg:oauth:2.0:oob` for server-side)
  - Download the credentials JSON
  - Get a refresh token (see Google API docs)

2. **Configure your app:**
  - Update `config/storage.php` with your Google Drive client ID, secret, refresh token, and folder ID

3. **Install Composer dependencies:**
  - Run:
    ```bash
    composer require google/apiclient
    ```

---

## OneDrive Integration (Microsoft Graph)

1. **Register your app in Azure (see SSO steps above):**
  - Add `Files.ReadWrite.All` permission for Microsoft Graph
  - Get client ID, secret, and generate a refresh token for the target account

2. **Configure your app:**
  - Update `config/storage.php` with your OneDrive client ID, secret, refresh token, and folder ID

3. **Install Composer dependencies:**
  - Run:
    ```bash
    composer require microsoft/microsoft-graph
    ```

---

## Composer Setup

This project uses Composer for cloud storage integrations:

1. **Install Composer (if not already):**
  - [Download Composer](https://getcomposer.org/download/)
  - Or on Ubuntu:
    ```bash
    sudo apt update && sudo apt install composer
    ```

2. **Install required packages:**
  - For Google Drive:
    ```bash
    composer require google/apiclient
    ```
  - For OneDrive:
    ```bash
    composer require microsoft/microsoft-graph
    ```

3. **Autoload:**
  - The code will automatically use Composer's autoloader for these packages.
1. **Set file permissions:**
  - Ensure the web server user can write to `config/` and `install/` during installation.
  - Example:
  ```bash
  chmod 755 config install
  chmod 644 config/database.php install/schema.sql
2. **Set your web server’s document root to the `public/` directory.**
3. **Visit the installer:**
  - Go to `http://yourdomain.com/install.php` (or `http://localhost/grievance_system/public/install.php`)
  - Follow the on-screen steps:
  1. Enter database details
  2. Create tables and config
  3. Set up the first super administrator
4. **Register your application in Microsoft Entra** and update `src/sso/microsoft.php` with your credentials.
  - Set **Redirect URI** to your callback URL, e.g.:
  ```
  https://yourdomain.com/sso/callback.php
  ```
  (For local testing: `http://localhost/grievance_system/public/sso/callback.php`)

# Student Grievance System

---

## Screenshots

<p align="center">
  <img src="screenshots/dashboard.png" alt="Dashboard" width="700">
</p>
<p align="center">
  <img src="screenshots/admin-panel.png" alt="Admin Panel" width="700">
</p>
<p align="center">
  <img src="screenshots/grievance-form.png" alt="Grievance Form" width="700">
</p>

A modern, role-based grievance management system for educational institutions, featuring Microsoft SSO, analytics, and a beautiful UI.

---

## Features

- Microsoft SSO (OAuth2 via Entra)
- Role-based access: student, admin, head, staff, and custom roles
- Admin can assign any role to any user
- Grievance submission, tracking, and status updates
- Category and staff management
- Analytics: category-wise and person-wise completed grievances with graphs
- Modern, responsive UI (Bootstrap + custom styles)

---

## Requirements

- PHP 7.4+
- MySQL 5.5+
- Microsoft Entra registration for SSO

---

## How to Download

**Clone via Git:**
```bash
git clone https://github.com/ajeducation/grievance_system.git
cd grievance_system
```

**Or download as ZIP:**
- Go to the GitHub page for the project
- Click "Code" > "Download ZIP"
- Extract the ZIP and open the folder

---

## Installation (Step-by-Step)

1. **Set file permissions:**
   - Ensure the web server user can write to `config/` and `install/` during installation.
   - Example:
     ```bash
     chmod 755 config install
     chmod 644 config/database.php install/schema.sql
     ```

2. **Set your web server’s document root to the `public/` directory.**

3. **Visit the installer:**
   - Go to `http://yourdomain.com/install.php` (or `http://localhost/grievance_system/public/install.php`)
   - Follow the on-screen steps:
     1. Enter database details
     2. Create tables and config
     3. Set up the first super administrator

4. **Register your application in Microsoft Entra** and update `src/sso/microsoft.php` with your credentials.

---


## After Installation

- **Delete the installer for security:**
  ```bash
  rm public/install.php
  rm -rf install
  ```
- **Set config file to read-only:**
  ```bash
  chmod 444 config/database.php
  ```
- **(Optional) Set up HTTPS** for secure logins.
- **Log in as super admin and assign roles to users as needed.**

---

## Database Schema

See `install/schema.sql` for the full schema. Tables include:
- users
- categories
- grievances
- grievance_actions

---

## Security Notes

- Always delete the installer after setup.
- Use strong passwords for admin accounts.
- Keep your PHP and MySQL versions up to date.

---

## License

MIT License. See LICENSE file.
- Admin configures categories and assignments
- Grievance submission, tracking, and status updates
- Analytics and filtering (to be implemented)

---

This is a starter structure. Further implementation is required for SSO callback, full CRUD, analytics, and UI polish.

---

## Appeal Window & Per-Grievance Controls

### Custom Appeal Window (Category Level)
- **Admins** can set a custom appeal window (in days) for each grievance category in the Admin Panel > Categories tab.
- This determines how many days after a grievance is marked completed that a student can file an appeal.
- The default is 7 days, but can be set per category.

### Per-Grievance Appeal Enable/Disable & Window
- **Managers/Admins** can enable or disable appeals for individual grievances after creation.
- They can also set a custom appeal window (in days) for a specific grievance, overriding the category default.
- This is available in the grievance management table (Actions column) for each grievance.

### Enforcement
- When a student attempts to file an appeal, the system checks:
  - If appeals are enabled for the grievance (per-grievance or category default)
  - If the appeal is within the allowed window (per-grievance or category default)
- If the window has expired, the student will see an error message and cannot submit an appeal.

### Tooltips & Help
- Tooltips are provided in the UI to explain the appeal window and per-grievance controls to admins, managers, and students.

---

## Analytics & Reporting

### Appeals Report & Analytics
- Total number of appeals
- Appeals by status (pending, under review, accepted, rejected)
- Average resolution time for appeals
- List of all appeals with student, grievance, status, and timestamps

### Grievance Analytics
- Category-wise completed grievances (counts and percentages)
- Person-wise (staff) completed grievances (counts and percentages)
- Graphs for both category and staff performance (using Chart.js)
- Filtering by category, status, and assigned staff

### Export & Bulk Actions
- Export selected grievances to CSV
- Download all attachments for selected grievances as ZIP
- Download NAAC-compliant grievance report (Excel/CSV) for accreditation purposes

### Timeline & Audit
- Grievance timeline (all actions taken, with timestamps and users)
- Audit logs for appeals and grievance actions

These analytics help admins and managers monitor grievance trends, staff performance, appeal outcomes, and workflow efficiency. The NAAC report export ensures compliance with accreditation requirements.

---

## Customizable User Reports (Rich Text, Word/PDF Export)

- Superadmins can design a custom report template in the admin panel using a rich text editor (with images, formatting, and placeholders).
- Placeholders like <code>{{total_grievances}}</code>, <code>{{category_name}}</code>, <code>{{date_range}}</code> are replaced with computed or raw values when generating a report.
- Managers/admins can preview the report or download it as Word (.docx) or PDF from the Analytics tab.
- Images and branding can be included in the template.
- The template is stored in the config table and can be updated anytime.

### How to Use
1. Go to Admin Panel &rarr; Report Template (superadmin only) to design your template.
2. Use placeholders for dynamic data (see above).
3. In the Analytics tab, use the Preview, Download Word, or Download PDF buttons to generate the report with live data.

---
# grievance_system