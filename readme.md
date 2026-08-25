# TN Help Guides

Author: Techn
Version: 1.2.0
Status: Production

## Purpose

TN Help Guides provides wiki-style documentation inside WordPress admin, including contextual help tabs and inline help attached to configured interface elements.

## Key Features

- Hierarchical `wiki` custom post type that preserves existing guide content.
- Accessible wiki browser under the Help Guides admin menu.
- Contextual WordPress help tabs mapped by screen ID.
- Inline help targets mapped by local admin URL rules and CSS selectors.
- Guarded Advanced Custom Fields Pro integration with a clear dependency notice.
- Capability and nonce protection for AJAX requests.
- Manifest-first, rate-limit-safe GitHub release updates.

## Folder Structure

```text
help-guides/
├── help-guides.php
├── functions/
├── scripts/
├── styles/
└── templates/
```

## Important Notes

- Advanced Custom Fields Pro is required for screen mappings and inline targets.
- Existing `wiki` posts and the original ACF field keys remain compatible.
- Version 1.1 used a non-standard `Help Guides/help_guides.php` path. Deactivate and remove that copy before installing `help-guides.zip`; the wiki content remains stored in WordPress.

## Future Considerations

- Very large guide libraries may benefit from indexed mapping data instead of scanning published guides.
