<?php

namespace Restomods\ListingBundle\Entity;

use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Event
 *
 * @ORM\Table(name="event")
 * @ORM\Entity(repositoryClass="Restomods\ListingBundle\Repository\EventRepository")
 */
class Event
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
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Application\Sonata\UserBundle\Entity\User")
     * @ORM\OrderBy({"name" = "ASC"})
  	 * @ORM\JoinColumn(nullable=true)
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=128)
     */
    private $session;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=63)
     */
    private $clientIp;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=63)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=10)
     */
    private $requestMethod;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=63)
     */
    private $requestPath;

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

	public function __construct() {
        $this->utmMedium = '';
		$this->utmSource = '';
		$this->path = '';
		$this->utmSource = '';
		$this->name = '';
		$this->session = '';
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Event
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
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param string $clientIp
     */
    public function setClientIp($clientIp)
    {
        $this->clientIp = $clientIp;
		return $this;
    }

	/**
	 * Get clientIp
	 *
	 * @return string
	 */
    public function getClientIp()
    {
        return $this->clientIp;
    }

	/**
	 * Get session
	 *
	 * @return string
	 */
	public function getSession()
	{
		return $this->session;
	}

	/**
	 * Set session
	 *
	 * @param string $session
	 *
	 * @return Event
	 */
	public function setSession( $session )
	{
		$this->session = $session;

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
	 * @return Event
	 */
	public function setName( $name )
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * Get requestMethod
	 *
	 * @return string
	 */
	public function getRequestMethod()
	{
		return $this->requestMethod;
	}

	/**
	 * Set requestMethod
	 *
	 * @param string $requestMethod
	 *
	 * @return Event
	 */
	public function setRequestMethod( $requestMethod )
	{
		$this->requestMethod = $requestMethod;

		return $this;
	}

	/**
	 * Get requestPath
	 *
	 * @return string
	 */
	public function getRequestPath()
	{
		return $this->requestPath;
	}

	/**
	 * Set requestPath
	 *
	 * @param string $requestPath
	 *
	 * @return Event
	 */
	public function setRequestPath( $requestPath )
	{
		$this->requestPath = $requestPath;

		return $this;
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
	 * @return Event
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
	 * @return Event
	 */
	public function setUtmMedium( $utmMedium )
	{
		$this->utmMedium = $utmMedium;

		return $this;
	}

}
