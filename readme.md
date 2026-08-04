# LIMS Ticketing Management System

A modernized, monorepo-based web application integrating a robust Laravel backend with a highly interactive Vue 3 / Quasar Vite frontend. This repository manages the unified lifecycle for the BSWM-LSD LIMS Support Ticketing System.

---

## 🎯 Goals

- **Centralized Ticketing Architecture**: Streamline support tickets for laboratory systems with a rapid, SPA-powered interface.
- **Modern UI/UX**: Utilizing a bento-inspired, glassmorphic layout wrapped in Quasar's design system using pure SCSS aesthetics.
- **Robust Monorepo Structure**: Abstracting API and Client logic in parallel while retaining single-command orchestration for rapid iterations.

---

## 🚀 Installation & Setup

Ensure you have **Node.js (>= 22)** and **PHP (Composer)** installed on your system. 

1. **Clone the Repository**
   ```bash
   git clone git@github.com-Personal:vinceviojan/LIMS-Ticketing.git
   cd LIMS-Ticketing
   ```

2. **Global Installation**
   Run the unified install command to automatically populate dependencies for both the frontend (NPM) and backend (Composer):
   ```bash
   npm run install
   ```

3. **Environment Configuration**
   - **Frontend**: Copy `frontend/.env.example` to `frontend/.env` and update `VITE_API_URL` to point to the backend.
   - **Backend**: Copy `backend/.env.example` to `backend/.env` and establish your database connections.

---

## 💻 Development (Running the app locally)

The project leverages `concurrently` to spin up both servers in a unified terminal instance!

```bash
# Starts both the Quasar Dev Server alongside Laravel Artisan Serve
npm run dev
```
* **Frontend**: Hosted dynamically via Vite (automatically opens in browser)
* **Backend**: APIs hosted seamlessly at `localhost:8000`

---

## 🛠️ Maintenance & Scripts

Handy commands built to keep the repository healthy and scalable:

### Root Scripts
* `npm run build`: Compiles the optimized production bundle for the frontend interface.
* `npm run vendor`: Publishes API vendor assets via Laravel Artisan.

### Frontend Scripts (cd frontend)
* `npm run lint`: Automatically enforces ESLint configurations alongside Prettier formatting.
* `npm run lint:check`: Validates formatting standards continuously without enforcing edits.

---

## 📚 Technological Stack

| Role | Technology | Highlights |
| :--- | :--- | :--- |
| **Frontend Framework** | Quasar (Vue 3) | Vite Bundler, Vue Router auto-mounting, Pinia State Management |
| **API Layer** | Axios | Custom global boot-file interception attached directly to Vue context. |
| **Backend Framework** | Laravel 11.x | State-of-the-art API token rendering schemas (Sanctum/Bearer). |
| **Theming** | Pure SCSS | Tokenized dark/light mappings implementing Claymorphism/Glassmorphism |

---
*Maintained by: John Vincent Viojan (jviojan08@gmail.com)*
