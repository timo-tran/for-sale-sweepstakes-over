<?php

namespace Restomods\ListingBundle\Entity;

use Application\Sonata\UserBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Ivory\HttpAdapter\CurlHttpAdapter;
use Geocoder\Provider\GoogleMaps;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * UserReferrer
 *
 * @ORM\Table(name="user_referrer")
 * @ORM\Entity(repositoryClass="Restomods\ListingBundle\Repository\UserReferrerRepository")
 */

class UserReferrer
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
	private $referBy;

    /**
     * @var User
     * @ORM\OneToOne(targetEntity="Application\Sonata\UserBundle\Entity\User",cascade={"persist"})
     *
     * */
	private $signUp;


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
    public function getReferBy()
    {
        return $this->referBy;
    }

    /**
     * @param User $referBy
     */
    public function setReferBy($referBy)
    {
        $this->referBy = $referBy;
    }

    /**
     * @return User
     */
    public function getSignUp()
    {
        return $this->signUp;
    }

    /**
     * @param User $signUp
     */
    public function setSignUp($signUp)
    {
        $this->signUp = $signUp;
    }

}
