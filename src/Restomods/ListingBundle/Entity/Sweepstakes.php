<?php

namespace Restomods\ListingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Sweepstakes
 *
 * @ORM\Table(name="sweepstakes")
 * @ORM\Entity(repositoryClass="Restomods\ListingBundle\Repository\SweepstakesRepository")
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Uploadable(path="uploads/media", filenameGenerator="SHA1")
 */
class Sweepstakes
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="car_name", type="string", length=255)
     */
	private $carName;

    /**
     * @var string
     *
     * @ORM\Column(name="video", type="string", length=256, nullable=true)
     */
	private $video;

    /**
     * @var string
     *
     * @ORM\Column(name="closing_video", type="string", length=256, nullable=true)
     */
	private $closingVideo;

    /**
     * @var string
     *
     * @ORM\Column(name="sweepstakes_limit", type="integer")
     */
	private $sweepstakesLimit;

    /**
     * @var string
     *
     * @ORM\Column(name="prize", type="text")
     */
	private $prize;

    /**
     * @var string
     *
     * @ORM\Column(name="requirements", type="text")
     */
	private $requirements;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_date", type="datetime")
     */
    private $startDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_date", type="datetime")
     */
    private $endDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="select_winner_date", type="datetime")
     */
    private $selectWinnerDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="contact_winner_date", type="datetime")
     */
    private $contactWinnerDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="reveal_winner_date", type="datetime")
     */
    private $revealWinnerDate;

    /**
     * Many Users have Many Groups.
     * @ORM\ManyToMany(targetEntity="Application\Sonata\UserBundle\Entity\User")
     * @ORM\JoinTable(name="sweepstakes_users",
     *      joinColumns={@ORM\JoinColumn(name="sweepstakes_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")}
     *      )
     */
    private $users;

    /**
     * @Assert\File(
     *     maxSize = "1M",
     *     mimeTypes = {"image/jpeg", "image/png"},
     *     maxSizeMessage = "The maximum allowed file size is 1MB.",
     *     mimeTypesMessage = "Only the filetypes image are allowed."
     * )
     */
    private $featuredImage1File;

    /**
     * @var string
     *
     * @ORM\Column(name="featured_image1", type="string", length=127, nullable=false)
     * @Gedmo\UploadableFileName()
     */
    private $featuredImage1;
    private $featuredImage1FileTemp;

    /**
     * @Assert\File(
     *     maxSize = "1M",
     *     mimeTypes = {"image/jpeg", "image/png"},
     *     maxSizeMessage = "The maximum allowed file size is 1MB.",
     *     mimeTypesMessage = "Only the filetypes image are allowed."
     * )
     */
    private $featuredImage2File;

    /**
     * @var string
     *
     * @ORM\Column(name="featured_image2", type="string", length=127, nullable=true)
     */
    private $featuredImage2;
    private $featuredImage2FileTemp;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="car_info_title", type="text", nullable=true)
	 */
	private $carInfoTitle;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="car_info_features", type="text", nullable=true)
	 */
	private $carInfoFeatures;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="SweepstakesImages", mappedBy="sweepstakes", cascade={"persist","remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"position" = "ASC"})
     */
    private $images;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="winner_section_title", type="text", nullable=true)
	 */
	private $winnerSectionTitle;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="winner_section_sub_title", type="text", nullable=true)
	 */
	private $winnerSectionSubTitle;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="winner_section_text", type="text")
	 */
	private $winnerSectionText;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="winner_section_video", type="string", length=256, nullable=true)
	 */
	private $winnerSectionVideo;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="benefits_section_text", type="text", nullable=true)
	 */
	private $benefitsSectionText;

	/**
     * @Assert\File(
     *     maxSize = "1M",
     *     mimeTypes = {"image/jpeg", "image/png"},
     *     maxSizeMessage = "The maximum allowed file size is 1MB.",
     *     mimeTypesMessage = "Only the filetypes image are allowed."
     * )
     */
    private $benefitsSectionImageFile;

    /**
     * @var string
     *
     * @ORM\Column(name="benefits_section_image", type="string", length=127, nullable=true)
     * @Gedmo\UploadableFileName()
     */
    private $benefitsSectionImage;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="extra_section_title", type="text", nullable=true)
	 */
    private $extraSectionTitle;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="extra_section_text", type="text", nullable=true)
	 */
    private $extraSectionText;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="terms_and_condition", type="text", nullable=true)
	 */

	private $termsAndCondition;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="block", type="text", nullable=true)
	 */

	private $block;

    /**
     * @var string
     *
     * @ORM\Column(name="enter_caption_copy", type="text", nullable=true)
     */

    private $enterCaptionCopy;

    /**
     * @var string
     *
     * @ORM\Column(name="final_step_sub_copy", type="text", nullable=true)
     */

    private $finalStepSubCopy;

    /**
     * @var string
     *
     * @ORM\Column(name="contest_ended_header_copy", type="text", nullable=true)
     */

    private $contestEndedHeaderCopy;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Restomods\ListingBundle\Entity\SweepstakesUserEntries", mappedBy="sweepstakes")
     * @ORM\OrderBy({"createdAt" = "DESC"})
     */
    private $sweepstakesUserEntries;

    /**
     * @var int
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="layout", type="smallint", options={"default" = 1})
     */
    private $layout = 1;

    /**
     * @var boolean
     *
     * @ORM\Column(name="active", type="boolean", nullable=true)
     */
    private $active = false;

    public function __toString(){
        return $this->name ? $this->name : '';
    }

	public function __clone() {
		$this->setId(null);
		$this->setActive(false);
		$this->users = null;
		$this->images = null;
	}

    public function __construct() {
        $this->users = new ArrayCollection();
        $this->images = new ArrayCollection();
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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getCarName()
    {
        return $this->carName;
    }

    /**
     * @param string $carName
     */
    public function setCarName($carName)
    {
        $this->carName = $carName;
    }

    /**
     * @return string
     */
    public function getVideo()
    {
        return $this->video;
    }

    /**
     * @param string $video
     */
    public function setVideo($video)
    {
        $this->video = $video;
    }

    /**
     * @return string
     */
    public function getPrize()
    {
        return $this->prize;
    }

    /**
     * @param string $prize
     */
    public function setPrize($prize)
    {
        $this->prize = $prize;
    }

    /**
     * @return string
     */
    public function getSweepstakesLimit()
    {
        return $this->sweepstakesLimit;
    }

    /**
     * @param string $carName
     */
    public function setSweepstakesLimit($sweepstakesLimit)
    {
        $this->sweepstakesLimit = $sweepstakesLimit;
    }

    /**
     * @return string
     */
    public function getRequirements()
    {
        return $this->requirements;
    }

    /**
     * @param string $requirements
     */
    public function setRequirements($requirements)
    {
        $this->requirements = $requirements;
    }

    /**
     * @return mixed
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param mixed $startDate
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }

    /**
     * @return mixed
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @param mixed $endDate
     */
    public function setEndDate($endDate)
    {
        // $endDate->add(new \DateInterval('PT86399S'));
        $this->endDate = $endDate;
    }

    /**
     * @return mixed
     */
    public function getSelectWinnerDate()
    {
        return $this->selectWinnerDate;
    }

    /**
     * @param mixed $selectWinnerDate
     */
    public function setSelectWinnerDate($selectWinnerDate)
    {
        $this->selectWinnerDate = $selectWinnerDate;
    }

    /**
     * @return mixed
     */
    public function getContactWinnerDate()
    {
        return $this->contactWinnerDate;
    }

    /**
     * @param mixed $contactWinnerDate
     */
    public function setContactWinnerDate($contactWinnerDate)
    {
        $this->contactWinnerDate = $contactWinnerDate;
    }

    /**
     * @return mixed
     */
    public function getRevealWinnerDate()
    {
        return $this->revealWinnerDate;
    }

    /**
     * @param mixed $revealWinnerDate
     */
    public function setRevealWinnerDate($revealWinnerDate)
    {
        $this->revealWinnerDate = $revealWinnerDate;
    }

    /**
     * Set active
     *
     * @param boolean $active
     *
     * @return Sweepstakes
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

    /**
     * @return mixed
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param mixed $users
     */
    public function setUsers($users)
    {
        $this->users = $users;
    }

    /**
     * Add user
     *
     * @param \Application\Sonata\UserBundle\Entity\User $user
     *
     * @return Sweepstakes
     */
    public function addUser(\Application\Sonata\UserBundle\Entity\User $user)
    {
        $this->users[] = $user;

        return $this;
    }

    /**
     * Remove user
     *
     * @param \Application\Sonata\UserBundle\Entity\User $user
     */
    public function removeUser(\Application\Sonata\UserBundle\Entity\User $user)
    {
        $this->users->removeElement($user);
    }

    /**
     * Add sweepstakesUserEntry
     *
     * @param \Restomods\ListingBundle\Entity\SweepstakesUserEntries $sweepstakesUserEntry
     *
     * @return Sweepstakes
     */
    public function addSweepstakesUserEntry(\Restomods\ListingBundle\Entity\SweepstakesUserEntries $sweepstakesUserEntry)
    {
        $this->sweepstakesUserEntries[] = $sweepstakesUserEntry;

        return $this;
    }

    /**
     * Remove sweepstakesUserEntry
     *
     * @param \Restomods\ListingBundle\Entity\SweepstakesUserEntries $sweepstakesUserEntry
     */
    public function removeSweepstakesUserEntry(\Restomods\ListingBundle\Entity\SweepstakesUserEntries $sweepstakesUserEntry)
    {
        $this->sweepstakesUserEntries->removeElement($sweepstakesUserEntry);
    }

    /**
     * Get sweepstakesUserEntries
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSweepstakesUserEntries()
    {
        return $this->sweepstakesUserEntries;
    }

    /**
     * Add image
     *
     * @param SweepstakesImages $image
     *
     * @return Sweepstakes
     */
    public function addImage(SweepstakesImages $image) {
        $image->setSweepstakes($this);
        $this->images->add($image);
        return $this;
    }

    /**
     * Remove image
     *
     * @param SweepstakesImages $image
     */
    public function removeImage(SweepstakesImages $image) {

        $this->images->removeElement( $image );
    }

    /**
     * Get images
     *
     * @return ArrayCollection
     */
    public function getImages() {
        return $this->images;
    }

    /**
     * Set images
     *
     * @param ArrayCollection $images
     * @return Sweepstakes
     */
    public function setImages( $images ) {
        $this->images = new ArrayCollection();
        if ( count( $images ) ) {
            foreach ( $images as $image ) {
                $this->addImage( $image );
            }
        }
    }

	/**
	 * Set termsAndCondition
	 *
	 * @param string $termsAndCondition
	 *
	 * @return Sweepstakes
	 */
	public function setTermsAndCondition($termsAndCondition)
	{
		$this->termsAndCondition = $termsAndCondition;

		return $this;
	}

	/**
	 * Get termsAndCondition
	 *
	 * @return string
	 */
	public function getTermsAndCondition()
	{
		return $this->termsAndCondition;
	}

	/**
	 * Set block
	 *
	 * @param string $block
	 *
	 * @return Sweepstakes
	 */
	public function setBlock($block)
	{
		$this->block = $block;

		return $this;
	}

	/**
	 * Get block
	 *
	 * @return string
	 */
	public function getBlock()
	{
		return $this->block;
	}

    /**
     * Set contestEndedHeaderCopy
     *
     * @param string $contestEndedHeaderCopy
     *
     * @return Sweepstakes
     */
    public function setContestEndedHeaderCopy($contestEndedHeaderCopy)
    {
        $this->contestEndedHeaderCopy = $contestEndedHeaderCopy;

        return $this;
    }

    /**
     * Get contestEndedHeaderCopy
     *
     * @return string
     */
    public function getContestEndedHeaderCopy()
    {
        return $this->contestEndedHeaderCopy;
    }

    /**
     * @return mixed
     */
    public function getFeaturedImage1File()
    {
        return $this->featuredImage1File;
    }

    /**
     * @param mixed $featuredImage1File
     */
    public function setFeaturedImage1File(UploadedFile $featuredImage1File = null)
    {
        $this->featuredImage1File = $featuredImage1File;
        // check if we have an old image path
        if (isset($this->featuredImage1)) {
            // store the old name to delete after the update
            $this->featuredImage1FileTemp = $this->featuredImage1;
            $this->featuredImage1 = null;
        } else {
            $this->featuredImage1 = 'initial';
        }
    }

    /**
     * Set featuredImage1
     *
     * @param string $featuredImage1
     *
     * @return Sweepstakes
     */
    public function setFeaturedImage1($featuredImage1)
    {
        $this->featuredImage1 = $featuredImage1;

        return $this;
    }

    /**
     * Get featuredImage1
     *
     * @return string
     */
    public function getFeaturedImage1()
    {
        return $this->featuredImage1;
    }

    /**
     * Set featuredImage2
     *
     * @param string $featuredImage2
     *
     * @return Sweepstakes
     */
    public function setFeaturedImage2($featuredImage2)
    {
        $this->featuredImage2 = $featuredImage2;

        return $this;
    }

    /**
     * Get featuredImage2
     *
     * @return string
     */
    public function getFeaturedImage2()
    {
        return $this->featuredImage2;
    }

    /**
     * Sets file.
     * @param UploadedFile $featuredImage2File
     */
    public function setFeaturedImage2File(UploadedFile $featuredImage2File = null)
    {
        $this->featuredImage2File = $featuredImage2File;
        // check if we have an old image path
        if (isset($this->featuredImage2)) {
            // store the old name to delete after the update
            $this->featuredImage2FileTemp = $this->featuredImage2;
            $this->featuredImage2 = null;
        } else {
            $this->featuredImage2 = 'initial';
        }
    }

    /**
     * Get file.
     * @return UploadedFile
     */
    public function getFeaturedImage2File()
    {
        return $this->featuredImage2File;
    }

    /**
     * Set layout
     *
     * @param integer $layout
     *
     * @return Sweepstakes
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;

        return $this;
    }

    /**
     * Get layout
     *
     * @return integer
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * Set enterCaptionCopy
     *
     * @param string $enterCaptionCopy
     *
     * @return Sweepstakes
     */
    public function setEnterCaptionCopy($enterCaptionCopy)
    {
        $this->enterCaptionCopy = $enterCaptionCopy;

        return $this;
    }

    /**
     * Get enterCaptionCopy
     *
     * @return string
     */
    public function getEnterCaptionCopy()
    {
        return $this->enterCaptionCopy;
    }

    /**
     * Set finalStepSubCopy
     *
     * @param string $finalStepSubCopy
     *
     * @return Sweepstakes
     */
    public function setFinalStepSubCopy($finalStepSubCopy)
    {
        $this->finalStepSubCopy = $finalStepSubCopy;

        return $this;
    }

    /**
     * Get finalStepSubCopy
     *
     * @return string
     */
    public function getFinalStepSubCopy()
    {
        return $this->finalStepSubCopy;
    }

	/**
     * Set carInfoTitle
     *
     * @param string $carInfoTitle
     *
     * @return Sweepstakes
     */
    public function setCarInfoTitle($carInfoTitle)
    {
        $this->carInfoTitle = $carInfoTitle;

        return $this;
    }

	/**
     * Get carInfoTitle
     *
     * @return string
     */
    public function getCarInfoTitle()
    {
        return $this->carInfoTitle;
    }

	/**
     * Set carInfoFeatures
     *
     * @param string $carInfoFeatures
     *
     * @return Sweepstakes
     */
    public function setCarInfoFeatures($carInfoFeatures)
    {
        $this->carInfoFeatures = $carInfoFeatures;

        return $this;
    }

	/**
     * Get carInfoFeatures
     *
     * @return string
     */
    public function getCarInfoFeatures()
    {
        return $this->carInfoFeatures;
    }



	/**
     * Set winnerSectionTitle
     *
     * @param string $winnerSectionTitle
     *
     * @return Sweepstakes
     */
    public function setWinnerSectionTitle($winnerSectionTitle)
    {
        $this->winnerSectionTitle = $winnerSectionTitle;

        return $this;
    }

	/**
     * Get winnerSectionTitle
     *
     * @return string
     */
    public function getWinnerSectionTitle()
    {
        return $this->winnerSectionTitle;
    }

	/**
     * Set winnerSectionSubTitle
     *
     * @param string $winnerSectionSubTitle
     *
     * @return Sweepstakes
     */
    public function setWinnerSectionSubTitle($winnerSectionSubTitle)
    {
        $this->winnerSectionSubTitle = $winnerSectionSubTitle;

        return $this;
    }

	/**
     * Get winnerSectionSubTitle
     *
     * @return string
     */
    public function getWinnerSectionSubTitle()
    {
        return $this->winnerSectionSubTitle;
    }

	/**
     * Set winnerSectionText
     *
     * @param string $winnerSectionText
     *
     * @return Sweepstakes
     */
    public function setWinnerSectionText($winnerSectionText)
    {
        $this->winnerSectionText = $winnerSectionText;

        return $this;
    }

	/**
     * Get winnerSectionText
     *
     * @return string
     */
    public function getWinnerSectionText()
    {
        return $this->winnerSectionText;
    }

	/**
     * Set winnerSectionVideo
     *
     * @param string $winnerSectionVideo
     *
     * @return Sweepstakes
     */
    public function setWinnerSectionVideo($winnerSectionVideo)
    {
        $this->winnerSectionVideo = $winnerSectionVideo;

        return $this;
    }

	/**
     * Get winnerSectionVideo
     *
     * @return string
     */
    public function getWinnerSectionVideo()
    {
        return $this->winnerSectionVideo;
    }

	/**
     * Set $benefitsSectionText
     *
     * @param string $benefitsSectionText
     *
     * @return Sweepstakes
     */
    public function setBenefitsSectionText($benefitsSectionText)
    {
        $this->benefitsSectionText = $benefitsSectionText;

        return $this;
    }

	/**
     * Get benefitsSectionText
     *
     * @return string
     */
    public function getBenefitsSectionText()
    {
        return $this->benefitsSectionText;
    }

	/**
     * @return mixed
     */
    public function getBenefitsSectionImageFile()
    {
        return $this->benefitsSectionImageFile;
    }

    /**
     * @param mixed $featuredImage1File
     */
    public function setBenefitsSectionImageFile($benefitsSectionImageFile)
    {
        $this->benefitsSectionImageFile = $benefitsSectionImageFile;
    }

    /**
     * Set $benefitsSectionImage
     *
     * @param string $benefitsSectionImage
     *
     * @return Sweepstakes
     */
    public function setBenefitsSectionImage($benefitsSectionImage)
    {
        $this->benefitsSectionImage = $benefitsSectionImage;

        return $this;
    }

    /**
     * Get benefitsSectionImage
     *
     * @return string
     */
    public function getBenefitsSectionImage()
    {
        return $this->benefitsSectionImage;
    }

    /**
     * Set $extraSectionTitle
     *
     * @param string $extraSectionTitle
     *
     * @return Sweepstakes
     */
    public function setExtraSectionTitle($extraSectionTitle)
    {
        $this->extraSectionTitle = $extraSectionTitle;

        return $this;
    }

    /**
     * Get extraSectionTitle
     *
     * @return string
     */
    public function getExtraSectionTitle()
    {
        return $this->extraSectionTitle;
    }

	/**
     * Set $extraSectionText
     *
     * @param string $extraSectionText
     *
     * @return Sweepstakes
     */
    public function setExtraSectionText($extraSectionText)
    {
        $this->extraSectionText = $extraSectionText;

        return $this;
    }

    /**
     * Get extraSectionText
     *
     * @return string
     */
    public function getExtraSectionText()
    {
        return $this->extraSectionText;
    }

	/**
     * Set closingVideo
     *
     * @param string $closingVideo
     *
     * @return Sweepstakes
     */
    public function setClosingVideo($closingVideo)
    {
        $this->closingVideo = $closingVideo;

        return $this;
    }

    /**
     * Get closingVideo
     *
     * @return string
     */
    public function getClosingVideo()
    {
        return $this->closingVideo;
    }

    /************************************** Media Upload Directory **********************************/
    protected function getUploadRootDir()
    {
        return __DIR__.'/../../../../web/'.$this->getUploadDir();
    }

    protected function getUploadDir()
    {
        return 'uploads/media';
    }

	public function getFeaturedImage1AbsolutePath()
    {
        return null === $this->featuredImage1 ? null : $this->getUploadRootDir().'/'.$this->featuredImage1;
    }

    public function getFeaturedImage2AbsolutePath()
    {
        return null === $this->featuredImage2 ? null : $this->getUploadRootDir().'/'.$this->featuredImage2;
    }

    public function getFeaturedImage2WebPath()
    {
        return null === $this->featuredImage2 ? null : $this->getUploadDir().'/'.$this->featuredImage2;
    }

    /*******************************Doctrine LifecycleEvens ****************************************/

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload()
    {
		if ($this->featuredImage1File !== null) {
            $this->featuredImage1 = md5(uniqid()).'.'.$this->featuredImage1File->guessExtension();
        }
        if ($this->featuredImage2File !== null) {
            $this->featuredImage2 = md5(uniqid()).'.'.$this->featuredImage2File->guessExtension();
        }
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function postUpload()
    {
		if($this->featuredImage1File){
            $this->featuredImage1File->move($this->getUploadRootDir(), $this->featuredImage1);
            if (isset($this->featuredImage1FileTemp)) {
                @unlink($this->getUploadRootDir().'/'.$this->featuredImage1FileTemp);
                $this->featuredImage1FileTemp = null;
            }
            $this->featuredImage1File = null;
        }
        if($this->featuredImage2File){
            $this->featuredImage2File->move($this->getUploadRootDir(), $this->featuredImage2);
            if (isset($this->featuredImage2FileTemp)) {
                @unlink($this->getUploadRootDir().'/'.$this->featuredImage2FileTemp);
                $this->featuredImage2FileTemp = null;
            }
            $this->featuredImage2File = null;
        }

        return;
    }

    /**
     * @ORM\PostRemove()
     */
    public function removeUpload()
    {
		if ($featuredImage1File = $this->getFeaturedImage1AbsolutePath()) {
            @unlink($featuredImage1File);
        }
        if ($featuredImage2File = $this->getFeaturedImage2AbsolutePath()) {
            @unlink($featuredImage2File);
        }
    }

}
