# HealingJourney SEO Theme

Multi-site SEO dashboard WordPress theme for medical travel sites.

## Features
- Custom Post Types: Sites, SEO Reports, Keyword Maps, Content Plans
- ACF integration (falls back to post meta via `hjseo_field()` if ACF inactive)
- Front-end routes: `/sites`, `/site/{slug}`, `/reports`
- Shortcodes: `seo_site_list`, `seo_report_table`, `seo_keyword_map`, `seo_content_plan`
- Global metrics dashboard widget
- Chart.js ready (assets/js/charts.js)
- SVG upload support (admin only)

## Installation
1. Copy folder `healingjourney-seo` into `wp-content/themes/`.
2. Activate theme in WP Admin.
3. (Optional) Install & activate ACF Pro for full field UI.
4. Visit Settings → Permalinks and press Save to ensure rewrites flush.

## Development
- PHP 8.3 compatible.
- Styles scoped to front-end via `body.hjseo`.
- Helper functions in `inc/helpers.php`.

## Git
Only this theme folder is versioned. Add additional assets or docs as needed.

## License
GPL-2.0-or-later
