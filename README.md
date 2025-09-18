
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

- **Delete or restrict the installer for security:**
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
# grievance_system