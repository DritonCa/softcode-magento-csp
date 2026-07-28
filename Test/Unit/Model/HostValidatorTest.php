<?php
declare(strict_types=1);

namespace Softcode\CspWhitelist\Test\Unit\Model;

use Softcode\CspWhitelist\Model\HostValidator;
use PHPUnit\Framework\TestCase;

/**
 * Specifies the strict host rules for the CSP whitelist: which host-sources are
 * accepted (and how they are canonicalised), which are rejected, and that rows
 * are validated and deduplicated as a set.
 */
class HostValidatorTest extends TestCase
{
    private HostValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new HostValidator();
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public function validHostProvider(): array
    {
        return [
            'bare domain'            => ['example.com', 'example.com'],
            'subdomain'              => ['sub.example.com', 'sub.example.com'],
            'leading wildcard'       => ['*.example.com', '*.example.com'],
            'localhost'              => ['localhost', 'localhost'],
            'lowercased + trimmed'   => ['  EXAMPLE.com  ', 'example.com'],
            'https scheme kept'      => ['https://example.com', 'https://example.com'],
            'trailing slash dropped' => ['https://www.gstatic.com/', 'https://www.gstatic.com'],
            'websocket scheme'       => ['wss://widget.zopim.com', 'wss://widget.zopim.com'],
            'explicit port'          => ['example.com:443', 'example.com:443'],
            'wildcard + scheme'      => ['https://*.bambora.com', 'https://*.bambora.com'],
        ];
    }

    /**
     * @dataProvider validHostProvider
     */
    public function testAcceptsAndCanonicalises(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->validator->normalizeHost($input));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public function invalidHostProvider(): array
    {
        return [
            'empty'                 => [''],
            'whitespace only'       => ['   '],
            'bare wildcard'         => ['*'],
            'scheme + bare wildcard'=> ['http://*'],
            'wildcard on bare TLD'  => ['*.com'],
            'wildcard not leading'  => ['a.*.com'],
            'keyword self'          => ["'self'"],
            'keyword unsafe-inline' => ["'unsafe-inline'"],
            'data scheme'           => ['data:image/png'],
            'javascript scheme'     => ['javascript:alert(1)'],
            'disallowed scheme'     => ['ftp://example.com'],
            'insecure http scheme'  => ['http://example.com'],
            'insecure ws scheme'    => ['ws://example.com'],
            'internal space'        => ['exa mple.com'],
            'has path'              => ['example.com/some/path'],
            'has query'             => ['example.com?a=1'],
            'trailing hyphen label' => ['-bad.example.com'],
        ];
    }

    /**
     * @dataProvider invalidHostProvider
     */
    public function testRejectsUnsafeOrMalformedHost(string $host): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->validator->normalizeHost($host);
    }

    public function testIsAllowedDirective(): void
    {
        $this->assertTrue($this->validator->isAllowedDirective('script-src'));
        $this->assertTrue($this->validator->isAllowedDirective('form-action'));
        $this->assertFalse($this->validator->isAllowedDirective('default-src'));
        $this->assertFalse($this->validator->isAllowedDirective(''));
    }

    public function testNormalizeRowsCanonicalisesAndDeduplicates(): void
    {
        $rows = [
            '_1' => ['dropdown_field' => 'script-src', 'text_field' => '*.google.com'],
            '_2' => ['dropdown_field' => 'script-src', 'text_field' => '  *.GOOGLE.com '], // dup after canonicalise
            '_3' => ['dropdown_field' => 'img-src',    'text_field' => 'https://cdn.example/'],
        ];

        $result = $this->validator->normalizeRows($rows);

        $this->assertCount(2, $result);
        $this->assertSame(
            ['dropdown_field' => 'script-src', 'text_field' => '*.google.com'],
            $result['_1']
        );
        $this->assertSame(
            ['dropdown_field' => 'img-src', 'text_field' => 'https://cdn.example'],
            $result['_3']
        );
    }

    public function testNormalizeRowsRejectsUnknownDirective(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->validator->normalizeRows([
            '_1' => ['dropdown_field' => 'default-src', 'text_field' => 'example.com'],
        ]);
    }

    public function testNormalizeRowsRejectsInvalidHost(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->validator->normalizeRows([
            '_1' => ['dropdown_field' => 'script-src', 'text_field' => "'unsafe-inline'"],
        ]);
    }
}
