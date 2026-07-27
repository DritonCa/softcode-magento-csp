<?php
declare(strict_types=1);

namespace Softcode\CspWhitelist\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value as ConfigValue;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Softcode\CspWhitelist\Helper\Data as DataHelper;
use Softcode\CspWhitelist\Model\HostValidator;

/**
 * Backend model for the serialized CSP whitelist grid.
 *
 * On save it validates and canonicalises every host and drops duplicates, so the
 * stored policy can never contain malformed, dangerous or repeated sources. An
 * invalid entry aborts the save with a message naming the offending value.
 */
class ArraySerialized extends ConfigValue
{
    private SerializerInterface $serializer;

    private DataHelper $dataHelper;

    private HostValidator $hostValidator;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        SerializerInterface $serializer,
        DataHelper $dataHelper,
        HostValidator $hostValidator,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->serializer = $serializer;
        $this->dataHelper = $dataHelper;
        $this->hostValidator = $hostValidator;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Validate, canonicalise and serialize the grid rows before persisting.
     *
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $value = $this->getValue();

        if (is_array($value)) {
            // An empty grid arrives as the single "__empty" placeholder row; seed the
            // documented defaults in that case, matching the module's prior behaviour.
            if (count($value) === 1) {
                $value = $this->dataHelper->getDefaultValues();
            }
            unset($value['__empty']);

            try {
                $value = $this->hostValidator->normalizeRows($value);
            } catch (\InvalidArgumentException $e) {
                throw new LocalizedException(__('CSP whitelist: %1', $e->getMessage()));
            }
        }

        $this->setValue($this->serializer->serialize($value));

        return parent::beforeSave();
    }

    /**
     * Unserialize the stored value after loading.
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        $value = $this->getValue();
        if ($value) {
            $this->setValue($this->serializer->unserialize($value));
        }

        return parent::_afterLoad();
    }
}
