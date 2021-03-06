<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Producer
 * @package App\Entity
 * @ORM\Entity(repositoryClass="App\Repository\ProducerRepository")
 */
class Producer extends User
{
    public const ROLE = 'producer';

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Farm", cascade={"persist"}, inversedBy="producer")
     * @Assert\Valid
     */
    private Farm $farm;

    public function __construct()
    {
        parent::__construct();
        $this->farm = (new Farm())->setProducer($this);
    }

    public function getRoles(): array
    {
        return ['ROLE_PRODUCER'];
    }

    /**
     * @return Farm
     */
    public function getFarm(): Farm
    {
        return $this->farm;
    }

    /**
     * @param Farm $farm
     * @return Producer
     */
    public function setFarm(Farm $farm): Producer
    {
        $this->farm = $farm;
        return $this;
    }
}
