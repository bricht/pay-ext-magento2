<?php
/**
 * Copyright © CM.com. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace CM\Payments\Test\Integration\Service;

use CM\Payments\Api\Model\Data\OrderInterfaceFactory;
use CM\Payments\Api\Model\Data\PaymentInterface;
use CM\Payments\Api\Model\Data\PaymentInterfaceFactory;
use CM\Payments\Api\Model\OrderRepositoryInterface as CMOrderRepositoryInterface;
use CM\Payments\Api\Model\PaymentRepositoryInterface as CMPaymentRepositoryInterface;
use CM\Payments\Api\Service\PaymentServiceInterface;
use CM\Payments\Client\Api\ApiClientInterface;
use CM\Payments\Client\Model\CMPaymentFactory;
use CM\Payments\Client\Model\CMPaymentUrlFactory;
use CM\Payments\Client\Model\Response\ShopperCreate;
use CM\Payments\Client\Order;
use CM\Payments\Client\Payment;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment as MagentoPayment;
use CM\Payments\Model\Data\BrowserDetails;
use CM\Payments\Model\Data\CardDetails;
use CM\Payments\Service\OrderRequestBuilder;
use CM\Payments\Service\OrderService;
use CM\Payments\Service\PaymentService;
use CM\Payments\Service\Order\Request\Part\Amount;
use CM\Payments\Service\Order\Request\Part\BillingAddressKey;
use CM\Payments\Service\Order\Request\Part\Country;
use CM\Payments\Service\Order\Request\Part\Currency;
use CM\Payments\Service\Order\Request\Part\Email;
use CM\Payments\Service\Order\Request\Part\Language;
use CM\Payments\Service\Order\Request\Part\OrderId;
use CM\Payments\Service\ShopperService;
use CM\Payments\Test\Integration\IntegrationTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class PaymentServiceTest extends IntegrationTestCase
{
    /**
     * @var ApiClientInterface|MockObject
     */
    private $clientMock;

    /**
     * @var PaymentServiceInterface
     */
    private $paymentService;

    /**
     * @var ShopperService|MockObject
     */
    private $shopperServiceMock;

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCreateIdealPayment()
    {
        $magentoOrder = $this->loadOrderById('100000001');
        $magentoOrder = $this->addCurrencyToOrder($magentoOrder);

        $cmOrderFactory = $this->objectManager->create(OrderInterfaceFactory::class);
        $cmOrderOrder = $cmOrderFactory->create();
        $cmOrderRepository = $this->objectManager->get(CMOrderRepositoryInterface::class);
        $cmOrderOrder->setIncrementId($magentoOrder->getIncrementId());
        $cmOrderOrder->setOrderId((int)$magentoOrder->getEntityId());
        $cmOrderOrder->setOrderKey('0287A1617D93780EF28044B98438BF2F');
        $cmOrderRepository->save($cmOrderOrder);

        $this->clientMock->expects($this->once())->method('execute')->willReturn(
            [
                'id' => 'pid4911257676t',
                'status' => 'REDIRECTED_FOR_AUTHORIZATION',
                'urls' => [
                    0 => [
                        'purpose' => 'REDIRECT',
                        'method' => 'GET',
                        //phpcs:ignore
                        'url' => 'https://test.docdatapayments.com/ps_sim/idealbanksimulator.jsf?trxid=1625579689224&ec=4911257676&returnUrl=https%3A%2F%2Ftestsecure.docdatapayments.com%2Fps%2FreturnFromAuthorization%3FpaymentReference%3D49112576765AD00EC846B52EAED61E9FC2530CFF90%26checkDigitId%3D49112576765AD00EC846B52EAED61E9FC2530CFF90',
                        'order' => 1,
                    ],
                ]
            ]
        );

        $payment = $this->paymentService->create((int) $magentoOrder->getId());
        $this->assertNotNull(
            $payment->getId()
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCreatePaypalPayment()
    {
        $magentoOrder = $this->loadOrderById('100000001');
        $magentoOrder = $this->addCurrencyToOrder($magentoOrder);

        $cmOrderFactory = $this->objectManager->create(OrderInterfaceFactory::class);
        $cmOrderOrder = $cmOrderFactory->create();
        $cmOrderRepository = $this->objectManager->get(CMOrderRepositoryInterface::class);
        $cmOrderOrder->setIncrementId($magentoOrder->getIncrementId());
        $cmOrderOrder->setOrderId((int)$magentoOrder->getEntityId());
        $cmOrderOrder->setOrderKey('0287A1617D93780EF28044B98438BF2F');
        $cmOrderRepository->save($cmOrderOrder);

        $this->clientMock->expects($this->once())->method('execute')->willReturn(
            [
                'id' => 'pid4911261016t',
                'status' => 'REDIRECTED_FOR_AUTHORIZATION',
                'urls' => [
                    0 => [
                        'purpose' => 'REDIRECT',
                        'method' => 'GET',
                        //phpcs:ignore
                        'url' => 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&useraction=commit&token=EC-0HD94326F3768884E',
                        'order' => 1,
                    ],
                ]
            ]
        );

        $payment = $this->paymentService->create((int) $magentoOrder->getId());
        $this->assertNotNull(
            $payment->getId()
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCreateElvPayment()
    {
        $magentoOrder = $this->loadOrderById('100000001');
        $magentoOrder = $this->addCurrencyToOrder($magentoOrder);

        $cmOrderFactory = $this->objectManager->create(OrderInterfaceFactory::class);
        $cmOrderOrder = $cmOrderFactory->create();
        $cmOrderRepository = $this->objectManager->get(CMOrderRepositoryInterface::class);
        $cmOrderOrder->setIncrementId($magentoOrder->getIncrementId());
        $cmOrderOrder->setOrderId((int)$magentoOrder->getEntityId());
        $cmOrderOrder->setOrderKey('0287A1617D93780EF28044B98438BF2M');
        $cmOrderRepository->save($cmOrderOrder);

        $this->clientMock->expects($this->once())->method('execute')->willReturn(
            [
                'id' => 'pid4911261022t',
                'status' => 'AUTHORIZED'
            ]
        );

        $payment = $this->paymentService->create((int) $magentoOrder->getId());
        $this->assertNotNull(
            $payment->getId()
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCardPayment()
    {
        $magentoOrder = $this->loadOrderById('100000001');
        $magentoOrder = $this->addCurrencyToOrder($magentoOrder);

        $this->shopperServiceMock->expects($this->once())->method('createByOrderAddress')->willReturn(
            new ShopperCreate(['shopper_key' => '123', 'address_key' => '123'])
        );

        $this->clientMock->expects($this->exactly(2))->method('execute')->willReturnOnConsecutiveCalls(
            [
                'order_key' => '0287A1617D93780EF28044B98438BF2M',
                //phpcs:ignore
                'url' => 'https://testsecure.docdatapayments.com/ps/menu?merchant_name=itonomy_b_v&client_language=NL&payment_cluster_key=0287A1617D93780EF28044B98438BF2F',
                'expires_on' => '2021-07-12T08:10:57Z'
            ],
            [
                'id' => 'pid4911261022t',
                'status' => 'REDIRECTED_FOR_AUTHENTICATION',
                'redirect_url' => null,
                'urls' => [
                    [
                        //phpcs:ignore
                        'url' => 'https =>//testsecure.docdatapayments.com/ps/api/public/3dsv2/v1/transactions/3ds-method-notification',
                        'order' => '1',
                        'method' => 'POST',
                        'purpose' => 'HIDDEN_IFRAME'
                    ],
                    [
                        //phpcs:ignore
                        'url' => 'https =>//testsecure.docdatapayments.com/ps/api/public/3dsv2/v1/transactions/2637baac-fe7c-46b2-b895-d21aef765342/references/4911288290/authenticate',
                        'order' => '2',
                        'method' => 'POST',
                        'purpose' => 'IFRAME'
                    ]
                ]
            ]
        );

        $cardDetails = new CardDetails();
        $cardDetails->setEncryptedCardData('encrypted_dummy_data');
        $cardDetails->setMethod('MC');

        $browserDetails = new BrowserDetails();
        $browserDetails
            ->setShopperIp('0.0.0.0')
            ->setAccept('text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8')
            //phpcs:ignore
            ->setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36 Edge/18.18363');

        $payment = $this->paymentService->create(
            (int) $magentoOrder->getId(),
            $cardDetails,
            $browserDetails
        );
        $this->assertNotNull(
            $payment->getId()
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/cm_payments_klarna/manual_capture 1
     */
    public function testCapturePayment()
    {
        $magentoOrder = $this->loadOrderById('100000001');
        $magentoOrder = $this->addCurrencyToOrder($magentoOrder);

        $cmOrderFactory = $this->objectManager->create(OrderInterfaceFactory::class);
        $cmOrderOrder = $cmOrderFactory->create();
        $cmOrderRepository = $this->objectManager->get(CMOrderRepositoryInterface::class);
        $cmOrderOrder->setIncrementId($magentoOrder->getIncrementId());
        $cmOrderOrder->setOrderId((int) $magentoOrder->getEntityId());
        $cmOrderOrder->setOrderKey('0287A1617D93780EF28044B98438BF2M');
        $cmOrderRepository->save($cmOrderOrder);

        // Add cm payment
        $cmPaymentFactory = $this->objectManager->create(PaymentInterfaceFactory::class);
        /** @var PaymentInterface $cmOrderOrder */
        $cmPayment = $cmPaymentFactory->create();
        $cmPaymentRepository = $this->objectManager->get(CMPaymentRepositoryInterface::class);
        $cmPayment->setIncrementId($magentoOrder->getIncrementId());
        $cmPayment->setOrderId((int) $magentoOrder->getEntityId());
        $cmPayment->setOrderKey('0287A1617D93780EF28044B98438BF2M');
        $cmPayment->setPaymentId('11122');
        $cmPayment->setPaymentMethod('KLARNA');
        $cmPaymentRepository->save($cmPayment);

        $this->clientMock->expects($this->once())->method('execute');

        /** @var MagentoPayment $payment */
        $payment = $this->objectManager->create(MagentoPayment::class);
        $payment->setLastTransId('11122');

        $magentoOrder->setPayment($payment);
        $repository = $this->objectManager->get(OrderRepositoryInterface::class);
        $repository->save($magentoOrder);

        $this->paymentService->captureKlarnaPayment($magentoOrder);

        $magentoOrderHistory = $magentoOrder->getStatusHistories();

        $this->assertEquals('Payment successfully captured', end($magentoOrderHistory)->getComment());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/cm_payments_klarna/manual_capture 1
     */
    public function testCapturePaymentException()
    {
        $magentoOrder = $this->loadOrderById('100000001');
        $magentoOrder = $this->addCurrencyToOrder($magentoOrder);

        $cmOrderFactory = $this->objectManager->create(OrderInterfaceFactory::class);
        $cmOrderOrder = $cmOrderFactory->create();
        $cmOrderRepository = $this->objectManager->get(CMOrderRepositoryInterface::class);
        $cmOrderOrder->setIncrementId($magentoOrder->getIncrementId());
        $cmOrderOrder->setOrderId((int) $magentoOrder->getEntityId());
        $cmOrderOrder->setOrderKey('0287A1617D93780EF28044B98438BF2M');
        $cmOrderRepository->save($cmOrderOrder);

        // Add cm payment
        $cmPaymentFactory = $this->objectManager->create(PaymentInterfaceFactory::class);
        /** @var PaymentInterface $cmOrderOrder */
        $cmPayment = $cmPaymentFactory->create();
        $cmPaymentRepository = $this->objectManager->get(CMPaymentRepositoryInterface::class);
        $cmPayment->setIncrementId($magentoOrder->getIncrementId());
        $cmPayment->setOrderId((int) $magentoOrder->getEntityId());
        $cmPayment->setOrderKey('0287A1617D93780EF28044B98438BF2M');
        $cmPayment->setPaymentId('11122');
        $cmPayment->setPaymentMethod('KLARNA');
        $cmPaymentRepository->save($cmPayment);

        $this->clientMock->expects($this->once())->method('execute')->willThrowException(
            new RequestException(
                json_encode(['messages' => 'Already captured"']),
                new Request('GET', 'test'),
                new Response(400)
            )
        );

        /** @var MagentoPayment $payment */
        $payment = $this->objectManager->create(MagentoPayment::class);
        $payment->setLastTransId('11122');

        $magentoOrder->setPayment($payment);
        $repository = $this->objectManager->get(OrderRepositoryInterface::class);
        $repository->save($magentoOrder);

        $this->paymentService->captureKlarnaPayment($magentoOrder);

        $magentoOrderHistory = $magentoOrder->getStatusHistories();

        $this->assertMatchesRegularExpression('/Payment capture error:/', end($magentoOrderHistory)->getComment());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCapturePaymentDisabled()
    {
        $magentoOrder = $this->loadOrderById('100000001');
        $magentoOrder = $this->addCurrencyToOrder($magentoOrder);
        $this->clientMock->expects($this->never())->method('execute');

        $this->paymentService->captureKlarnaPayment($magentoOrder);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDontCapturePayment()
    {
        $magentoOrder = $this->loadOrderById('100000001');
        $magentoOrder = $this->addCurrencyToOrder($magentoOrder);

        $cmOrderFactory = $this->objectManager->create(OrderInterfaceFactory::class);
        $cmOrderOrder = $cmOrderFactory->create();
        $cmOrderRepository = $this->objectManager->get(CMOrderRepositoryInterface::class);
        $cmOrderOrder->setIncrementId($magentoOrder->getIncrementId());
        $cmOrderOrder->setOrderId((int) $magentoOrder->getEntityId());
        $cmOrderOrder->setOrderKey('0287A1617D93780EF28044B98438BF2M');
        $cmOrderRepository->save($cmOrderOrder);

        // Add cm payment
        $cmPaymentFactory = $this->objectManager->create(PaymentInterfaceFactory::class);
        /** @var PaymentInterface $cmOrderOrder */
        $cmPayment = $cmPaymentFactory->create();
        $cmPaymentRepository = $this->objectManager->get(CMPaymentRepositoryInterface::class);
        $cmPayment->setIncrementId($magentoOrder->getIncrementId());
        $cmPayment->setOrderId((int) $magentoOrder->getEntityId());
        $cmPayment->setOrderKey('0287A1617D93780EF28044B98438BF2M');
        $cmPayment->setPaymentId('11122');
        $cmPayment->setPaymentMethod('IDEAL');
        $cmPaymentRepository->save($cmPayment);

        $this->clientMock->expects($this->never())->method('execute');

        $this->paymentService->captureKlarnaPayment($magentoOrder);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testSavePaymentInDatabase()
    {
        $magentoOrder = $this->loadOrderById('100000001');
        $magentoOrder = $this->addCurrencyToOrder($magentoOrder);

        $cmOrderFactory = $this->objectManager->create(OrderInterfaceFactory::class);
        $cmOrderOrder = $cmOrderFactory->create();
        $cmOrderRepository = $this->objectManager->get(CMOrderRepositoryInterface::class);
        $cmOrderOrder->setIncrementId($magentoOrder->getIncrementId());
        $cmOrderOrder->setOrderId((int)$magentoOrder->getEntityId());
        $cmOrderOrder->setOrderKey('2287A1617D93780EF28044B98438BF2G');
        $cmOrderRepository->save($cmOrderOrder);

        $this->clientMock->expects($this->once())->method('execute')->willReturn(
            [
                'id' => 'pid4911257677t',
                'status' => 'REDIRECTED_FOR_AUTHORIZATION',
                'urls' => [
                    0 => [
                        'purpose' => 'REDIRECT',
                        'method' => 'GET',
                        //phpcs:ignore
                        'url' => 'https://test.docdatapayments.com/ps_sim/idealbanksimulator.jsf?trxid=1625579689224&ec=4911257676&returnUrl=https%3A%2F%2Ftestsecure.docdatapayments.com%2Fps%2FreturnFromAuthorization%3FpaymentReference%3D49112576765AD00EC846B52EAED61E9FC2530CFF90%26checkDigitId%3D49112576765AD00EC846B52EAED61E9FC2530CFF90',
                        'order' => 1,
                    ],
                ]
            ]
        );

        $this->paymentService->create((int) $magentoOrder->getId());

        /** @var CMPaymentRepositoryInterface $cmOrderRepository */
        $cmPaymentRepository = $this->objectManager->create(CMPaymentRepositoryInterface::class);

        $resultByOrderKey = $cmPaymentRepository->getByOrderKey('2287A1617D93780EF28044B98438BF2G');
        $this->assertSame((int)$magentoOrder->getId(), $resultByOrderKey->getOrderId());

        $resultByPaymentId = $cmPaymentRepository->getByPaymentId('pid4911257677t');
        $this->assertSame((int)$magentoOrder->getId(), $resultByPaymentId->getOrderId());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientMock = $this->createMock(ApiClientInterface::class);
        $paymentClient = $this->objectManager->create(
            Payment::class,
            [
                'apiClient' => $this->clientMock,
            ]
        );
        $orderClient = $this->objectManager->create(
            Order::class,
            [
                'apiClient' => $this->clientMock,
            ]
        );

        $this->shopperServiceMock = $this->createMock(ShopperService::class);
        $billingAddressKey = $this->objectManager->create(BillingAddressKey::class, [
            'shopperService' => $this->shopperServiceMock
        ]);

        $orderRequestBuilder = $this->objectManager->create(
            OrderRequestBuilder::class,
            [
                'orderRequestParts' => [
                    $billingAddressKey,
                    $this->objectManager->create(OrderId::class),
                    $this->objectManager->create(Amount::class),
                    $this->objectManager->create(Country::class),
                    $this->objectManager->create(Currency::class),
                    $this->objectManager->create(Email::class),
                    $this->objectManager->create(Language::class)
                ]
            ]
        );
        $orderService = $this->objectManager->create(
            OrderService::class,
            [
                'orderClient' => $orderClient,
                'orderRequestBuilder' => $orderRequestBuilder
            ],
        );

        $this->paymentService = $this->objectManager->create(
            PaymentService::class,
            [
                'paymentClient' => $paymentClient,
                'orderService' => $orderService
            ]
        );
    }
}
