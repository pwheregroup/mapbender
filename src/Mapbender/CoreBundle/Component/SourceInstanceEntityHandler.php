<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\WmsBundle\Component\Dimension;
use Mapbender\WmsBundle\Component\InstanceTunnel;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Description of SourceInstanceEntityHandler
 *
 * @author Paul Schmidt
 *
 * @property SourceInstance $entity
 */
abstract class SourceInstanceEntityHandler extends EntityHandler
{
    /** @var InstanceTunnel */
    protected $tunnel;

    /**
     * @param array $configuration
     * @return SourceInstance
     * @internal param SourceInstance $instance
     */
    abstract public function setParameters(array $configuration = array());

    /**
     * Copies attributes from bound instance's source to the bound instance
     * @deprecated
     * If the source is already bound to the instance....
     */
    abstract public function create();
    
    /**
     * Update instance parameters
     */
    abstract public function update();
    
    /**
     * Returns the instance configuration with signed urls or null if an instance configuration isn't valid.
     * @return array instance configuration or null
     */
    abstract public function getConfiguration(Signer $signer);
    
    /**
     * Generates an instance configuration
     */
    abstract public function generateConfiguration();
    
    /**
     * Merges a fiving dimension with an existing.
     * @param Dimension $dimension a diemsion
     */
    abstract public function mergeDimension($dimension);
    
    /**
     * Returns an array with sensitive vendor specific parameters
     */
    abstract public function getSensitiveVendorSpecific();

    /**
     * @return InstanceTunnel
     */
    protected function getTunnel()
    {
        if (!$this->tunnel) {
            /** @var UrlGeneratorInterface $router */
            $router = $this->container->get('router');
            $this->tunnel = new InstanceTunnel($router, $this->entity);
        }
        return $this->tunnel;
    }
}
