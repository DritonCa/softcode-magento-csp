<?php

namespace Softcode\CspWhitelist\Config\Backend;

use Magento\Framework\App\Config\Value as ConfigValue;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Serialize\SerializerInterface;
use Softcode\CspWhitelist\Helper\Data as DataHelper;

class ArraySerialized extends ConfigValue
{
    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    protected $_serializer;

    /**
     * @var \Softcode\CspWhitelist\Helper\Data
     */
    protected $dataHelper;

    /**
     * @param $context
     * @param $registry
     * @param $config
     * @param $cacheTypeList
     * @param $resource
     * @param $resourceCollection
     * @param $serializer
     * @param $dataHelper
     * @param $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        SerializerInterface $serializer,
        DataHelper $dataHelper,
        array $data = []
    ) {
        $this->_serializer = $serializer;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Processing object before save data
     *
     * @return void
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        if(count($value) == 1){
            $value = $this->dataHelper->getDefaultValues();
        }
        unset($value['__empty']);
        $encodedValue = $this->_serializer->serialize($value);
        $this->setValue($encodedValue);
    }

    /**
     * Processing object after save data
     *
     * @return void
     */
    protected function _afterLoad()
    {
        $value = $this->getValue();
        if ($value)
        {
            $decodedValue = $this->_serializer->unserialize($value);
            $this->setValue($decodedValue);
        }
    }
}
