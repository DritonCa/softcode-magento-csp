<?php
namespace Softcode\CspWhitelist\Block\Adminhtml\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;

class DynamicFields extends AbstractFieldArray
{
    /**
     * @var string
     */
    private $_dropdownFieldRenderer;

    /**
     * Prepare to render
     *
     * @return void
     */
    protected function _prepareToRender()
    {
		$this->addColumn(
            'dropdown_field',
            [
                'label' => __('Policy Id'),
                'renderer' => $this->getDropdownFieldRenderer(),
                'class' => 'required-entry'
            ]
        );
        $this->addColumn(
            'text_field',
            [
                'label' => __('Policy Value'),
                'class' => 'required-entry'
            ]
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add New Row');
    }

    /**
     * Prepare existing row data object
     *
     * @param \Magento\Framework\DataObject $row
     * @return void
     */
    protected function _prepareArrayRow(DataObject $row)
    {
        $options = [];
        $dropdownField = $row->getDropdownField();
        if ($dropdownField !== null)
        {
            $options['option_' . $this->getDropdownFieldRenderer()->calcOptionHash($dropdownField)] = 'selected="selected"';
        }
        $row->setData('option_extra_attrs', $options);
    }

    /**
     *  Get Dropdown Field Renderer
     *
     * @return string
     */
    private function getDropdownFieldRenderer()
    {
        if (!$this->_dropdownFieldRenderer)
        {
            $this->_dropdownFieldRenderer = $this->getLayout()->createBlock(
                CustomOptions::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]);
        }
        return $this->_dropdownFieldRenderer;
    }
}
