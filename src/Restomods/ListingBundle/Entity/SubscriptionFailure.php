<?php

namespace Restomods\ListingBundle\Entity;

use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * SubscriptionFailure
 *
 * @ORM\Table(name="subscription_failure")
 * @ORM\Entity(repositoryClass="Restomods\ListingBundle\Repository\SubscriptionFailureRepository")
 */
class SubscriptionFailure
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
    protected $nextTryAt;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $orderId;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $ancestorId;

	/**
     * @var integer
     *
     * @ORM\Column(type="integer", length=8, nullable=true)
     */
    private $retryCount;

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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return SubscriptionFailure
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
     * Set nextTryAt
     *
     * @param \DateTime $nextTryAt
     *
     * @return SubscriptionFailure
     */
    public function setNextTryAt($nextTryAt)
    {
        $this->nextTryAt = $nextTryAt;

        return $this;
    }

    /**
     * Get nextTryAt
     *
     * @return \DateTime
     */
    public function getNextTryAt()
    {
        return $this->nextTryAt;
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
	 * Set orderId
	 *
	 * @param string $orderId
	 *
	 * @return SubscriptionFailure
	 */
	public function setOrderId( $orderId )
	{
		$this->orderId = $orderId;

		return $this;
	}

	/**
	 * Get ancestorId
	 *
	 * @return string
	 */
	public function getAncestorId()
	{
		return $this->ancestorId;
	}

	/**
	 * Set ancestorId
	 *
	 * @param string $ancestorId
	 *
	 * @return SubscriptionFailure
	 */
	public function setAncestorId( $ancestorId )
	{
		$this->ancestorId = $ancestorId;

		return $this;
	}

	/**
	 * Get retryCount
	 *
	 * @return string
	 */
	public function getRetryCount()
	{
		return $this->retryCount;
	}

	/**
	 * Set retryCount
	 *
	 * @param string $retryCount
	 *
	 * @return SubscriptionFailure
	 */
	public function setRetryCount( $retryCount )
	{
		$this->retryCount = $retryCount;

		return $this;
	}

}
