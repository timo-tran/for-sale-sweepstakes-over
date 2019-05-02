<?php

namespace Restomods\ListingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * SweepstakesProduct
 *
 * @ORM\Table(name="faq")
 * @ORM\Entity(repositoryClass="Restomods\ListingBundle\Repository\FaqRepository")
 */
class Faq
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
     * @ORM\Column(name="position", type="integer")
     */
    private $position;

    /**
     * @ORM\Column(name="question", type="text")
     */
    private $question;

    /**
     * @ORM\Column(name="answer", type="text")
     */
    private $answer;

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
	 * @return Faq
     */
    public function setPosition($position)
    {
        $this->position = $position;
		return $this;
    }

	/**
	 * Get question
	 *
	 * @return string
	 */
	public function getQuestion()
	{
		return $this->question;
	}

	/**
	 * Set question
	 *
	 * @param string $question
	 *
	 * @return Faq
	 */
	public function setQuestion( $question )
	{
		$this->question = $question;

		return $this;
	}

	/**
	 * Get answer
	 *
	 * @return string
	 */
	public function getAnswer()
	{
		return $this->answer;
	}

	/**
	 * Set answer
	 *
	 * @param string $answer
	 *
	 * @return Faq
	 */
	public function setAnswer( $answer )
	{
		$this->answer = $answer;

		return $this;
	}
}
