<?php
declare(strict_types=1);

namespace Softcode\CspWhitelist\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

/**
 * Reads the CSP whitelist configuration (Stores > Configuration).
 */
class Data extends AbstractHelper
{
    public function isEnabled(string $path): bool
    {
        return $this->scopeConfig->isSetFlag($path, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Grouped hosts per CSP policy, parsed from the serialized admin field.
     *
     * @return array<string, array{hosts: string[]}>
     */
    public function getWhitelistedHosts(string $configPath): array
    {
        $raw = $this->scopeConfig->getValue(
            $configPath . '/csp_script_group/script_src',
            ScopeInterface::SCOPE_STORE
        );

        $rows = json_decode((string) $raw, true);
        if (!is_array($rows)) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            if (empty($row['dropdown_field']) || empty($row['text_field'])) {
                continue;
            }
            $result[$row['dropdown_field']]['hosts'][] = $row['text_field'];
        }

        return $result;
    }

    /**
     * Seed values offered when the admin first opens the whitelist grid:
     * common third-party hosts across the CSP policies.
     *
     * @return array<string, array{dropdown_field: string, text_field: string}>
     */
    public function getDefaultValues(): array
    {
        $hosts = ['*.google.com', '*.facebook.com', '*.yotpo.com', '*.adobe.com'];
        $policies = ['script-src', 'style-src', 'img-src', 'connect-src', 'font-src', 'frame-src', 'form-action'];

        $values = [];
        $index = 0;
        foreach ($hosts as $host) {
            foreach ($policies as $policy) {
                $values['_default_' . sprintf('%02d', ++$index)] = [
                    'dropdown_field' => $policy,
                    'text_field' => $host,
                ];
            }
        }

        return $values;
    }
}
