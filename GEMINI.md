# Project Overview

This monorepo project, named `illustreas-monorepo`, consists of three main applications: a Laravel API backend, a Vue 3 backoffice frontend, and a Vue 3 frontoffice frontend. It leverages `npm workspaces` for managing these interconnected projects. A shared UI library (`@illustreas/shared-ui`) is utilized across the frontend applications to maintain consistency.

## Building and Running

### General Monorepo Commands
The monorepo uses `npm workspaces`. You can run commands in specific workspaces using `npm run --workspace=<workspace-name> <script-name>`.

### API (Laravel)
*   **Technology:** Laravel (PHP)
*   **Dependencies:** Composer for PHP, npm/Yarn for Node.js (Vite)
*   **Commands:**
    *   `composer install` (to install PHP dependencies)
    *   `npm install` (to install Node.js dependencies for Vite)
    *   `npm run dev` (for development with Vite)
    *   `npm run build` (to build assets for production)
    *   `php artisan serve` (to serve the Laravel application)

### Backoffice (Vue 3)
*   **Technology:** Vue 3, Vite, Pinia, Vue Router, Tiptap
*   **Dependencies:** npm/Yarn
*   **Commands:**
    *   `npm install` (or `yarn install`) in the `backoffice` directory.
    *   `npm run dev` (for development with Vite)
    *   `npm run build` (to build for production)
    *   `npm run preview` (to preview the production build)

### Frontoffice (Vue 3)
*   **Technology:** Vue 3, Vite, Pinia, Vue Router, Bootstrap, FontAwesome, VueUse
*   **Dependencies:** npm/Yarn
*   **Commands:**
    *   `npm install` (or `yarn install`) in the `frontoffice` directory.
    *   `npm run dev` (for development with Vite)
    *   `npm run build` (to build for production)
    *   `npm run preview` (to preview the production build)

## Development Conventions

*   **Monorepo Management:** `npm workspaces` is used for managing multiple packages within a single repository.
*   **Frontend Framework:** Vue 3 with Composition API is consistently used for both `backoffice` and `frontoffice` applications.
*   **State Management:** Pinia is the chosen state management library for Vue applications.
*   **Routing:** Vue Router handles client-side routing in frontend applications.
*   **UI Library:** A custom shared UI library (`@illustreas/shared-ui`) is utilized for consistent UI components.
*   **Build Tooling:** Vite is used across all JavaScript/Vue projects for fast development and optimized production builds.
*   **Backend Framework:** Laravel (PHP) provides the API layer, including authentication via Laravel Sanctum.
*   **Image Handling:** Cloudinary is integrated with the Laravel API for robust image management.
