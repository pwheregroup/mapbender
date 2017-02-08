<?php
namespace Mapbender\WmsBundle\Component;

/**
 * LegendUrl class.
 *
 * @author Paul Schmidt
 */
class LegendUrl
{
    /**
     * ORM\Column(type="string", nullable=true)
     */
    protected $onlineResource;

    /**
     * ORM\Column(type="integer", nullable=true)
     */
    protected $width;

    /**
     * ORM\Column(type="integer", nullable=true)
     */
    protected $height;

    /**
     *
     * @param OnlineResource $onlineResource onl
     * @param int            $width
     * @param int            $height
     */
    public function __construct($onlineResource = null, $width = null, $height = null)
    {
        $this->onlineResource = $onlineResource;
        $this->width          = $width;
        $this->height         = $height;
    }

    /**
     * Set onlineResource
     *
     * @param OnlineResource $onlineResource
     * @return LegendUrl
     */
    public function setOnlineResource(OnlineResource $onlineResource)
    {
        $this->onlineResource = $onlineResource;

        return $this;
    }

    /**
     * Get onlineResource
     *
     * @return OnlineResource
     */
    public function getOnlineResource()
    {
        return $this->onlineResource;
    }

    /**
     * Set width
     *
     * @param integer $width
     * @return LegendUrl
     */
    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Get width
     *
     * @return integer
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set height
     *
     * @param integer $height
     * @return LegendUrl
     */
    public function setHeight($height)
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Get height
     *
     * @return integer
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param int|null            $width  Width
     * @param int|null            $height Height
     * @param OnlineResource|null $onlineResource
     * @return LegendUrl|null
     */
    public static function create(
        $width = null,
        $height = null,
        $onlineResource = null)
    {
        $lurl           = null;
        $onlineResource = $onlineResource === null ? OnlineResource::create() : $onlineResource;

        if ($onlineResource !== null) {
            $lurl = new LegendUrl();
            $lurl->setWidth($width);
            $lurl->setHeight($height);
            $lurl->setOnlineResource($onlineResource);
        }

        return $lurl;
    }
}