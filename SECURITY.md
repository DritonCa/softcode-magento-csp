# Security Policy

## Reporting a vulnerability

Please report suspected vulnerabilities privately to **cazdrit@gmail.com** with a
description and reproduction steps, rather than opening a public issue.

## Supported versions

This is a reference / portfolio module; only the `main` branch receives fixes.

---

## Why this module needs care

The module lets an administrator add hosts to the storefront/admin
**Content-Security-Policy** from *Stores > Configuration*. CSP is a browser
security control, so a careless entry weakens security for **every visitor**:

- a bare `*` or a wildcard on a bare TLD (`*.com`) allows almost any origin;
- a `data:` or `javascript:` scheme source can enable script injection;
- a keyword such as `'unsafe-inline'` disables CSP's main protection;
- duplicated or malformed hosts make the policy hard to review and audit.

Convenience is therefore deliberately subordinated to safety: input is validated
**when the admin saves**, and anything outside the documented shape is rejected
with a reason instead of being shipped to browsers.

## What is accepted

Entries must be **host-sources** in this shape (`Model\HostValidator`):

```
[scheme://] host [:port]
```

| Part | Rule |
| --- | --- |
| scheme (optional) | `https`, `http`, `wss`, `ws` only |
| host | a domain (`example.com`, `sub.example.com`), optionally a single leading `*.` wildcard label (`*.example.com`); `localhost` allowed |
| port (optional) | numeric |

Hosts are **canonicalised** (trimmed, lower-cased, a trailing `/` removed) and
**deduplicated** per directive, both on save and again when the policy is built.

## What is rejected (and why)

| Input | Reason |
| --- | --- |
| empty / whitespace | not a host |
| `*` | matches every host — far too broad |
| `*.com`, `*.co.uk` | wildcard on a public suffix — too broad |
| `a.*.com` | wildcard is only valid as the leading label |
| `data:…`, `blob:…`, `javascript:…`, `ftp://…` | scheme sources / disallowed schemes can weaken script policy |
| `'self'`, `'unsafe-inline'`, nonces, hashes | keyword sources are not hosts and can disable protections |
| `example.com/path`, `example.com?x=1` | a whitelist stores hosts, not URLs |
| `host name.com`, `-bad.example.com` | malformed host |

Only the seven fetch/navigation directives the admin dropdown offers are accepted
(`script-src`, `style-src`, `img-src`, `connect-src`, `font-src`, `frame-src`,
`form-action`); any other directive is rejected.

## Access control

The configuration section is guarded by the ACL resource
`Softcode_CspWhitelist::config` (`etc/acl.xml`), nested under
*Stores > Settings > Configuration*, so only roles granted that resource can change
the whitelist.

## Notes

- The shipped `etc/csp_whitelist.xml` contains **no** hosts by design — no
  environment- or customer-specific data is committed. Manage hosts in the admin.
- The validator restricts wildcards to a single leading label but does not consult
  the Public Suffix List, so `*.example.co.uk` is accepted. Grant admin access only
  to trusted roles.
