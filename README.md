# Drupal 10 Event Registration Platform

A production-ready, enterprise-quality Drupal 10 event registration platform with clean architecture, reproducible Docker builds, and professional DevOps structure.

## Table of Contents

- [Features](#features)
- [Technology Stack](#technology-stack)
- [Quick Start](#quick-start)
- [Environment Configuration](#environment-configuration)
- [URLs Reference](#urls-reference)
- [Module Architecture](#module-architecture)
- [Database Schema](#database-schema)
- [Validation Logic](#validation-logic)
- [Email System](#email-system)
- [Development](#development)
- [Troubleshooting](#troubleshooting)

## Features

### Event Management (Admin)
- Create, edit, and delete events
- Set registration windows (start/end dates)
- Categorize events
- View event status (Open, Closed, Completed)

### Public Registration
- AJAX-driven cascading dropdowns:
  - Category → Event Dates
  - Category + Date → Event Names
- Date-restricted availability (only shows events with open registration)
- Duplicate prevention (per email + event combination)
- Input validation with user-friendly error messages

### Admin Dashboard
- Filter registrations by date and event
- Live participant count
- CSV export functionality
- Responsive table display

### Email Notifications
- User confirmation emails
- Admin notification emails
- Configurable templates with token support

## Technology Stack

| Component | Technology |
|-----------|------------|
| CMS | Drupal 10.x |
| PHP | 8.2+ (FPM) |
| Web Server | Nginx 1.24 |
| Database | PostgreSQL 15 |
| Package Manager | Composer 2.x |
| CLI | Drush 12.x |
| Containerization | Docker + Docker Compose |

## Quick Start

### Prerequisites

- Docker Engine 20.10+
- Docker Compose 2.0+
- Git

### Installation

1. **Clone the repository:**
   ```bash
   cd /home/mohan/Documents/Projects/Drupal
   ```

2. **Configure environment:**
   ```bash
   cp env/.env.example env/.env.local
   ```

3. **Edit environment variables:**
   ```bash
   nano env/.env.local
   ```

   Update the following values:
   - `DB_PASSWORD` - Use a secure password
   - `DB_ROOT_PASSWORD` - Use a secure password
   - `ADMIN_EMAIL` - Your admin email address

4. **Build and start containers:**
   ```bash
   docker-compose up --build
   ```

5. **Wait for installation:**
   The entrypoint script will automatically:
   - Install Composer dependencies
   - Install Drupal
   - Enable the event_registration module
   - Clear caches

6. **Access the site:**
   - Frontend: http://localhost:8080
   - Admin login: `admin` / `admin` (change immediately!)

### Stopping the Environment

```bash
docker-compose down
```

To remove volumes (reset database):
```bash
docker-compose down -v
```

## Environment Configuration

### Required Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `DB_HOST` | Database hostname | `postgres` |
| `DB_NAME` | Database name | `drupal` |
| `DB_USER` | Database user | `drupal` |
| `DB_PASSWORD` | Database password | `drupal_password` |

| `DRUPAL_BASE_URL` | Site base URL | `http://localhost:8080` |
| `ADMIN_EMAIL` | Admin notification email | `admin@example.com` |

### File Locations

- `.env.example` - Template with all variables (committed)
- `.env.local` - Local overrides (gitignored)

## URLs Reference

### Public URLs

| URL | Description |
|-----|-------------|
| `/event-registration/register` | Public registration form |

### Admin URLs

| URL | Description | Permission |
|-----|-------------|------------|
| `/admin/config/event-registration` | Admin overview | `administer event registration` |
| `/admin/config/event-registration/events` | Event list | `administer event registration` |
| `/admin/config/event-registration/events/add` | Add new event | `administer event registration` |
| `/admin/config/event-registration/events/{id}/edit` | Edit event | `administer event registration` |
| `/admin/config/event-registration/dashboard` | Registration dashboard | `view event dashboard` |
| `/admin/config/event-registration/settings` | Module settings | `administer event registration` |

### API Endpoints

| URL | Method | Description |
|-----|--------|-------------|
| `/event-registration/ajax/dates` | GET | Get dates for category |
| `/event-registration/ajax/events` | GET | Get events for category + date |
| `/admin/config/event-registration/dashboard/export` | GET | Export CSV |

## Module Architecture

```
web/modules/custom/event_registration/
├── event_registration.info.yml      # Module metadata
├── event_registration.module         # Hook implementations
├── event_registration.install        # Schema definitions
├── event_registration.routing.yml    # Route definitions
├── event_registration.permissions.yml # Permission definitions
├── event_registration.links.menu.yml # Admin menu links
├── event_registration.services.yml   # Service definitions
├── event_registration.libraries.yml  # Asset libraries
├── config/
│   └── install/
│       └── event_registration.settings.yml
├── src/
│   ├── Controller/
│   │   ├── AdminController.php       # Admin pages
│   │   ├── AdminDashboardController.php # Dashboard + export
│   │   └── AjaxController.php        # AJAX endpoints
│   ├── Form/
│   │   ├── EventConfigForm.php       # Create/edit events
│   │   ├── EventDeleteForm.php       # Delete confirmation
│   │   ├── RegistrationForm.php      # Public registration
│   │   └── SettingsForm.php          # Module settings
│   ├── Repository/
│   │   ├── EventRepository.php       # Event data access
│   │   └── RegistrationRepository.php # Registration data access
│   └── Service/
│       ├── EmailService.php          # Email handling
│       ├── EventService.php          # Event business logic
│       ├── RegistrationService.php   # Registration logic
│       └── ValidationService.php     # Input validation
├── templates/
│   └── admin-dashboard.html.twig
├── css/
│   └── admin-dashboard.css
└── js/
    └── registration-form.js
```

### Design Principles

1. **Separation of Concerns:**
   - Forms = UI and form handling only
   - Services = Business logic
   - Repositories = Database access

2. **Dependency Injection:**
   - All services use constructor injection
   - No `\Drupal::service()` calls in classes

3. **Drupal Coding Standards:**
   - PSR-4 autoloading
   - PHPDoc documentation
   - Strict types enabled

## Database Schema

### Events Table (`event_registration_events`)

| Column | Type | Description |
|--------|------|-------------|
| `id` | SERIAL | Primary key |
| `event_name` | VARCHAR(255) | Event title |
| `category` | VARCHAR(100) | Event category |
| `registration_start` | VARCHAR(20) | Registration opens (YYYY-MM-DD) |
| `registration_end` | VARCHAR(20) | Registration closes (YYYY-MM-DD) |
| `event_date` | VARCHAR(20) | Event date (YYYY-MM-DD) |
| `created` | INT | Creation timestamp |
| `updated` | INT | Update timestamp |

**Indexes:** `category`, `event_date`, `registration_window`

### Registrations Table (`event_registration_registrations`)

| Column | Type | Description |
|--------|------|-------------|
| `id` | SERIAL | Primary key |
| `full_name` | VARCHAR(255) | Participant name |
| `email` | VARCHAR(255) | Participant email |
| `college_name` | VARCHAR(255) | Institution name |
| `department` | VARCHAR(255) | Department |
| `event_id` | INT | FK to events.id |
| `created` | INT | Registration timestamp |

**Indexes:** `event_id`, `email`, `email_event`

## Validation Logic

### Email Validation
- Standard PHP `filter_var()` with `FILTER_VALIDATE_EMAIL`

### Text Field Validation
- Allowed characters: Letters (Unicode), numbers, spaces, basic punctuation
- Pattern: `/^[\p{L}\p{N}\s\.\,\-\'\"]+$/u`

### Duplicate Prevention
- Checks for existing registration with same email + event ID
- Case-insensitive email comparison

### Registration Window
- Events only appear if current date is between `registration_start` and `registration_end`
- Server-side validation prevents registration outside window

## Email System

### Configuration

Access settings at `/admin/config/event-registration/settings`:

- **Admin Email:** Recipient for admin notifications
- **Enable User Notifications:** Toggle confirmation emails
- **Enable Admin Notifications:** Toggle admin alerts
- **Email Subjects:** Customizable with tokens

### Available Tokens

| Token | Description |
|-------|-------------|
| `[event:name]` | Event name |
| `[event:category]` | Event category |
| `[event:date]` | Formatted event date |
| `[user:name]` | Participant name |
| `[user:email]` | Participant email |
| `[user:college]` | College name |
| `[user:department]` | Department |

### Email Templates

Emails include:
- Participant information (name, email, college, department)
- Event details (name, category, date)
- Registration confirmation/notification message

## Development

### Docker Commands

```bash
# Rebuild containers
docker-compose build --no-cache

# View logs
docker-compose logs -f php

# Access PHP container
docker-compose exec php bash

# Run Drush commands
docker-compose exec php drush cr
docker-compose exec php drush pm:list

# Database access
docker-compose exec postgres psql -U drupal -d drupal
```

### Common Drush Commands

```bash
# Clear cache
drush cr

# Enable module
drush en event_registration

# Disable module
drush pm:uninstall event_registration

# Check status
drush status

# Export configuration
drush config:export
```

### Code Quality

```bash
# PHP CodeSniffer (inside container)
phpcs --standard=Drupal web/modules/custom/event_registration

# PHPStan
phpstan analyse web/modules/custom/event_registration
```

## Troubleshooting

### Container Won't Start

```bash
# Check logs
docker-compose logs

# Rebuild from scratch
docker-compose down -v
docker-compose up --build
```

### Database Connection Issues

1. Ensure PostgreSQL container is healthy:
   ```bash
   docker-compose ps
   ```

2. Check environment variables in `env/.env.local`

3. Wait for PostgreSQL initialization (check logs)

### Module Not Enabled

```bash
docker-compose exec php drush en event_registration -y
docker-compose exec php drush cr
```

### Permission Denied Errors

```bash
# Fix file permissions
docker-compose exec php chown -R www-data:www-data /var/www/html/web/sites/default/files
```

### AJAX Not Working

1. Clear Drupal cache: `drush cr`
2. Check browser console for JavaScript errors
3. Verify routes are accessible

## License

This project is licensed under the GPL-2.0-or-later license.

## Support

For issues and feature requests, please contact the development team.
