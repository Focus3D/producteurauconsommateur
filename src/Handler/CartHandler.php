<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\CartType;
use App\HandlerFactory\AbstractHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class CartHandler
 * @package App\Handler
 */
class CartHandler extends AbstractHandler
{

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var FlashBagInterface
     */
    private FlashBagInterface $flashBag;

    public function __construct(
        EntityManagerInterface $entityManager,
        FlashBagInterface $flashBag,
        ContainerInterface $container
    ) {
        $this->entityManager = $entityManager;
        $this->flashBag = $flashBag;
        $this->setFormFactory($container->get('form.factory'));
    }

    protected function process($data, array $options): void
    {
        $this->entityManager->flush();
        $this->flashBag->add('success', 'Votre panier a été mis à jour avec succès.');
    }

    protected function configure(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'form_type' => CartType::class
            ]
        );
    }
}
