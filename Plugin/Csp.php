<?php
declare(strict_types=1);

namespace Softcode\CspWhitelist\Plugin;

use Magento\Csp\Model\Collector\CspWhitelistXmlCollector;
use Magento\Csp\Model\Policy\FetchPolicy;
use Softcode\CspWhitelist\Helper\Data;

/**
 * Appends admin-configured hosts to the collected CSP whitelist, so extra
 * script/style/img sources can be allowed from Stores > Configuration instead of
 * shipping a csp_whitelist.xml change.
 */
class Csp
{
    public function __construct(
        private readonly Data $helper
    ) {
    }

    /**
     * @param CspWhitelistXmlCollector $subject
     * @param \Magento\Csp\Api\Data\PolicyInterface[] $defaultPolicies
     * @return \Magento\Csp\Api\Data\PolicyInterface[]
     */
    public function afterCollect(CspWhitelistXmlCollector $subject, array $defaultPolicies = []): array
    {
        if (!$this->helper->isEnabled('softcode_general_settings/csp_script_group/enable')) {
            return $defaultPolicies;
        }

        foreach ($this->helper->getWhitelistedHosts('softcode_general_settings') as $policyId => $data) {
            $defaultPolicies[] = new FetchPolicy(
                $policyId,
                false,
                $data['hosts'],
                [],
                false,
                false,
                false,
                [],
                [],
                false,
                false
            );
        }

        return $defaultPolicies;
    }
}
