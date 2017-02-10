<?php
namespace Mapbender\WmcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Entity\Contact;
use Mapbender\CoreBundle\Entity\State;
use Mapbender\WmsBundle\Component\OnlineResource;
use Mapbender\WmsBundle\Component\LegendUrl;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A Wmc entity presents an OGC WMC.
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_wmc_wmc")
 * ORM\DiscriminatorMap({"mb_wmc" = "Wmc"})
 */
class Wmc
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $version The wmc version
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    protected $version = "1.1.0";

    /**
     * @var string $wmcid a wmc id
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $wmcid;

    /**
     * @ORM\OneToOne(targetEntity="Mapbender\CoreBundle\Entity\State", cascade={"persist","remove"})
     * @ORM\JoinColumn(name="state", referencedColumnName="id")
     * @var State
     */
    protected $state;

    /**
     * @var array $keywords The keywords of the wmc
     * @ORM\Column(type="array",nullable=true)
     */
    protected $keywords = array();

    /**
     * @var string $abstract The wmc description
     * @ORM\Column(type="text", nullable=true)
     */
    protected $abstract;

    /**
     * @var LegendUrl Logo URL
     * @ORM\Column(type="object", nullable=true)
     */
    public $logourl;

    /**
     * @var OnlineResource Description URL
     * @ORM\Column(type="object", nullable=true)
     */
    public $descriptionurl;

    /**
     * @var string $screenshotPath The wmc description
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $screenshotPath;

    /**
     * @var File
     * @Assert\File(maxSize="6000000")
     */
    private $screenshot;

    /**
     * @var Contact A contact.
     * @ORM\OneToOne(targetEntity="Mapbender\CoreBundle\Entity\Contact", cascade={"persist","remove"})
     */
    protected $contact;

    /**
     * @var File document
     * @Assert\File(maxSize="6000000")
     */
    private $xml;

    /**
     * @var boolean public
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $public = false;

    /**
     * Set id
     *
     * @param integer $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param State $state
     * @return $this
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * @return State
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param array $keywords
     * @return $this
     */
    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;
        return $this;
    }

    /**
     * @return array
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * Set abstract
     *
     * @param string $abstract
     * @return $this
     */
    public function setAbstract($abstract)
    {
        $this->abstract = $abstract;
        return $this;
    }

    /**
     * Get abstract
     *
     * @return string
     */
    public function getAbstract()
    {
        return $this->abstract;
    }

    /**
     * Set logourl
     *
     * @param LegendUrl $logourl
     * @return Wmc
     */
    public function setLogourl(LegendUrl $logourl)
    {
        $this->logourl = $logourl;
        return $this;
    }

    /**
     * Get logourl
     *
     * @return LegendUrl
     */
    public function getLogourl()
    {
        return $this->logourl;
    }

    /**
     * Set descriptionurl
     *
     * @param OnlineResource $descriptionurl
     * @return Wmc
     */
    public function setDescriptionurl(OnlineResource $descriptionurl)
    {
        $this->descriptionurl = $descriptionurl;
        return $this;
    }

    /**
     * Get descriptionurl
     *
     * @return OnlineResource
     */
    public function getDescriptionurl()
    {
        return $this->descriptionurl;
    }

    /**
     * Set screen shot path
     *
     * @param string $screenshotPath
     * @return $this
     */
    public function setScreenshotPath($screenshotPath)
    {
        $this->screenshotPath = $screenshotPath;
        return $this;
    }

    /**
     * Get screenshotPath
     *
     * @return string
     */
    public function getScreenshotPath()
    {
        return $this->screenshotPath;
    }

    /**
     * @param string $screenshot
     * @return $this
     */
    public function setScreenshot($screenshot)
    {
        $this->screenshot = $screenshot;
        return $this;
    }

    /**
     * Get version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param string $version
     * @return $this
     */
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * Get version
     *
     * @return string
     */
    public function getWmcid()
    {
        return $this->wmcid;
    }

    /**
     * @param string $wmcid
     * @return $this
     */
    public function setWmcid($wmcid)
    {
        $this->wmcid = $wmcid;
        return $this;
    }

    /**
     * Set contact
     *
     * @param string $contact
     * @return $this
     */
    public function setContact($contact)
    {
        $this->contact = $contact;
        return $this;
    }

    /**
     * Get contact
     *
     * @return string
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @param string $xml
     * @return $this
     */
    public function setXml($xml)
    {
        $this->xml = $xml;
        return $this;
    }

    /**
     * Get xml
     *
     * @return string
     */
    public function getXml()
    {
        return $this->xml;
    }

    /**
     * Get screenshot
     *
     * @return string
     */
    public function getScreenshot()
    {
        return $this->screenshot;
    }

    /**
     * Set public
     *
     * @param boolean $public
     * @return $this
     */
    public function setPublic($public)
    {
        $this->public = $public;
        return $this;
    }

    /**
     * Get public
     *
     * @param boolean
     * @return bool
     */
    public function getPublic()
    {
        return $this->public;
    }

    /**
     *
     *
     * @param State|null          $state
     * @param LegendUrl|null      $logoUrl
     * @param OnlineResource|null $descriptionUrl
     * @return Wmc
     */
    public static function create(
        $state = null,
        $logoUrl = null,
        $descriptionUrl = null)
    {
        $wmc            = new Wmc();
        $descriptionUrl = $descriptionUrl ? $descriptionUrl : OnlineResource::create();
        $logoUrl        = $logoUrl ? $logoUrl : LegendUrl::create();

        $wmc->setState($state ? $state : new State());

        if (!$logoUrl) {
            $wmc->setLogourl($logoUrl);
        }

        if (!$descriptionUrl) {
            $wmc->setDescriptionurl($descriptionUrl);
        }

        return $wmc;
    }

}
