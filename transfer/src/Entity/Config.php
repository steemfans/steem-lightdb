<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ConfigRepository")
 */
class Config
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $param;

    /**
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    private $val;

    public function getId()
    {
        return $this->id;
    }

    public function getParam(): ?string
    {
        return $this->param;
    }

    public function setParam(string $param): self
    {
        $this->param = $param;

        return $this;
    }

    public function getVal(): ?string
    {
        return $this->val;
    }

    public function setVal(?string $val): self
    {
        $this->val = $val;

        return $this;
    }
}
