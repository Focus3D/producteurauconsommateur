<?php

namespace App\Tests;

use App\Entity\Order;
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

        /** @var Order $order */
        $order = $entityManager->getRepository(Order::class)->findOneBy(['state' => 'created']);

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
