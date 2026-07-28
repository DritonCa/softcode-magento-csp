<?php
declare(strict_types=1);

namespace Softcode\CspWhitelist\Model;

/**
 * Strict validator and canonicaliser for CSP whitelist entries.
 *
 * A Content-Security-Policy whitelist is a security control: one malformed or
 * overly broad entry (a bare "*", a data: scheme, a keyword such as
 * 'unsafe-inline') can silently widen the policy for every visitor. This class
 * therefore accepts only host-source values in a documented shape and rejects
 * everything else with an explanation, so bad input is stopped when the admin
 * saves rather than shipped to the browser.
 *
 * Accepted host-source form: `[scheme://] host [:port]`
 *  - scheme (optional): https or wss (insecure http/ws are rejected)
 *  - host: a domain such as `example.com` or `sub.example.com`, optionally with a
 *    single leading `*.` wildcard label (`*.example.com`); `localhost` is allowed
 *  - port (optional): numeric
 *
 * Rejected, each with a reason: empty/whitespace, a bare `*`, a wildcard on a bare
 * TLD (`*.com`) or anywhere but the leading label, paths/queries/fragments, other
 * schemes (`data:`, `blob:`, `javascript:`, `ftp:` …) and keyword sources
 * (`'self'`, `'unsafe-inline'`, nonces, hashes).
 */
class HostValidator
{
    /**
     * CSP directives the module exposes in the admin dropdown.
     */
    public const ALLOWED_DIRECTIVES = [
        'script-src',
        'style-src',
        'img-src',
        'connect-src',
        'font-src',
        'frame-src',
        'form-action',
    ];

    // Only secure schemes. A bare host (no scheme) is still allowed and matches the
    // page's own scheme, so this does not block hosts served over plain HTTP.
    private const ALLOWED_SCHEMES = ['https', 'wss'];

    public function isAllowedDirective(string $directive): bool
    {
        return in_array($directive, self::ALLOWED_DIRECTIVES, true);
    }

    /**
     * Validate a single host-source and return its canonical (lower-cased,
     * trimmed) form.
     *
     * @throws \InvalidArgumentException with a human-readable reason
     */
    public function normalizeHost(string $host): string
    {
        $value = strtolower(trim($host));

        if ($value === '') {
            throw new \InvalidArgumentException('a whitelist host cannot be empty');
        }
        if (preg_match('/\s/', $value)) {
            throw new \InvalidArgumentException(sprintf('"%s" must not contain spaces', $host));
        }
        if (str_contains($value, "'")) {
            throw new \InvalidArgumentException(
                sprintf('"%s" looks like a keyword source; only hosts are allowed here', $host)
            );
        }

        // Optional scheme part.
        $scheme = '';
        if (preg_match('#^([a-z][a-z0-9+.\-]*)://(.+)$#', $value, $matches)) {
            $scheme = $matches[1];
            $rest = $matches[2];
            if (!in_array($scheme, self::ALLOWED_SCHEMES, true)) {
                throw new \InvalidArgumentException(
                    sprintf('scheme "%s://" is not allowed; use https, wss, or a bare host', $scheme)
                );
            }
        } elseif (preg_match('#^[a-z][a-z0-9+.\-]*:(?!\d)#', $value)) {
            // data:, blob:, javascript:, mailto: … — scheme sources, not hosts. The
            // negative lookahead lets a "host:port" such as example.com:443 through.
            throw new \InvalidArgumentException(
                sprintf('"%s" is a scheme source; enter a host instead', $host)
            );
        } else {
            $rest = $value;
        }

        // A single trailing slash (root) is tolerated; any real path is rejected.
        $rest = rtrim($rest, '/');
        if (preg_match('#[/?\#]#', $rest)) {
            throw new \InvalidArgumentException(
                sprintf('"%s" must be a host only (no path or query)', $host)
            );
        }

        // Optional numeric port.
        $port = '';
        if (preg_match('#^(.+):(\d+)$#', $rest, $matches)) {
            $rest = $matches[1];
            $port = ':' . $matches[2];
        }

        if ($rest === '*') {
            throw new \InvalidArgumentException('a bare "*" matches every host and is too broad');
        }

        // Optional single leading wildcard label.
        $wildcard = '';
        $hostOnly = $rest;
        if (str_starts_with($hostOnly, '*.')) {
            $wildcard = '*.';
            $hostOnly = substr($hostOnly, 2);
        }
        if ($hostOnly === '' || str_contains($hostOnly, '*')) {
            throw new \InvalidArgumentException(
                sprintf('"%s": a wildcard is only allowed as a single leading "*." label', $host)
            );
        }

        $labels = explode('.', $hostOnly);
        if ($wildcard !== '' && count($labels) < 2) {
            throw new \InvalidArgumentException(
                sprintf('"%s": a wildcard needs at least a domain and a TLD (e.g. *.example.com)', $host)
            );
        }
        foreach ($labels as $label) {
            if (!preg_match('/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/', $label)) {
                throw new \InvalidArgumentException(sprintf('"%s" is not a valid host', $host));
            }
        }

        $prefix = $scheme !== '' ? $scheme . '://' : '';

        return $prefix . $wildcard . $hostOnly . $port;
    }

    /**
     * Validate every grid row, canonicalise its host and drop exact duplicates
     * (same directive + host).
     *
     * @param array<array-key, array{dropdown_field?: string, text_field?: string}> $rows
     * @return array<array-key, array{dropdown_field: string, text_field: string}>
     * @throws \InvalidArgumentException
     */
    public function normalizeRows(array $rows): array
    {
        $result = [];
        $seen = [];

        foreach ($rows as $key => $row) {
            $directive = trim((string)($row['dropdown_field'] ?? ''));
            $host = (string)($row['text_field'] ?? '');

            if (!$this->isAllowedDirective($directive)) {
                throw new \InvalidArgumentException(
                    sprintf('"%s" is not a supported CSP directive', $directive)
                );
            }

            $canonicalHost = $this->normalizeHost($host);

            $dedupeKey = $directive . '|' . $canonicalHost;
            if (isset($seen[$dedupeKey])) {
                continue;
            }
            $seen[$dedupeKey] = true;

            $result[$key] = [
                'dropdown_field' => $directive,
                'text_field' => $canonicalHost,
            ];
        }

        return $result;
    }
}
