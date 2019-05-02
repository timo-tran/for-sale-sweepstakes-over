<?php

namespace Restomods\ListingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Magazine
 *
 * @ORM\Table(name="magazine")
 * @ORM\Entity(repositoryClass="Restomods\ListingBundle\Repository\MagazineRepository")
 */
class Magazine
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
     * @ORM\Column(name="media_html", type="text", nullable=true)
     */
    private $mediaHtml;

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
    private $content;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=127, nullable=true)
     */
    private $action;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=256, nullable=true)
     */
    private $link;

	/**
	 * @ORM\Column(type="boolean", nullable=false, options={"default"=0})
	 */
	private $active = false;

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
     * @return Magazine
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
     * @param string $mediaHtml
     */
    public function setMediaHtml($mediaHtml)
    {
        $this->mediaHtml = $mediaHtml;
		return $this;
    }

    /**
	 * Get mediaHtml
	 *
     * @return string
     */
    public function getmediaHtml()
    {
        return $this->mediaHtml;
    }

	/**
	 * Set title
	 *
	 * @param string $title
	 *
	 * @return Magazine
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
	 * Set content
	 *
	 * @param string $content
	 *
	 * @return Magazine
	 */
	public function setContent( $content )
	{
		$this->content = $content;

		return $this;
	}

	/**
	 * Get content
	 *
	 * @return string
	 */
	public function getContent()
	{
		return $this->content;
	}

	/**
	 * Set action
	 *
	 * @param string $action
	 *
	 * @return Magazine
	 */
	public function setAction( $action )
	{
		$this->action = $action;

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
	 * Set link
	 *
	 * @param string $link
	 *
	 * @return Magazine
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
	 * Set active
	 *
	 * @param string $active
	 *
	 * @return Magazine
	 */
	public function setActive( $active )
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
