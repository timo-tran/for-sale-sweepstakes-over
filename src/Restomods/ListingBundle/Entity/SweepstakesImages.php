<?php

namespace Restomods\ListingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SweepstakesImages
 *
 * @ORM\Table(name="sweepstakes_images")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 */
class SweepstakesImages
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
     * @ORM\Column(name="src", type="string", length=63)
     */
    private $src;

    /**
     * @var integer
     *
     * @ORM\Column(name="position", type="integer", length=8, nullable=true)
     */
    private $position;

    /**
     * @var Sweepstakes
     *
     * @ORM\ManyToOne(targetEntity="Sweepstakes", inversedBy="images")
     */
    private $sweepstakes;


    public function __toString(){
        return $this->src ? $this->src : '';
    }

    public function __clone() {
        $this->setId(null);
        $this->setSweepstakes(null);
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * set id
     *
     * @return integer
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Set src
     *
     * @param string $src
     *
     * @return SweepstakesImages
     */
    public function setSrc($src)
    {
        $this->src = $src;

        return $this;
    }

    /**
     * Get src
     *
     * @return string
     */
    public function getSrc()
    {
        return $this->src;
    }

    /**
     * Set position
     *
     * @param integer $position
     *
     * @return SweepstakesImages
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return integer
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set sweepstakes
     *
     * @param Sweepstakes $sweepstakes
     *
     * @return SweepstakesImages
     */
    public function setSweepstakes(Sweepstakes $sweepstakes = null)
    {
        $this->sweepstakes = $sweepstakes;

        return $this;
    }

    /**
     * Get sweepstakes
     *
     * @return Sweepstakes
     */
    public function getSweepstakes()
    {
        return $this->sweepstakes;
    }

}
