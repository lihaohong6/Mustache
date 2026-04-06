If you find any security vulnerabilities, please report to this GitHub repository. I have never received security vulnerability emails from GitHub, so please reach out to me via email or some other means if you don't see a reply within a few days.

The Mustache template has a rather complex threat model.

## SSTI
Mustache is logicless, which reduces the chance of SSTI induced RCE. Note that mustache.php did have a [RCE vulnerability](https://github.com/advisories/GHSA-4rmr-c2jx-vx27) a few years ago.

## Trusted Administrators vs Unprivileged Editors
**Trusted admins** (with both `editsitecss` and `editsitejs` permissions, usually interface administrators) are considered fully trusted because they already have the ability to execute arbitrary JavaScript and CSS across the entire site. Therefore, as Mustache template creators they may write arbitrary HTML, including:
- Static JavaScript event handlers (e.g., `onclick="alert('XSS')"`)
- Arbitrary HTML structures
- Any JavaScript code in `<script>` tags and CSS in `<style>` tags
- Silly things such as `<a href="javascript:alert('xss')">Test</a>`

**Unprivileged editors** can only provide data to templates via parser function arguments. The security controls are designed to prevent these users from injecting JavaScript code or unsanitized CSS through template variables.

## Filters
The extension supports Mustache filters via the `PRAGMA_FILTERS` feature, using the syntax `{{ variable|filter-name }}`. Filters transform values into a form safe for a specific output context. The validator enforces that the correct filter is used depending on where interpolation appears; Mustache templates that use interpolation without a required filter are rejected on save.

## Sanitization
The extension tries to stop interface administrators automatically when it detects a pattern which indicates a mistake. For example, interpolation without proper filtering inside JavaScript and CSS. However, this check is done in a best effort basis since IAs can easily introduce XSS if they wish to.

Raw interpolation with `{{{` and `{{&` are absolutely not permitted since it easily leads to XSS.

Interpolation inside `<script>` tags requires a `js-*` filter (`js-string` or `js-identifier`). Interpolation inside `<style>` tags requires a `css-*` filter (`css-selector` or `css-value`). Templates that violate these requirements are rejected on save.

Interpolation inside HTML attributes is blocked except for whitelisted attributes so that dangerous attributes such as `onload` and `onerror` cannot be exploited. Whitelisted attributes require the appropriate filter: `url` for `href`/`src`, `css-value` for `style`, and `attribute` for all others. It is still possible for carelessly written JS to lead to XSS, by, for example, blindly trusting `data-*` attributes. This is a known issue for regular wikitext parsed by JavaScript and is therefore not a concern for the Mustache extension.

Characters `&<>"` are escaped in the default `{{ }}` output.
