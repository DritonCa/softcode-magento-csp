<?php

namespace Softcode\CspWhitelist\Block\Adminhtml\Config\Form\Field;

use Magento\Framework\View\Element\Html\Select;

class CustomOptions extends Select
{
    /**
     * Set Name
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Set Id
     *
     * @param string $value
     * @return $this
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * Render HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions())
        {
            $this->setOptions($this->getDropdownOptions());
        }
        return parent::_toHtml();
    }

    /**
     * Get Dropdown Options
     *
     * @return array $option
     */
     private function getDropdownOptions()
    {
        $options = [
            ['label' => 'script-src', 'value' => 'script-src'],
            ['label' => 'style-src', 'value' => 'style-src'],
            ['label' => 'img-src', 'value' => 'img-src'],
            ['label' => 'connect-src', 'value' => 'connect-src'],
            ['label' => 'font-src', 'value' => 'font-src'],
			['label' => 'frame-src', 'value' => 'frame-src'],
			['label' => 'form-action', 'value' => 'form-action'],
        ];
        return $options;
    }
}
