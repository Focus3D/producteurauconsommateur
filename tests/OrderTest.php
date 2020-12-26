<?php

namespace App\Tests;

use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\Producer;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class OrderTest extends WebTestCase
{
    use AuthenticationTrait;

    public function testSuccessfullCreateOrderAndCancelIt(): void
    {
        $client = static::createAuthenticatedClient('customer@email.com');

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get('router');

        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');

        $product = $entityManager->getRepository(Product::class)->findOneBy([]);

        $client->request(
            Request::METHOD_GET,
            $router->generate(
                'cart_add',
                [
                    'id' => $product->getId()
                ]
            )
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $client->request(Request::METHOD_GET, $router->generate('order_create'));

        self::assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $client->followRedirect();

        $customer = $entityManager->getRepository(Customer::class)->findOneByEmail('customer@email.com');

        /** @var Order $order */
        $order = $entityManager->getRepository(Order::class)->findOneBy(
            [
                'state' => 'created',
                'customer' => $customer
            ]
        );

        $client->request(
            Request::METHOD_GET,
            $router->generate(
                'order_cancel',
                [
                    'id' => $order->getId()
                ]
            )
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $entityManager->clear();

        $order = $entityManager->getRepository(Order::class)->find($order->getId());

        self::assertEquals('canceled', $order->getState());
    }

    public function testSuccessfullCancelOrderInAcceptedStatus(): void
    {
        $client = static::createAuthenticatedClient('customer@email.com');

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get('router');

        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');

        $customer = $entityManager->getRepository(Customer::class)->findOneByEmail('customer@email.com');

        $order = $entityManager->getRepository(Order::class)->findOneBy(
            [
                'customer' => $customer,
                'state' => 'accepted'
            ]
        );

        $client->request(
            Request::METHOD_GET,
            $router->generate(
                'order_cancel',
                [
                    'id' => $order->getId()
                ]
            )
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $entityManager->clear();

        $order = $entityManager->getRepository(Order::class)->find($order->getId());

        self::assertEquals('canceled', $order->getState());
    }

    public function testSuccessfullRefuseOrder(): void
    {
        $client = static::createAuthenticatedClient('producer@email.com');

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get('router');

        $client->request(Request::METHOD_GET, $router->generate('order_manage'));

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');

        $producer = $entityManager->getRepository(Producer::class)->findOneByEmail("producer@email.com");

        /** @var Order $order */
        $order = $entityManager->getRepository(Order::class)->findOneBy(
            [
                'state' => 'created',
                'farm' => $producer->getFarm()
            ]
        );

        $client->request(
            Request::METHOD_GET,
            $router->generate(
                'order_refuse',
                [
                    'id' => $order->getId()
                ]
            )
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $entityManager->clear();

        $order = $entityManager->getRepository(Order::class)->find($order->getId());

        self::assertEquals('refused', $order->getState());
    }

    public function testSuccessfullAcceptOrder(): void
    {
        $client = static::createAuthenticatedClient('producer@email.com');

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get('router');

        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');

        $producer = $entityManager->getRepository(Producer::class)->findOneByEmail("producer@email.com");

        /** @var Order $order */
        $order = $entityManager->getRepository(Order::class)->findOneBy(
            [
                'state' => 'created',
                'farm' => $producer->getFarm()
            ]
        );

        $client->request(
            Request::METHOD_GET,
            $router->generate(
                'order_accept',
                [
                    'id' => $order->getId()
                ]
            )
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $entityManager->clear();

        $order = $entityManager->getRepository(Order::class)->find($order->getId());

        self::assertEquals('accepted', $order->getState());
    }

    public function testSuccessfullSettleOrder(): void
    {
        $client = static::createAuthenticatedClient('customer@email.com');

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get('router');

        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');

        $customer = $entityManager->getRepository(Customer::class)->findOneByEmail("customer@email.com");

        /** @var Order $order */
        $order = $entityManager->getRepository(Order::class)->findOneBy(
            [
                'state' => 'accepted',
                'customer' => $customer->getId()
            ]
        );

        $client->request(
            Request::METHOD_GET,
            $router->generate(
                'order_settle',
                [
                    'id' => $order->getId()
                ]
            )
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $entityManager->clear();

        $order = $entityManager->getRepository(Order::class)->find($order->getId());

        self::assertEquals('settled', $order->getState());
    }

    public function testSuccessfullProcessOrder(): void
    {
        $client = static::createAuthenticatedClient('producer@email.com');

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get('router');

        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');

        $producer = $entityManager->getRepository(Producer::class)->findOneByEmail('producer@email.com');

        /** @var Order $order */
        $order = $entityManager->getRepository(Order::class)->findOneBy(
            [
                'farm' => $producer->getFarm(),
                'state' => 'settled'
            ]
        );

        $client->request(
            Request::METHOD_GET,
            $router->generate(
                'order_process',
                [
                    'id' => $order->getId()
                ]
            )
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $entityManager->clear();

        $order = $entityManager->getRepository(Order::class)->find($order->getId());

        self::assertEquals('processing', $order->getState());
    }

    public function testSuccessfullReadyOrder(): void
    {
        $client = static::createAuthenticatedClient('producer@email.com');

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get('router');

        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');

        $producer = $entityManager->getRepository(Producer::class)->findOneByEmail('producer@email.com');

        /** @var Order $order */
        $order = $entityManager->getRepository(Order::class)->findOneBy(
            [
                'farm' => $producer->getFarm(),
                'state' => 'processing'
            ]
        );

        $client->request(
            Request::METHOD_GET,
            $router->generate(
                'order_ready',
                [
                    'id' => $order->getId()
                ]
            )
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $entityManager->clear();

        $order = $entityManager->getRepository(Order::class)->find($order->getId());

        self::assertEquals('ready', $order->getState());
    }

    public function testSuccessfullDeliverOrder(): void
    {
        $client = static::createAuthenticatedClient('producer@email.com');

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get('router');

        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');

        $producer = $entityManager->getRepository(Producer::class)->findOneByEmail('producer@email.com');

        /** @var Order $order */
        $order = $entityManager->getRepository(Order::class)->findOneBy(
            [
                'farm' => $producer->getFarm(),
                'state' => 'ready'
            ]
        );

        $client->request(
            Request::METHOD_GET,
            $router->generate(
                'order_deliver',
                [
                    'id' => $order->getId()
                ]
            )
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $entityManager->clear();

        $order = $entityManager->getRepository(Order::class)->find($order->getId());

        self::assertEquals('issued', $order->getState());
    }

    public function testAccessDeniedOrderCreateForProducer(): void
    {
        $client = static::createAuthenticatedClient('producer@email.com');

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get('router');

        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');

        $product = $entityManager->getRepository(Product::class)->findOneBy([]);

        $client->request(
            Request::METHOD_GET,
            $router->generate(
                'cart_add',
                [
                    'id' => $product->getId()
                ]
            )
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testRedirectToLoginIfUserIsNotLoggedForOrderCreate(): void
    {
        $client = static::createClient();

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get('router');

        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');

        $product = $entityManager->getRepository(Product::class)->findOneBy([]);

        $client->request(
            Request::METHOD_GET,
            $router->generate(
                'cart_add',
                [
                    'id' => $product->getId()
                ]
            )
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $client->followRedirect();

        self::assertRouteSame('security_login');
    }

    public function testSuccessfullOrderHistory(): void
    {
        $client = static::createAuthenticatedClient('customer@email.com');

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get('router');

        $client->request(Request::METHOD_GET, $router->generate('order_history'));

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testRedirectToLoginIfUserIsNotLoggedForOrderHistory(): void
    {
        $client = static::createClient();

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get('router');

        $client->request(Request::METHOD_GET, $router->generate('order_history'));

        self::assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $client->followRedirect();

        self::assertRouteSame('security_login');
    }

    public function testAccessDeniedIfUserIsNotACustomerForOrderHistory(): void
    {
        $client = static::createAuthenticatedClient('producer@email.com');

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get('router');

        $client->request(Request::METHOD_GET, $router->generate('order_history'));

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    //
    public function testSuccessfullOrderManage(): void
    {
        $client = static::createAuthenticatedClient('producer@email.com');

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get('router');

        $client->request(Request::METHOD_GET, $router->generate('order_manage'));

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testRedirectToLoginIfUserIsNotLoggedForOrderManage(): void
    {
        $client = static::createClient();

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get('router');

        $client->request(Request::METHOD_GET, $router->generate('order_manage'));

        self::assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $client->followRedirect();

        self::assertRouteSame('security_login');
    }

    public function testAccessDeniedIfUserIsNotAProducerForOrderManage(): void
    {
        $client = static::createAuthenticatedClient('customer@email.com');

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get('router');

        $client->request(Request::METHOD_GET, $router->generate('order_manage'));

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAccessDeniedCancelOrder(): void
    {
        $client = static::createAuthenticatedClient("producer@email.com");

        /** @var RouterInterface $router */
        $router = $client->getContainer()->get("router");

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $client->getContainer()->get("doctrine.orm.entity_manager");

        $order = $entityManager->getRepository(Order::class)->findOneBy(["state" => "created"]);

        $client->request(
            Request::METHOD_GET,
            $router->generate(
                "order_cancel",
                [
                    "id" => $order->getId()
                ]
            )
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
