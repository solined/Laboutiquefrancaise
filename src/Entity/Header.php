<?php

namespace App\Entity;

use App\Repository\HeaderRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=HeaderRepository::class)
 */
class Header
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id_header;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title_header;

    /**
     * @ORM\Column(type="text")
     */
    private $content_header;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $btnTitle_header;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $btnUrl_header;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $illustration_header;

    public function getIdHeader(): ?int
    {
        return $this->id_header;
    }

    public function getTitleHeader(): ?string
    {
        return $this->title_header;
    }

    public function setTitleHeader(string $title_header): self
    {
        $this->title_header = $title_header;

        return $this;
    }

    public function getContentHeader(): ?string
    {
        return $this->content_header;
    }

    public function setContentHeader(string $content_header): self
    {
        $this->content_header = $content_header;

        return $this;
    }

    public function getBtnTitleHeader(): ?string
    {
        return $this->btnTitle_header;
    }

    public function setBtnTitleHeader(string $btnTitle_header): self
    {
        $this->btnTitle_header = $btnTitle_header;

        return $this;
    }

    public function getBtnUrlHeader(): ?string
    {
        return $this->btnUrl_header;
    }

    public function setBtnUrlHeader(string $btnUrl_header): self
    {
        $this->btnUrl_header = $btnUrl_header;

        return $this;
    }

    public function getIllustrationHeader(): ?string
    {
        return $this->illustration_header;
    }

    public function setIllustrationHeader(string $illustration_header): self
    {
        $this->illustration_header = $illustration_header;

        return $this;
    }
}
