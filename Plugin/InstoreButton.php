<?php

namespace Paynl\Payment\Plugin;

use Magento\Framework\UrlInterface;
use Paynl\Payment\Helper\PayHelper;

class InstoreButton
{
    protected $messageManager;
    protected $order;
    protected $backendUrl;
    protected $urlInterface;
    protected $_request;

    /**
     *
     * @var \Paynl\Payment\Helper\PayHelper;
     */
    protected $payHelper;

    /**
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Backend\Model\Url $backendUrl
     * @param UrlInterface $urlInterface
     * @param PayHelper $payHelper
     */
    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Sales\Model\Order $order,
        \Magento\Backend\Model\Url $backendUrl,
        UrlInterface $urlInterface,
        PayHelper $payHelper
    ) {
        $this->messageManager = $messageManager;
        $this->order = $order;
        $this->backendUrl = $backendUrl;
        $this->urlInterface = $urlInterface;
        $this->payHelper = $payHelper;
    }

    /**
     * @param \Magento\Backend\Block\Widget\Button\Toolbar\Interceptor $subject
     * @param \Magento\Framework\View\Element\AbstractBlock $context
     * @param \Magento\Backend\Block\Widget\Button\ButtonList $buttonList
     * @return array
     */
    public function beforePushButtons(
        \Magento\Backend\Block\Widget\Button\Toolbar\Interceptor $subject,
        \Magento\Framework\View\Element\AbstractBlock $context,
        \Magento\Backend\Block\Widget\Button\ButtonList $buttonList
    ) {
        if (!$context instanceof \Magento\Sales\Block\Adminhtml\Order\View) {
            return [$context, $buttonList];
        }

        $this->_request = $context->getRequest();
        if ($this->_request->getFullActionName() == 'sales_order_view') {
            $order_id = $this->_request->getParams()['order_id'];
            $order = $this->order->load($order_id);
            $store = $order->getStore();
            $payment = $order->getPayment();
            $payment_method = $payment->getMethod();

            $currentUrl = $this->urlInterface->getCurrentUrl();

            if (!isset($buttonList->getItems()['paynl']['start_instore_payment'])) {
                if (
                    $payment_method == 'paynl_payment_instore'
                    && !$order->hasInvoices()
                    && ($store->getConfig('payment/paynl_payment_instore/show_pin_button') == 1
                        || $store->getConfig('payment/paynl_payment_instore/pinmoment') > 0)
                ) {
                    $instoreUrl = $this->backendUrl->getUrl('paynl/order/instore') . '?order_id=' . $order_id . '&return_url=' . urlencode($currentUrl);
                    $buttonList->add(
                        'start_instore_payment',
                        ['label' => __('Start Pay. Pin'), 'onclick' => 'setLocation(\'' . $instoreUrl . '\')', 'class' => 'save'],
                        'paynl'
                    );
                }
                $error = $this->payHelper->getCookie('pinError');

                if (!empty($error)) {
                    $this->messageManager->addError($error);
                }

                $this->payHelper->deleteCookie('pinError');
            }

            if (!isset($buttonList->getItems()['paynl']['start_card_refund'])) {
                if ($payment_method == 'paynl_payment_instore' && $order->hasInvoices() && $order->getBaseTotalRefunded() == 0) {
                    $cardRefundUrl = $this->backendUrl->getUrl('paynl/order/cardrefundform') . '?order_id=' . $order_id . '&return_url=' . urlencode($currentUrl);
                    $buttonList->add(
                        'start_card_refund',
                        ['label' => __('Pay. Refund by card'), 'onclick' => 'setLocation(\'' . $cardRefundUrl . '\')', 'class' => 'save'],
                        'paynl'
                    );
                }
                $error = $this->payHelper->getCookie('cardRefundError');

                if (!empty($error)) {
                    $this->messageManager->addError($error);
                }

                $this->payHelper->deleteCookie('cardRefundError');
            }
        }

        return [$context, $buttonList];
    }
}
