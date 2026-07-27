<?php
declare(strict_types=1);

namespace Softcode\CspWhitelist\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Softcode\CspWhitelist\Helper\Data;
use PHPUnit\Framework\TestCase;

/**
 * Specifies how the admin whitelist configuration is parsed into grouped hosts,
 * and how the default seed values are generated. Runs without a Magento install
 * by mocking the helper Context's ScopeConfigInterface.
 */
class DataTest extends TestCase
{
    /**
     * @param array<string, mixed> $values store-config path => raw value
     */
    private function helper(array $values): Data
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')
            ->willReturnCallback(static fn (string $path) => $values[$path] ?? null);
        $scopeConfig->method('isSetFlag')
            ->willReturnCallback(static fn (string $path): bool => (bool)($values[$path] ?? false));

        $context = $this->createMock(Context::class);
        $context->method('getScopeConfig')->willReturn($scopeConfig);

        return new Data($context);
    }

    public function testWhitelistedHostsAreEmptyForMissingOrInvalidJson(): void
    {
        $this->assertSame([], $this->helper([])->getWhitelistedHosts('csp/whitelist'));

        $invalid = $this->helper([
            'csp/whitelist/csp_script_group/script_src' => 'not-json',
        ]);
        $this->assertSame([], $invalid->getWhitelistedHosts('csp/whitelist'));
    }

    public function testWhitelistedHostsAreGroupedByPolicy(): void
    {
        $rows = json_encode([
            ['dropdown_field' => 'script-src', 'text_field' => '*.google.com'],
            ['dropdown_field' => 'script-src', 'text_field' => '*.facebook.com'],
            ['dropdown_field' => 'img-src',    'text_field' => '*.cdn.example'],
        ]);

        $result = $this->helper([
            'csp/whitelist/csp_script_group/script_src' => $rows,
        ])->getWhitelistedHosts('csp/whitelist');

        $this->assertSame(
            [
                'script-src' => ['hosts' => ['*.google.com', '*.facebook.com']],
                'img-src'    => ['hosts' => ['*.cdn.example']],
            ],
            $result
        );
    }

    public function testWhitelistedHostsSkipRowsMissingPolicyOrHost(): void
    {
        $rows = json_encode([
            ['dropdown_field' => 'script-src', 'text_field' => '*.keep.example'],
            ['dropdown_field' => '',           'text_field' => '*.no-policy.example'],
            ['dropdown_field' => 'img-src',    'text_field' => ''],
            ['text_field' => '*.no-key.example'],
        ]);

        $result = $this->helper([
            'csp/whitelist/csp_script_group/script_src' => $rows,
        ])->getWhitelistedHosts('csp/whitelist');

        $this->assertSame(['script-src' => ['hosts' => ['*.keep.example']]], $result);
    }

    public function testDefaultValuesAreHostsTimesPolicies(): void
    {
        $defaults = $this->helper([])->getDefaultValues();

        // 4 hosts × 7 policies
        $this->assertCount(28, $defaults);

        $first = $defaults['_default_01'];
        $this->assertSame('script-src', $first['dropdown_field']);
        $this->assertSame('*.google.com', $first['text_field']);

        foreach ($defaults as $key => $row) {
            $this->assertStringStartsWith('_default_', $key);
            $this->assertArrayHasKey('dropdown_field', $row);
            $this->assertArrayHasKey('text_field', $row);
        }
    }

    public function testIsEnabledReadsTheFlag(): void
    {
        $helper = $this->helper(['csp/whitelist/enabled' => true]);

        $this->assertTrue($helper->isEnabled('csp/whitelist/enabled'));
        $this->assertFalse($helper->isEnabled('csp/whitelist/other'));
    }
}
