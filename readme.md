# Help Guides (Wiki-style)

Author: TECHN
Version: 1.2.2
Status: Production

## Purpose

Adds the existing Wiki custom post type, Wiki Pages admin interface, screen ID display, contextual help tabs, and class-targeted inline guides.

## Key Features

- Original production Wiki Pages menu and navigation
- Hierarchical `wiki` post type
- ACF-powered screen and CSS class mappings
- Contextual WordPress help tabs
- Magenta inline guide markers and popovers
- Native WordPress updates through TN Update Controller

## Folder Structure

The production plugin remains in its established `Help Guides/` folder with `help_guides.php` as its main file. Runtime modules remain in `includes/`.

## Important Notes

- Version 1.2.2 preserves the original version 1.1 runtime behaviour and identifiers.
- Advanced Custom Fields Pro is required by the original mapping features.
- The shared controller client supplies local update-management links.

## Future Considerations

Any security, accessibility, UI, naming, or architecture changes should be developed and regression-tested separately from this compatibility release.

## Controller migration — 1.2.2

Updates are now supplied by [TN Update Controller](https://github.com/cchatterton/tn-update-controller). The old independent updater has been removed. Plugin identity, feature settings and activation scope are unchanged. Install/activate/check links use local controller detection and never fetch release metadata while rendering. Legacy update guidance below or in historical notes is superseded by this controller integration.

Release order: build and validate the ZIP, publish its matching GitHub release asset, then publish verified controller catalogue metadata. Existing update.json endpoints are maintained only for older, not-yet-migrated installations, after asset verification.
