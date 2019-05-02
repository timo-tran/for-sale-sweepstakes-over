<?php

namespace Restomods\ListingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * SweepstakesProduct
 *
 * @ORM\Table(name="sweepstakes_product")
 * @ORM\Entity(repositoryClass="Restomods\ListingBundle\Repository\SweepstakesProductRepository")
 */
class SweepstakesProduct
{
	/**
	 * Hook timestampable behavior
	 * updates createdAt, updatedAt fields
	 */
	use TimestampableEntity;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Sweepstakes
     *
     * @ORM\ManyToOne(targetEntity="Restomods\ListingBundle\Entity\Sweepstakes")
     * */
    private $sweepstakes;

    /**
     * @ORM\Column(type="enumproducttype")
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $name;

    /**
     * @ORM\Column(type="integer")
     */
    private $entries;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2)
     */
    private $price;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $priceForDisplay;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=256, nullable=true)
     */
    private $image;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $action;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $actionSub;

    /**
     * @ORM\Column(type="string", length=63, nullable=true)
     */
    private $woocommerceProductId;

    /**
     * @ORM\Column(type="string", length=63, nullable=true)
     */
    private $limeLightProductId;

    /**
     * @var boolean
     *
     * @ORM\Column(name="active", type="boolean", nullable=true, options={"default" = 1})
     */
    private $active = true;

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
     * @return Sweepstakes
     */
    public function getSweepstakes()
    {
        return $this->sweepstakes;
    }

    /**
     * @param Sweepstakes $sweepstakes
     */
    public function setSweepstakes($sweepstakes)
    {
        $this->sweepstakes = $sweepstakes;
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
	 * Set name
	 *
	 * @param string $name
	 *
	 * @return SweepstakesProduct
	 */
	public function setName( $name )
	{
		$this->name = $name;

		return $this;
	}


	/**
	 * Get type
	 *
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * Set type
	 *
	 * @param string $type
	 *
	 * @return SweepstakesProduct
	 */
	public function setType( $type )
	{
		$this->type = $type;

		return $this;
	}


	/**
	 * Get entries
	 *
	 * @return integer
	 */
	public function getEntries()
	{
		return $this->entries;
	}

	/**
	 * Set entries
	 *
	 * @param integer $entries
	 *
	 * @return SweepstakesProduct
	 */
	public function setEntries( $entries )
	{
		$this->entries = $entries;

		return $this;
	}

    /**
	 * Get price
	 *
	 * @return integer
	 */
	public function getPrice()
	{
		return $this->price;
	}

	/**
	 * Set price
	 *
	 * @param string $price
	 *
	 * @return SweepstakesProduct
	 */
	public function setPrice( $price )
	{
		$this->price = $price;

		return $this;
	}

    /**
	 * Get $priceForDisplay
	 *
	 * @return string
	 */
	public function getPriceForDisplay()
	{
		return $this->priceForDisplay;
	}

	/**
	 * Set priceForDisplay
	 *
	 * @param string $priceForDisplay
	 *
	 * @return SweepstakesProduct
	 */
	public function setPriceForDisplay( $priceForDisplay )
	{
		$this->priceForDisplay = $priceForDisplay;

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
	 * Set title
	 *
	 * @param string $title
	 *
	 * @return SweepstakesProduct
	 */
	public function setTitle( $title )
	{
		$this->title = $title;

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
	 * Set description
	 *
	 * @param string $description
	 *
	 * @return SweepstakesProduct
	 */
	public function setDescription( $description )
	{
		$this->description = $description;

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
	 * Set image
	 *
	 * @param string $description
	 *
	 * @return SweepstakesProduct
	 */
	public function setImage( $image )
	{
		$this->image = $image;

		return $this;
	}

    /**
	 * Get action
	 *
	 * @return string
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * Set actionSub
	 *
	 * @param string $action
	 *
	 * @return SweepstakesProduct
	 */
	public function setAction( $action )
	{
		$this->action = $action;

		return $this;
	}

    /**
	 * Get actionSub
	 *
	 * @return string
	 */
	public function getActionSub()
	{
		return $this->actionSub;
	}

	/**
	 * Set actionSub
	 *
	 * @param string $actionSub
	 *
	 * @return SweepstakesProduct
	 */
	public function setActionSub( $actionSub )
	{
		$this->actionSub = $actionSub;

		return $this;
	}

    /**
	 * Get limeLightProductId
	 *
	 * @return string
	 */
	public function getLimeLightProductId()
	{
		return $this->limeLightProductId;
	}

	/**
	 * Set limeLightProductId
	 *
	 * @param string $limeLightProductId
	 *
	 * @return SweepstakesProduct
	 */
	public function setLimeLightProductId( $limeLightProductId )
	{
		$this->limeLightProductId = $limeLightProductId;

		return $this;
	}

    /**
	 * Get woocommerceProductId
	 *
	 * @return string
	 */
	public function getWoocommerceProductId()
	{
		return $this->woocommerceProductId;
	}

	/**
	 * Set woocommerceProductId
	 *
	 * @param string $woocommerceProductId
	 *
	 * @return SweepstakesProduct
	 */
	public function setWoocommerceProductId( $woocommerceProductId )
	{
		$this->woocommerceProductId = $woocommerceProductId;

		return $this;
	}

    /**
     * Set active
     *
     * @param boolean $active
     *
     * @return SweepstakesProduct
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }
}
