<?php

namespace Paynl\Payment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class UseEstimate implements ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $arrOptions = $this->toArray();

        $arrResult = [];
        foreach ($arrOptions as $value => $label) {
            $arrResult[] = ['value' => $value, 'label' => $label];
        }
        return $arrResult;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            0 => __('Off'),
            1 => __('On - as Optional'),
            2 => __('On - as Required')
        ];
    }
}
