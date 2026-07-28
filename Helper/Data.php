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

            $directive = $row['dropdown_field'];
            $host = $row['text_field'];

            // Deduplicate per directive so a repeated host is never emitted twice
            // into the policy (defence in depth; the backend model already dedupes
            // on save).
            $existing = $result[$directive]['hosts'] ?? [];
            if (!in_array($host, $existing, true)) {
                $result[$directive]['hosts'][] = $host;
            }
        }

        return $result;
    }
}
