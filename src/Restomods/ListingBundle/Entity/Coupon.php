<?php

namespace Restomods\ListingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Coupon
 *
 * @ORM\Table(name="coupon")
 * @ORM\Entity(repositoryClass="Restomods\ListingBundle\Repository\CouponRepository")
 */
class Coupon
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
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Application\Sonata\UserBundle\Entity\User")
     * @ORM\OrderBy({"name" = "ASC"})
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=63, nullable=true)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $url;

	/**
	 * @ORM\Column(type="boolean", nullable=false, options={"default"=0})
	 */
	private $used = false;

	private $alias = '';

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
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set user
     *
     * @param User $user
     *
     * @return Coupon
     */
    public function setUser($user)
    {
        $this->user = $user;
		return $this;
    }

    /**
     * Set code
     *
     * @return string
     *
     * @return Coupon
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Get code
     *
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
		return $this;
    }

    /**
     * Set url
     *
     * @return string
     *
     * @return Coupon
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Get url
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
		return $this;
    }


    /**
     * Set used
     *
     * @param boolean $used
     *
     * @return Coupon
     */
    public function setUsed($used)
    {
        $this->used = $used;

        return $this;
    }

    /**
     * Get used
     *
     * @return boolean
     */
    public function getUsed()
    {
        return $this->used;
    }

	public function getAlias() {
		return $this->alias;
	}

	public function setAlias($alias) {
		$this->alias = $alias;
		return $this;
	}
}
