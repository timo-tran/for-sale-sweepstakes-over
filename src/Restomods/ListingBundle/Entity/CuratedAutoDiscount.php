<?php

namespace Restomods\ListingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * CuratedAutoDiscount
 *
 * @ORM\Table(name="curated_auto_discount")
 * @ORM\Entity(repositoryClass="Restomods\ListingBundle\Repository\CuratedAutoDiscountRepository")
 */
class CuratedAutoDiscount
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
     * @var \DateTime
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="string", length=256, nullable=true)
     */
    private $image;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=256, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(name="position", type="integer")
     */
    private $position;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=256, nullable=true)
     */
    private $link;

	public function __construct() {
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return CuratedAutoDiscount
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param string $image
     */
    public function setImage($image)
    {
        $this->image = $image;
		return $this;
    }

    /**
	 * Get image
	 *
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

	/**
	 * Set title
	 *
	 * @param string $title
	 *
	 * @return CuratedAutoDiscount
	 */
    public function setTitle($title)
    {
        $this->title = $title;
		return $this;
    }

	/**
	 * Get title
	 *
	 * @return string
	 */
    public function getTitle()
    {
        return $this->title;
    }

	/**
	 * Set description
	 *
	 * @param string $description
	 *
	 * @return CuratedAutoDiscount
	 */
	public function setDescription( $description )
	{
		$this->description = $description;

		return $this;
	}

	/**
	 * Get description
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * Set link
	 *
	 * @param string $link
	 *
	 * @return CuratedAutoDiscount
	 */
	public function setLink( $link )
	{
		$this->link = $link;

		return $this;
	}

	/**
	 * Get link
	 *
	 * @return string
	 */
	public function getLink()
	{
		return $this->link;
	}

    /**
     * Get $position
	 *
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param int $position
	 *
	 * @return CuratedAutoDiscount
     */
    public function setPosition($position)
    {
        $this->position = $position;
		return $this;
    }

}
