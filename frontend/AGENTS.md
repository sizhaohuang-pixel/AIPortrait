# Repository Guidelines

## Project Structure & Module Organization
- `pages/` holds all page-level views (e.g., `pages/index/index.vue`, `pages/template-detail/index.vue`).
- `components/` contains reusable UI components (e.g., `components/SkeletonLoader.vue`).
- `data/` stores mock/data sources used by pages (e.g., `data/mock.js`).
- `utils/` hosts shared utilities (e.g., `utils/history.js`).
- `static/` holds assets and icons (e.g., `static/icons/*`).
- App entry points: `App.vue`, `main.js`, routing in `pages.json`.

## Build, Test, and Development Commands
- This is a UniApp project. Use HBuilderX to run and build:
  - Run to H5 / WeChat Mini Program: use HBuilderX “Run” menu.
  - Build/Publish: use HBuilderX “Build” menu.
- If you use the UniApp CLI locally, typical commands are:
  - `npm run dev:h5` (run H5)
  - `npm run dev:mp-weixin` (run WeChat mini program)
  - These scripts are not present in this repo by default; add them only if needed.

## Coding Style & Naming Conventions
- Use Vue SFC (`.vue`) with `<template>`, `<script>`, `<style scoped>`.
- Indentation: tabs are used in existing files—keep consistent.
- ES modules with explicit extensions in imports (`.vue`, `.js`).
- Prefer `function` declarations in JS logic for clarity and consistent `this` behavior.
- Naming: kebab-case for directories, lower-case for filenames; camelCase for variables.

## Testing Guidelines
- No testing framework is configured.
- If adding tests, document the framework and add scripts in `package.json`.

## Commit & Pull Request Guidelines
- No Git history is present in this repo, so commit conventions are not established.
- If contributing, use clear, scoped messages (e.g., `feat: add preview page`).
- PRs should include: summary, screenshots for UI changes, and linked issues (if any).

## Configuration & Release Notes
- Mini Program image domains must be whitelisted (external images in `static`/mock data).
- For production, replace external image URLs with your own CDN/OSS domain.
