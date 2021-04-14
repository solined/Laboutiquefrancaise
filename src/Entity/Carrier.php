<?php

namespace App\Entity;

use App\Repository\CarrierRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CarrierRepository::class)
 */
class Carrier
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="text")
     */
    private $description;

    /**
     * @ORM\Column(type="float")
     */
    private $price;

    /**
     * Affiche les infos du transporteur ( le prix )
     * __toString() : force ce que je renvoie à mon formulaire (OrderType)
     * https://www.php.net/manual/fr/function.number-format.php  : 2 ou 4parametre obligatoire
     *************************
     * @author Durand Soline <Solined.independant@php.net>
     * @version Version20210301074205 : video 54
     * MAJ du prix transporteur en Admin/backoffice : /100
     */
    public function __toString()
    {
        return $this->getName().'[br]'.$this->getDescription().'[br]'.number_format(($this->getPrice()/100), 2, ',', ',').' €';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }
}
