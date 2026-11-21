# Repository: [https://github.com/tim249/library-system.git](https://github.com/tim249/library-system.git)

# General Library Management System

A web-based library management system project for organizing library records and member services. This README describes the project using the information available in the referenced project conversation. The application source files and database export were not present in this workspace when this README was prepared, so setup details that depend on those files remain placeholders.

## Overview

The project is intended to support common library workflows through two roles: administrators and library members. The available project notes describe a PHP and MySQL application with a browser interface.

## Features

### Administrator

- Manage book, author and category records.
- Handle loan workflows, including issuing and returning items as supported by the application.

### Member

- Browse the book catalogue.
- Maintain a personal reading list.
- View personal loan history.

The exact screens, permissions and workflow rules should be confirmed against the application source before relying on this feature list.

## Technologies

Project notes identify the following technologies:

- PHP for server-side processing
- MySQL for data storage
- HTML, CSS and JavaScript for the browser interface
- PDO is mentioned in project documentation as the intended PHP database interface; verify this against the source code
- XAMPP for local development

## Requirements

- XAMPP with Apache and MySQL (or a compatible PHP/MySQL environment)
- A modern web browser
- The project source package
- The database SQL script or export, if one is supplied with the source package

The required PHP version and any PHP extensions have not been verified. Check the source package and XAMPP configuration for compatibility.

## Installation with XAMPP

1. Install XAMPP.
2. Copy the application folder into XAMPP's `htdocs` directory (commonly `C:\\xampp\\htdocs\\`).
3. Start Apache and MySQL from the XAMPP Control Panel.
4. Open phpMyAdmin at `http://localhost/phpmyadmin/`.
5. Create the database using the name and schema expected by the application. **[Database name: confirm from the project configuration.]**
6. Import the SQL setup file or database export supplied with the project. **[SQL filename: confirm from the source package.]**
7. Update the application's database connection configuration with the local host, database name, username and password. **[Configuration file and setting names: confirm from the source.]**
8. Visit `http://localhost/<project-folder>/` in a browser, replacing `<project-folder>` with the directory name placed in `htdocs`.
9. Use the account creation flow or administrator credentials supplied with the project. No default credentials are documented here; do not guess or publish credentials.

## Database setup and configuration

The project notes describe a MySQL database but do not verify the database name, schema, table names, setup script or connection file against the implementation. Obtain these from the project source or database export before importing or configuring the application. Do not assume a proposed schema from design notes matches the implemented database.

Keep local credentials out of version control. If the application supports environment-based configuration, use it for secrets; otherwise, protect the configuration file and replace any sample credentials before deployment.

## Running locally

1. Start Apache and MySQL in XAMPP.
2. Confirm the database has been created and populated using the supplied setup materials.
3. Confirm that the application's database settings match the local MySQL configuration.
4. Open `http://localhost/<project-folder>/`.
5. Sign in using a valid project account, then check access appropriate to that account's role.

If the page does not load, check the Apache and PHP error logs, database connection settings, and whether the expected database has been imported.

## Project structure

The application source tree could not be inspected: the available workspace contained only `work/` and `outputs/`, with no PHP, SQL, or application files. Consequently, this README does not list or infer application paths. Update this section from the actual source package, for example by documenting its real entry point, configuration location, SQL setup file and feature directories.

## Testing notes

No test suite or test results were available to inspect. After installation, manually check the following with test accounts and data:

- Login, logout and role-specific access
- Administrator book, author and category management
- Loan issue and return workflows
- Member catalogue browsing and reading-list changes
- Member loan-history access
- Invalid input and database connection failures

Record observed results separately; this README makes no claim that these checks have passed.

## Security notes

- Use prepared statements for database queries and validate input on the server.
- Store passwords with a suitable password-hashing function and verify them safely.
- Enforce role checks on the server for every protected action, not only in the interface.
- Escape output to reduce cross-site scripting risk and use CSRF protection for state-changing forms.
- Use non-default credentials, restrict database privileges, and avoid committing secrets.
- For public deployment, configure HTTPS, disable development error display, and review session and file permissions.

These are security practices to verify or apply; they are not claims about the current implementation.

## Future enhancements

Potential future work, subject to project requirements, includes barcode-assisted circulation, reservations, overdue notifications, reporting, fine management, audit trails, and deployment hardening. These are suggestions, not implemented features.

## Project details

- **Project:** General Library Management System
- **Institution:** Cavendish University Uganda
- **Programme:** Bachelor of Software Engineering
- **Student:** [Student Name]
- **Registration number:** [Registration Number]
- **Supervisor:** [Supervisor Name]
![Entity relationship diagram](database/erd.png)
![Login page](screenshots/login.png)
![create page](screenshots/create.png)
![admin page](screenshots/admindashboard.png)
![member page](screenshots/memberdashboard.png)
