<?php

namespace Restomods\ListingBundle\Entity;

use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * SweepstakesUserEntries
 *
 * @ORM\Table(name="sweepstakes_user_entries")
 * @ORM\Entity(repositoryClass="Restomods\ListingBundle\Repository\SweepstakesUserEntriesRepository")
 */
class SweepstakesUserEntries
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
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Application\Sonata\UserBundle\Entity\User")
     * @ORM\OrderBy({"name" = "ASC"})
     */
    private $user;

    /**
     * @var Sweepstakes
     *
     * @ORM\ManyToOne(targetEntity="Restomods\ListingBundle\Entity\Sweepstakes")
     * */
    private $sweepstakes;

    /**
     * @var SweepstakesProduct
     *
     * @ORM\ManyToOne(targetEntity="Restomods\ListingBundle\Entity\SweepstakesProduct")
  	 * @ORM\JoinColumn(nullable=true)
     * */
    private $sweepstakesProduct;

    /**
     * @var string
     *
     * @ORM\Column(name="entries", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $entries;

    /**
     * @var string
     *
     * @ORM\Column(name="description", nullable=true)
     */
    private $description;

    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    protected $verifiedAt;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=127, nullable=true)
     */
    private $funnelPurchaseId;

	/**
	 * @ORM\Column(type="string", length=127, nullable=false)
	 */
	private $stripeChargeId = '';

	/**
	 * @ORM\Column(type="string", length=127, nullable=false)
	 */
	private $stripeSubscriptionId = '';

	/**
	 * @ORM\Column(type="string", length=127, nullable=false)
	 */
	private $stripeInvoiceId = '';

	/**
	 * @ORM\Column(type="string", length=127, nullable=false)
	 */
	private $stripeRefundId = '';

	/**
	 * @ORM\Column(type="string", length=127, nullable=false)
	 */
	private $stripeDisputeId = '';

	/**
	 * @ORM\Column(type="string", length=20, nullable=false)
	 */
	private $stripeStatus = '';

	/**
	 * @ORM\Column(type="string", length=63, nullable=false)
	 */
	private $orderId = '';

	/**
	 * @ORM\Column(type="string", length=127, nullable=false)
	 */
	private $shopifyOrderId = '';

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=63)
     */
    private $utmSource;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=63)
     */
    private $utmMedium;

	/**
	 * @ORM\Column(type="boolean", nullable=false, options={"default"=0})
	 */
	private $returning = false;

	/**
	 * @ORM\Column(type="boolean", nullable=false, options={"default"=1})
	 */
	private $active = true;

	public function __construct() {
        $this->utmMedium = '';
		$this->utmSource = '';
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
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
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
    }

    /**
     * @return SweepstakesProduct
     */
    public function getSweepstakesProduct()
    {
        return $this->sweepstakesProduct;
    }

    /**
     * @param SweepstakesProduct $sweepstakesProduct
     */
    public function setSweepstakesProduct($sweepstakesProduct)
    {
        $this->sweepstakesProduct = $sweepstakesProduct;
    }

    /**
     * @return string
     */
    public function getEntries()
    {
        return $this->entries;
    }

    /**
     * @param string $entries
     */
    public function setEntries($entries)
    {
        $this->entries = $entries;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return SweepstakesUserEntries
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
     * Set verifiedAt
     *
     * @param \DateTime $verifiedAt
     *
     * @return SweepstakesUserEntries
     */
    public function setVerifiedAt($verifiedAt)
    {
        $this->verifiedAt = $verifiedAt;

        return $this;
    }

    /**
     * Get verifiedAt
     *
     * @return \DateTime
     */
    public function getVerifiedAt()
    {
        return $this->verifiedAt;
    }

    /**
     * Set funnelPurchaseId
     *
     * @param string $funnelPurchaseId
     *
     * @return SweepstakesUserEntries
     */
    public function setFunnelPurchaseId($funnelPurchaseId)
    {
        $this->funnelPurchaseId = $funnelPurchaseId;

        return $this;
    }

    /**
     * Get funnelPurchaseId
     *
     * @return string
     */
    public function getFunnelPurchaseId()
    {
        return $this->funnelPurchaseId;
    }

	/**
	 * Set stripeChargeId
	 *
	 * @param string $stripeChargeId
	 *
	 * @return Listing
	 */
	public function setStripeChargeId($stripeChargeId)
	{
		$this->stripeChargeId = $stripeChargeId;

		return $this;
	}

	/**
	 * Get stripeChargeId
	 *
	 * @return string
	 */
	public function getStripeChargeId()
	{
		return $this->stripeChargeId;
	}

	/**
	 * Set stripeRefundId
	 *
	 * @param string $stripeRefundId
	 *
	 * @return Listing
	 */
	public function setStripeRefundId($stripeRefundId)
	{
		$this->stripeRefundId = $stripeRefundId;

		return $this;
	}

	/**
	 * Get stripeRefundId
	 *
	 * @return string
	 */
	public function getStripeRefundId()
	{
		return $this->stripeRefundId;
	}

	/**
	 * Set stripeDisputeId
	 *
	 * @param string $stripeDisputeId
	 *
	 * @return Listing
	 */
	public function setStripeDisputeId($stripeDisputeId)
	{
		$this->stripeDisputeId = $stripeDisputeId;

		return $this;
	}

	/**
	 * Get stripeDisputeId
	 *
	 * @return string
	 */
	public function getStripeDisputeId()
	{
		return $this->stripeDisputeId;
	}

	/**
	 * Set stripeSubscriptionId
	 *
	 * @param string $stripeSubscriptionId
	 *
	 * @return Listing
	 */
	public function setStripeSubscriptionId($stripeSubscriptionId)
	{
		$this->stripeSubscriptionId = $stripeSubscriptionId;

		return $this;
	}

	/**
	 * Get stripeSubscriptionId
	 *
	 * @return string
	 */
	public function getStripeSubscriptionId()
	{
		return $this->stripeSubscriptionId;
	}

	/**
	 * Set stripeInvoiceId
	 *
	 * @param string $stripeInvoiceId
	 *
	 * @return Listing
	 */
	public function setStripeInvoiceId($stripeInvoiceId)
	{
		$this->stripeInvoiceId = $stripeInvoiceId;

		return $this;
	}

	/**
	 * Get stripeInvoiceId
	 *
	 * @return string
	 */
	public function getStripeInvoiceId()
	{
		return $this->stripeInvoiceId;
	}

	/**
	 * Set stripeStatus
	 *
	 * @param string $stripeStatus
	 *
	 * @return Listing
	 */
	public function setStripeStatus($stripeStatus)
	{
		$this->stripeStatus = $stripeStatus;

		return $this;
	}

	/**
	 * Get stripeStatus
	 *
	 * @return string
	 */
	public function getStripeStatus()
	{
		return $this->stripeStatus;
	}

	/**
	 * Set orderId
	 *
	 * @param string $orderId
	 *
	 * @return Listing
	 */
	public function setOrderId($orderId)
	{
		$this->orderId = $orderId;

		return $this;
	}

	/**
	 * Get orderId
	 *
	 * @return string
	 */
	public function getOrderId()
	{
		return $this->orderId;
	}



	/**
	 * Set shopifyOrderId
	 *
	 * @param string $shopifyOrderId
	 *
	 * @return Listing
	 */
	public function setShopifyOrderId($shopifyOrderId)
	{
		$this->shopifyOrderId = $shopifyOrderId;

		return $this;
	}

	/**
	 * Get shopifyOrderId
	 *
	 * @return string
	 */
	public function getShopifyOrderId()
	{
		return $this->shopifyOrderId;
	}

	/**
	 * Get utmSource
	 *
	 * @return string
	 */
	public function getUtmSource()
	{
		return $this->utmSource;
	}

	/**
	 * Set utmSource
	 *
	 * @param string $utmSource
	 *
	 * @return SweepstakesUserEntries
	 */
	public function setUtmSource( $utmSource )
	{
		$this->utmSource = $utmSource;

		return $this;
	}

	/**
	 * Get utmMedium
	 *
	 * @return string
	 */
	public function getUtmMedium()
	{
		return $this->utmMedium;
	}

	/**
	 * Set utmMedium
	 *
	 * @param string $utmMedium
	 *
	 * @return SweepstakesUserEntries
	 */
	public function setUtmMedium( $utmMedium )
	{
		$this->utmMedium = $utmMedium;

		return $this;
	}

	/**
	 * Set returning
	 *
	 * @param string $returning
	 *
	 * @return SweepstakesUserEntries
	 */
	public function setReturning($returning)
	{
		$this->returning = $returning;

		return $this;
	}

	/**
	 * Get returning
	 *
	 * @return boolean
	 */
	public function getReturning()
	{
		return $this->returning;
	}

	/**
	 * Set active
	 *
	 * @param string $active
	 *
	 * @return SweepstakesUserEntries
	 */
	public function setActive($active)
	{
		$this->active = $active;

		return $this;
	}

	/**
	 * Get active
	 *
	 * @return string
	 */
	public function getActive()
	{
		return $this->active;
	}

}
