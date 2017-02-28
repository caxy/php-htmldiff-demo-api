<?php

namespace AppBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * Demo
 *
 * @ApiResource
 *
 * @ORM\Table(name="demo")
 * @ORM\Entity
 */
class Demo
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="htmlOld", type="text")
     */
    private $htmlOld;

    /**
     * @var string
     *
     * @ORM\Column(name="htmlNew", type="text")
     */
    private $htmlNew;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Demo
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set htmlOld
     *
     * @param string $htmlOld
     *
     * @return Demo
     */
    public function setHtmlOld($htmlOld)
    {
        $this->htmlOld = $htmlOld;

        return $this;
    }

    /**
     * Get htmlOld
     *
     * @return string
     */
    public function getHtmlOld()
    {
        return $this->htmlOld;
    }

    /**
     * Set htmlNew
     *
     * @param string $htmlNew
     *
     * @return Demo
     */
    public function setHtmlNew($htmlNew)
    {
        $this->htmlNew = $htmlNew;

        return $this;
    }

    /**
     * Get htmlNew
     *
     * @return string
     */
    public function getHtmlNew()
    {
        return $this->htmlNew;
    }
}

