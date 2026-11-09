# Security Policy

## Reporting a Vulnerability

If you discover a security vulnerability regarding this project, please e-mail me to [security@solidtime.io](mailto:security@solidtime.io) or use GitHub's [private vulnerability reporting](https://github.com/solidtime-io/solidtime/security/advisories/new)!

Published advisories are listed at [github.com/solidtime-io/solidtime/security/advisories](https://github.com/solidtime-io/solidtime/security/advisories).

## Verifying Docker images

The public images (`solidtime/solidtime` and `ghcr.io/solidtime-io/solidtime`) are signed with [Sigstore cosign](https://github.com/sigstore/cosign) (keyless, via GitHub Actions OIDC) and ship with an SBOM and SLSA provenance attestation.

Verify the signature:

```bash
cosign verify ghcr.io/solidtime-io/solidtime:<version> \
  --certificate-identity-regexp '^https://github.com/solidtime-io/solidtime/.github/workflows/build-public.yml@refs/(heads/main|tags/v.*)$' \
  --certificate-oidc-issuer https://token.actions.githubusercontent.com
```

On-premise images (`registry.on-premise.solidtime.io/solidtime/solidtime`) are signed the same way; log in to the registry first and use `build-onpremise.yml` instead of `build-public.yml` in the identity regex.

Show the SBOM (SPDX) and build provenance:

```bash
docker buildx imagetools inspect ghcr.io/solidtime-io/solidtime:<version> --format '{{ json .SBOM }}'
docker buildx imagetools inspect ghcr.io/solidtime-io/solidtime:<version> --format '{{ json .Provenance }}'
```

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
