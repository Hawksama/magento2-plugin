<?php

namespace Paynl\Payment\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\StoreManagerInterface;

class PayPaymentCreateFastCheckoutOrder
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var QuoteFactory
     */
    protected $quote;

    /**
     * @var QuoteManagement
     */
    protected $quoteManagement;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @param StoreManagerInterface $storeManager
     * @param QuoteFactory $quote
     * @param QuoteManagement $quoteManagement
     * @param CustomerFactory $customerFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        QuoteFactory $quote,
        QuoteManagement $quoteManagement,
        CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        OrderFactory $orderFactory
    ) {
        $this->storeManager = $storeManager;
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->orderFactory = $orderFactory;
    }

    /**
     * @param array $params
     * @return Order
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    public function create($params)
    {
        $checkoutData = $params['checkoutData'];

        $customerData = $checkoutData['customer'] ?? null;
        $billingAddressData = $checkoutData['billingAddress'] ?? null;
        $shippingAddressData = $checkoutData['shippingAddress'] ?? null;

        if (empty($customerData) || empty($billingAddressData) || empty($shippingAddressData)) {
            throw new \Exception("Missing data, cannot create order.");
        }

        $payOrderId = $params['payOrderId'];

        $orderId = explode('fastcheckout', $params['orderId']);
        $quoteId = $orderId[1];

        $quote = $this->quote->create()->loadByIdWithoutStore($quoteId);
        $storeId = $quote->getStoreId();

        $store = $this->storeManager->getStore($storeId);
        $websiteId = $store->getWebsiteId();

        $email = $customerData['email'];
        $customer = $this->customerFactory->create()
            ->setWebsiteId($websiteId)
            ->loadByEmail($email);

        if (!$customer->getEntityId()) {
            $customer->setWebsiteId($websiteId)
                ->setStore($store)
                ->setFirstname($customerData['firstName'])
                ->setLastname($customerData['lastName'])
                ->setEmail($email)
                ->setPassword($email);
            $customer->save();
        }

        $customer = $this->customerRepository->getById($customer->getEntityId());

        $quote->assignCustomer($customer);
        $quote->setSendConfirmation(1);

        $billingAddress = $quote->getBillingAddress()->addData(array(
            'customer_address_id' => '',
            'prefix' => '',
            'firstname' => $billingAddressData['firstName'] ?? $customerData['firstName'],
            'middlename' => '',
            'lastname' => $billingAddressData['lastName'] ?? $customerData['lastName'],
            'suffix' => '',
            'company' => $customerData['company'] ?? '',
            'street' => array(
                '0' => $billingAddressData['streetName'],
                '1' => $billingAddressData['streetNumber'] . ($billingAddressData['streetNumberAddition'] ?? ''),
            ),
            'city' => $billingAddressData['city'],
            'country_id' => $billingAddressData['countryCode'],
            'region' => $billingAddressData['regionCode'] ?? '',
            'postcode' => $billingAddressData['zipCode'],
            'telephone' => $customerData['phone'],
            'fax' => '',
            'vat_id' => '',
            'save_in_address_book' => 1,
        ));

        $shippingAddress = $quote->getShippingAddress()->addData(array(
            'customer_address_id' => '',
            'prefix' => '',
            'firstname' => $shippingAddressData['firstName'] ?? $customerData['firstName'],
            'middlename' => '',
            'lastname' => $shippingAddressData['lastName'] ?? $customerData['lastName'],
            'suffix' => '',
            'company' => $customerData['company'] ?? '',
            'street' => array(
                '0' => $shippingAddressData['streetName'],
                '1' => $shippingAddressData['streetNumber'] . ($shippingAddressData['streetNumberAddition'] ?? ''),
            ),
            'city' => $shippingAddressData['city'],
            'country_id' => $shippingAddressData['countryCode'],
            'region' => $shippingAddressData['regionCode'] ?? '',
            'postcode' => $shippingAddressData['zipCode'],
            'telephone' => $customerData['phone'],
            'fax' => '',
            'vat_id' => '',
            'save_in_address_book' => 1,
        ));

        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)->collectShippingRates()->setShippingMethod($store->getConfig('payment/paynl_payment_ideal/fast_checkout_shipping'));

        $quote->setPaymentMethod('paynl_payment_ideal');
        $quote->setInventoryProcessed(false);
        $quote->save();

        // Set Sales Order Payment
        $quote->getPayment()->importData(['method' => 'paynl_payment_ideal']);
        $quote->collectTotals()->save();

        $service = $this->quoteManagement->submit($quote);
        $increment_id = $service->getRealOrderId();

        $order = $this->orderFactory->create()->loadByIncrementId($increment_id);
        $additionalData = $order->getPayment()->getAdditionalInformation();
        $additionalData['transactionId'] = $payOrderId;
        $order->getPayment()->setAdditionalInformation($additionalData);
        $order->save();

        $order->addStatusHistoryComment(__('PAY. - Fast checkout order created'))->save();

        return $order;
    }
}
