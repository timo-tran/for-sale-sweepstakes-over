<?php

namespace Restomods\ListingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Settings
 *
 * @ORM\Table(name="settings")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Uploadable(path="uploads/media", filenameGenerator="SHA1")
 */
class Settings
{
	static $referralLandingOptions = array('sonata_user_profile_show' => 'Dashboard','restomods_listing_index' => 'Listing','restomods_sweepstakes' => 'Sweepstakes');

    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string", length=7)
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="site_name", type="string", length=127, nullable=true)
     */
    private $siteName;

    /**
     * @var string
     *
     * @ORM\Column(name="landing_header_script", type="text", nullable=true)
     */
    private $landingHeaderScript;

    /**
     * @var string
     *
     * @ORM\Column(name="landing_noscript", type="text", nullable=true)
     */
    private $landingNoScript;

	/**
     * @var string
     *
     * @ORM\Column(name="landing_footer_script", type="text", nullable=true)
     */
    private $landingFooterScript;

    /**
     * @var string
     *
     * @ORM\Column(name="referral_landing_page", type="string", length=31, nullable=true)
     */
    private $referralLandingPage;

    /**
     * @var string
     *
     * @ORM\Column(name="sweepstakes_copy", type="text", nullable=true)
     */
    private $sweepstakesCopy;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="referral_url_copy", type="text", nullable=true)
	 */
	private $referralUrlCopy;

    /**
     * @var string
     *
     * @ORM\Column(name="my_listings_copy", type="text", nullable=true)
     */
    private $myListingsCopy;

    /**
     * @var string
     *
     * @ORM\Column(name="no_active_sweepstakes_copy", type="text", nullable=true)
     */
    private $noActiveSweepstakesCopy;

    /**
     * @Assert\File(
     *     maxSize = "1M",
     *     mimeTypes = {"image/jpeg", "image/png"},
     *     maxSizeMessage = "The maxmimum allowed file size is 1MB.",
     *     mimeTypesMessage = "Only the filetypes image are allowed."
     * )
     */
    private $sweepstakesLogoFile;

    /**
     * @var string
     *
     * @ORM\Column(name="sweepstakes_logo", type="string", length=127, nullable=true)
     * @Gedmo\UploadableFileName
     */
    private $sweepstakesLogo;


    private $entriesApi;

    /**
     * @var string
     *
     * @ORM\Column(name="entries_api_key", type="string", length=127, nullable=true)
     */
    private $entriesApiKey;

    /**
     * @var string
     *
     * @ORM\Column(name="join_api", type="string", length=255, nullable=true)
     */
    private $joinApi;

    /**
     * @var string
     *
     * @ORM\Column(name="join_api_username", type="string", length=127, nullable=true)
     */
    private $joinApiUsername;

    /**
     * @var string
     *
     * @ORM\Column(name="join_api_password", type="string", length=127, nullable=true)
     */
    private $joinApiPassword;

    /**
     * @var string
     *
     * @ORM\Column(name="shopify_domain", type="string", length=255, nullable=true)
     */
    private $shopifyDomain;

    /**
     * @var string
     *
     * @ORM\Column(name="shopify_api_key", type="string", length=127, nullable=true)
     */
    private $shopifyApiKey;

    /**
     * @var string
     *
     * @ORM\Column(name="shopify_password", type="string", length=127, nullable=true)
     */
    private $shopifyPassword;

    public function __toString()
    {
        return 'Settings';
    }

    /**
     * Set id
     *
     * @param string $id
     *
     * @return Settings
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set siteName
     *
     * @param string $siteName
     *
     * @return Settings
     */
    public function setSiteName($siteName)
    {
        $this->siteName = $siteName;

        return $this;
    }

    /**
     * Get siteName
     *
     * @return string
     */
    public function getSiteName()
    {
        return $this->siteName;
    }

    /**
     * Set landingHeaderScript
     *
     * @param string $landingHeaderScript
     *
     * @return Settings
     */
    public function setLandingHeaderScript($landingHeaderScript)
    {
        $this->landingHeaderScript = $landingHeaderScript;

        return $this;
    }

    /**
     * Get landingHeaderScript
     *
     * @return string
     */
    public function getLandingHeaderScript()
    {
        return $this->landingHeaderScript;
    }

    /**
     * Set landingNoScript
     *
     * @param string $landingNoScript
     *
     * @return Settings
     */
    public function setLandingNoScript($landingNoScript)
    {
        $this->landingNoScript = $landingNoScript;

        return $this;
    }

    /**
     * Get landingNoScript
     *
     * @return string
     */
    public function getLandingNoScript()
    {
        return $this->landingNoScript;
    }

    /**
     * Set landingFooterScript
     *
     * @param string $landingFooterScript
     *
     * @return Settings
     */
    public function setLandingFooterScript($landingFooterScript)
    {
        $this->landingFooterScript = $landingFooterScript;

        return $this;
    }

    /**
     * Get landingFooterScript
     *
     * @return string
     */
    public function getLandingFooterScript()
    {
        return $this->landingFooterScript;
    }

    /**
     * Set referralLandingPage
     *
     * @param string $referralLandingPage
     *
     * @return Settings
     */
    public function setReferralLandingPage($referralLandingPage)
    {
        $this->referralLandingPage = $referralLandingPage;

        return $this;
    }

    /**
     * Get referralLandingPage
     *
     * @return string
     */
    public function getReferralLandingPage()
    {
        return $this->referralLandingPage;
    }

    /**
     * Set sweepstakesCopy
     *
     * @param string $sweepstakesCopy
     *
     * @return Settings
     */
    public function setSweepstakesCopy($sweepstakesCopy)
    {
        $this->sweepstakesCopy = $sweepstakesCopy;

        return $this;
    }

    /**
     * Get sweepstakesCopy
     *
     * @return string
     */
    public function getSweepstakesCopy()
    {
        return $this->sweepstakesCopy;
    }

    /**
     * Set referralUrlCopy
     *
     * @param string $referralUrlCopy
     *
     * @return Settings
     */
    public function setReferralUrlCopy($referralUrlCopy)
    {
        $this->referralUrlCopy = $referralUrlCopy;

        return $this;
    }

    /**
     * Get referralUrlCopy
     *
     * @return string
     */
    public function getReferralUrlCopy()
    {
        return $this->referralUrlCopy;
    }

    /**
     * Set myListingsCopy
     *
     * @param string $myListingsCopy
     *
     * @return Settings
     */
    public function setMyListingsCopy($myListingsCopy)
    {
        $this->myListingsCopy = $myListingsCopy;

        return $this;
    }

    /**
     * Get myListingsCopy
     *
     * @return string
     */
    public function getMyListingsCopy()
    {
        return $this->myListingsCopy;
    }

    /**
     * Set noActiveSweepstakesCopy
     *
     * @param string $noActiveSweepstakesCopy
     *
     * @return Settings
     */
    public function setNoActiveSweepstakesCopy($noActiveSweepstakesCopy)
    {
        $this->noActiveSweepstakesCopy = $noActiveSweepstakesCopy;

        return $this;
    }

    /**
     * Get noActiveSweepstakesCopy
     *
     * @return string
     */
    public function getNoActiveSweepstakesCopy()
    {
        return $this->noActiveSweepstakesCopy;
    }

    /**
     * Set sweepstakesLogo
     *
     * @param string $sweepstakesLogo
     *
     * @return Settings
     */
    public function setSweepstakesLogo($sweepstakesLogo)
    {
        $this->sweepstakesLogo = $sweepstakesLogo;

        return $this;
    }

    /**
     * Get sweepstakesLogo
     *
     * @return string
     */
    public function getSweepstakesLogo()
    {
        return $this->sweepstakesLogo;
    }

    /**
     * @return mixed
     */
    public function getSweepstakesLogoFile()
    {
        return $this->sweepstakesLogoFile;
    }

    /**
     * @param mixed $sweepstakesLogoFile
     */
    public function setSweepstakesLogoFile($sweepstakesLogoFile)
    {
        $this->sweepstakesLogoFile = $sweepstakesLogoFile;
    }

	public function getEntriesApi() {
		return "";
	}

	public function setEntriesApi($entriesApi) {

	}

	public function getEntriesApiKey() {
		return $this->entriesApiKey;
	}

	public function setEntriesApiKey($entriesApiKey) {
		$this->entriesApiKey = $entriesApiKey;
		return $this;
	}

	public function getJoinApi() {
		return $this->joinApi;
	}

	public function setJoinApi($joinApi) {
		$this->joinApi = $joinApi;
		return $this;
	}

	public function getJoinApiUsername() {
		return $this->joinApiUsername;
	}

	public function setJoinApiUsername($joinApiUsername) {
		$this->joinApiUsername = $joinApiUsername;
		return $this;
	}

	public function getJoinApiPassword() {
		return $this->joinApiPassword;
	}

	public function setJoinApiPassword($joinApiPassword) {
		$this->joinApiPassword = $joinApiPassword;
		return $this;
	}

	public function getShopifyDomain() {
		return $this->shopifyDomain;
	}

	public function setShopifyDomain($shopifyDomain) {
		$this->shopifyDomain = $shopifyDomain;
		return $this;
	}

	public function getShopifyApiKey() {
		return $this->shopifyApiKey;
	}

	public function setShopifyApiKey($shopifyApiKey) {
		$this->shopifyApiKey = $shopifyApiKey;
		return $this;
	}

	public function getShopifyPassword() {
		return $this->shopifyPassword;
	}

	public function setShopifyPassword($shopifyPassword) {
		$this->shopifyPassword = $shopifyPassword;
		return $this;
	}
}
