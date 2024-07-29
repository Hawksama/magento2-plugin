<?php

namespace Paynl\Payment\Block\Checkout;

use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\ProductList\Item\Block;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Url\Helper\Data as UrlHelper;
use Magento\Framework\View\Page\Config;
use Magento\Store\Model\Store;

class QuickCheckout extends Block
{
    /**
     * @var UrlHelper
     */
    protected $urlHelper;

    /**
     * ListProduct constructor.
     * @param Context $context
     * @param UrlHelper $urlHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        UrlHelper $urlHelper,
        Config $page,
        Store $store,
        array $data = []
    ) {
        $this->urlHelper = $urlHelper;
        $page->addPageAsset('Paynl_Payment::css/payQuickCheckout.css');
        parent::__construct($context, $data);
    }

}