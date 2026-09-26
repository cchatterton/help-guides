# Changelog

All notable changes to Help Guides (Wiki-style) are recorded here.

## 1.2.2 - 2026-09-26

- Require WordPress 7.0+ and PHP 8.5+ for this release.

- Replace the independent GitHub updater with the version 1 TN Update Controller integration.
- Add local Install/Activate/Check controller actions and standardise Techn author/repository metadata.
- Preserve plugin identity, feature code, settings and activation scope; no feature-plugin release discovery runs during page rendering.

## 1.2.1 - 2026-08-25

- Restored the original version 1.1 production runtime, menu structure, labels, branding, field definitions, CSS classes, URLs, and interactions without behavioural changes.
- Added the plugin version constant and GitHub release metadata.
- Added the required manifest-first updater with public-redirect and API-last fallbacks, short rate-limit backoff, and generic failure notices.
- Added reproducible release packaging, documentation, and updater-order tests.

## 1.2.0 - 2026-08-25

- Superseded before production deployment because it changed established plugin behaviour.

## 1.1 - 2026-01-02

- Added inline guides.

## 1.0 - 2025-04-01

- Initial plugin.
