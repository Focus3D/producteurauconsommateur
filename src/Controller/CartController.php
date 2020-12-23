<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Product;
use App\Handler\CartHandler;
use App\HandlerFactory\HandlerFactoryInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class CartController
 * @package App\Controller
 * @IsGranted("ROLE_CUSTOMER")
 * @Route("/cart")
 */
class CartController extends AbstractController
{
    /**
     * @param Product $product
     * @Route("/add/{id}", name="cart_add")
     * @return RedirectResponse
     * @IsGranted("add_to_cart", subject="product")
     */
    public function add(Product $product): RedirectResponse
    {
        $this->getUser()->addToCart($product);
        $this->getDoctrine()->getManager()->flush();
        $this->addFlash('success', 'Le produit a bien été ajouté à votre panier');
        return $this->redirectToRoute(
            'farm_show',
            [
                'slug' => $product->getFarm()->getSlug()
            ]
        );
    }

    /**
     * @param Request $request
     * @param HandlerFactoryInterface $handlerFactory
     * @return Response
     * @Route("/", name="cart_index")
     */
    public function index(Request $request, HandlerFactoryInterface $handlerFactory): Response
    {
        $handler = $handlerFactory->createHandler(CartHandler::class);

        if ($handler->handle($request, $this->getUser())) {
            return $this->redirectToRoute('cart_index');
        }

        return $this->render(
            'ui/cart/index.html.twig',
            [
                'form' => $handler->createView()
            ]
        );
    }
}
