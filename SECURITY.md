# Security Policy

## Reporting a Vulnerability

If you discover a security vulnerability regarding this project, please e-mail me to [security@solidtime.io](mailto:security@solidtime.io)!

## Out of scope


Reports we typically won't issue an advisory for:

* Theoretical findings without a working PoC
* Raw scanner output without manual validation
* Missing/weak security headers in isolation (CSP, X-Frame-Options, HSTS, etc.)
* SPF/DKIM/DMARC on non-mail-sending domains; missing DNSSEC/CAA; TLS cipher preferences
* Self-XSS; CSRF on non-state-changing endpoints (logout, theme)
* CSV / spreadsheet formula injection in exports — treated as a spreadsheet-application issue
* Org owners or admins acting destructively within their own organization
* Anything requiring direct DB, shell, or filesystem access on a self-hosted instance
* Missing OAuth Scope enforcement (this is not implemented yet, but AI scanners flag it which is why it is included in this list until we actually support it)
