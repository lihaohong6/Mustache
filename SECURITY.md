If you find any security vulnerabilities, please report to this GitHub repository. I have never received security vulnerability emails from GitHub, so please reach out to me via email or some other means if yuu don't see a reply within a few days.

The Mustache template has a rather complex threat model.

## SSTI
Mustache is logicless, which reduces the chance of SSTI induced RCE. The Mustache engine is further locked down: partials and lambdas are not allowed.

## Trusted Administrators vs Unprivileged Editors
**Trusted admins** (with both `editsitecss` and `editsitejs` permissions, usually interface administrators) are considered fully trusted because they already have the ability to execute arbitrary JavaScript and CSS across the entire site. Therefore, as Mustache template creators they may write arbitrary HTML, including:
- Static JavaScript event handlers (e.g., `onclick="alert('XSS')"`)
- Arbitrary HTML structures
- Any JavaScript code in `<script>` tags and CSS in `<style>` tags
- Silly things such as `<a href="javascript:alert('xss')">Test</a>`

**Unprivileged editors** can only provide data to templates via parser function arguments. The security controls are designed to prevent these users from injecting JavaScript code or unsanitized CSS through template variables.

## Sanitization
The extension tries to find common patterns of problematic usage and automatically stops them. However, interface administrators will always find creative ways to bypass these checks and introduce XSS. Therefore, these security measures are provided on a best effort basis meant to curb common inadvertent XSS patterns.

Raw interpolation with `{{{` and `{{&` are absolutely not permitted since it easily leads to XSS.

Interpolation inside the `<script>` and `<style>` tags is not permitted because unprivileged editors can easily insert arbitrary js/css.

Interpolation inside HTML attributes is blocked except for whitelisted attributes so that dangerous attributes such as `onload` and `onerror` cannot be exploited. It is still possible for carelessly written JS to lead to XSS, by, for example, blindly trusting `data-*` attributes. This is a known issue for regular wikitext parsed by JavaScript and is therefore not a concern for the Mustache extension.

Some attributes such as `href` would benefit greatly from interpolation but accepts protocols such as `javascript:alert(1)`. Thus, sanitization is performed after template expansion: if a `href`/`src` attribute does not start with `https?://` or `/` it is deemed dangerous and automatically cleared.

Characters `&<>'"=` are escaped. Note that `=` is not escaped in common Mustache implementations, but it presents risks in situations such as `<div style={{test}}>` where `test=a onclick=alert(1)` causes XSS due to the attribute being unquoted.
