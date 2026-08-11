# AGENTS.md - Developer & Assistant Guidelines

## Overview
**LIMS Support Ticketing System** for the Bureau of Soils and Water Management (BSWM) – Laboratory Services Division (LSD).
This application allows internal staff and lab users to submit, manage, assign, track, and resolve support tickets.

---

## Tech Stack

### Frontend
- **Framework**: Vue 3 (Composition API `<script setup>`) + Quasar 2 Framework
- **State Management**: Pinia (`src/stores/auth.js`)
- **HTTP Client**: Axios (`src/boot/axios.js`)
- **Routing**: Vue Router (`src/router/`)
- **Styling**: SCSS / Vanilla CSS with custom design system variables (`src/css/themes.scss`)

### Backend
- **Framework**: Laravel 12
- **Database**: MySQL
- **Authentication**: Laravel Sanctum (Token-based API auth)
- **API Architecture**: RESTful API Resource controllers (`app/Http/Controllers/`)

---

## Key Architecture & Code Conventions

### 1. Authentication & State Flow
- `useAuthStore()` (Pinia) handles user authentication state, token storage, and user profile data.
- **Provider Pattern**: Auth store instances are initialized in parent Layouts (`layouts/AdminLayout.vue`, `layouts/StaffLayout.vue`, `layouts/UserLayout.vue`, `layouts/AuthLayout.vue`) using `provide('authStore', authStore)`.
- **Consumer Pattern**: Pages and nested components access auth state via `inject('authStore')` rather than importing `useAuthStore` directly in pages.

### 2. User Roles & RBAC
- **Roles**: `ADMIN`, `STAFF`, `USER`
- Access controls are enforced both on the backend (Sanctum + Policy/Middleware) and frontend (`router/index.js` navigation guards based on `meta.requiredRoles`).

### 3. Data Models & Tables
- **`users`**: `id`, `first_name`, `last_name`, `email`, `password`, `division`, `sections`, `status` (`ACTIVE`, `INACTIVE`, `SUSPENDED`, `ARCHIVED`), `role` (`ADMIN`, `STAFF`, `USER`), `position`.
- **`tickets`**: `id`, `ticket_no` (STP-YYYY-XXXX), `user_id`, `issue`, `problem_category_id`, `date_submitted`, `status` (`OPEN`, `ESCALATED`, `PENDING`, `RESOLVED`, `CLOSE`, `CANCEL`), `urgency` (`LOW`, `NORMAL`, `HIGH`, `CRITICAL`), `upload_intralab`, `upload_limsportal`, `description`, `assigned_staff_id`.
- **`problem_categories`**: `id`, `type`, `categories`.

---

## Development Environment & Commands

### Frontend (`frontend/`)
```bash
# Start Quasar development server
npm run dev

# Lint & check formatting
npm run lint:check
```

### Backend (`backend/`)
```bash
# Start Laravel server
php artisan serve

# Run database migrations & seeders
php artisan migrate --seed
```

---

## MVP Core Objectives & Limitations (from `GOALS.MD`)

### Core Features
1. Ticket Submission by Users.
2. Admin Assignment of Tickets.
3. Staff Self-Assignment & Ticket Acquisition.
4. Admin & Staff Ticket Closure permissions.
5. Notifications for ticket status updates.
6. User comments before ticket closure.
7. Dynamic Problem Categories dropdown.
8. PDF/Excel Ticket Export with filtering capability.
9. Public view for "Closed" tickets.
10. Staff Accomplishment Reports per cutoff period.

### MVP Scope Boundaries / Limitations
1. Deployed & tested on localhost environment.
2. Forgot password workflow disabled for initial MVP.
3. External ticketing disabled (internal BSWM LSD scope only).
4. PWA functionality deferred to future phase.
