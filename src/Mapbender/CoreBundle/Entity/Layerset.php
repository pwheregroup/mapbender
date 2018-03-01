<?php
namespace Mapbender\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Layerset configuration entity
 *
 * @author Christian Wygoda
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_core_layerset", uniqueConstraints={@UniqueConstraint(name="layerset_idx", columns={"application_id", "title"})})
 * @UniqueEntity(fields={"application", "title"}, message ="Duplicate entry for key 'title'.")
 */
class Layerset
{

    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $title The layerset title
     * @ORM\Column(type="string", length=128)
     * @Assert\NotBlank()
     */
    protected $title;

    /**
     * @var Application The configuration entity for the application
     * @ORM\ManyToOne(targetEntity="Application", inversedBy="layersets")
     */
    protected $application;

    /**
     * @ORM\OneToMany(targetEntity="SourceInstance", mappedBy="layerset", cascade={"remove","persist"})
     * @ORM\JoinColumn(name="instances", referencedColumnName="id")
     * @ORM\OrderBy({"weight" = "asc"})
     */
    protected $instances;

    /** @var \Mapbender\WmsBundle\Entity\WmsInstance[]|SourceInstance[]  */
    public $layerObjects;

    /**
     * Layerset constructor.
     */
    public function __construct()
    {
        $this->instances = new ArrayCollection();
    }

    /**
     * Set id. DANGER
     *
     * Set the entity id. DO NOT USE THIS unless you know what you're doing.
     * Probably the only place where this should be used is in the
     * ApplicationYAMLMapper class. Maybe this could be done using a proxy
     * class instead?
     *
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        if (null !== $id) {
            $this->id = $id;
        }

        return $this;
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
     * Set title
     *
     * @param string $title
     * @return $this
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
     * Set application
     *
     * @param Application $application
     * @return $this
     */
    public function setApplication(Application $application)
    {
        $this->application = $application;

        return $this;
    }

    /**
     * Get application
     *
     * @return Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * Add SourceInstance
     *
     * @param SourceInstance $instance
     */
    public function addInstance(SourceInstance $instance)
    {
        $this->instances->add($instance);
    }

    public function addInstanceAtOffset(SourceInstance $instance, $offset=-1)
    {
        $nInstances = $this->countInstances();
        $instance->setWeight($nInstances);
        $this->addInstance($instance);
        $instance->setLayerset($this);
        if ($offset >= 0) {
            $this->moveInstance($instance, $offset);
        }
    }

    /**
     * Set instances
     *
     * @param  Collection $instances Collection of the SourceInstances
     * @return Layerset
     */
    public function setInstances($instances)
    {
        $this->instances = $instances;

        return $this;
    }

    /**
     * Get instances
     *
     * @return SourceInstance[]|Collection
     */
    public function getInstances()
    {
        return $this->instances;
    }

    /**
     * @return string Layerset ID
     */
    public function __toString()
    {
        return (string) $this->getId();
    }

    /**
     * Move the given SourceInstance from its current offset to the given $targetOffset.
     * The instance must be part of this Layerset.
     *
     * @param SourceInstance $instance
     * @param int $targetOffset use -1 for end of list
     * @return boolean true if any weights were changed (to guide persist / flush optimizations)
     * @throws \LogicException if instance not part of Layerset
     */
    public function moveInstance(SourceInstance $instance, $targetOffset)
    {
        $instanceId = $instance->getId();
        if ($instance->getLayerset()->getId() !== $this->getId()) {
            throw new \LogicException("Instance with id " . print_r($instance->getId(), true) . " not part of Layerset");
        }
        $oldOffset = $instance->getWeight();
        if ($targetOffset < 0) {
            $nInstances = $this->countInstances();
            if ($oldOffset < 0 || $oldOffset === null) {
                $instance->setWeight($nInstances);
                return true;
            } else {
                $targetOffset = $nInstances;
            }
        }

        if ($oldOffset == $targetOffset) {
            return false;
        }
        foreach ($this->instances as $otherInstance) {
            $otherId = $otherInstance->getId();
            $otherWeight = $otherInstance->getWeight();
            if ($otherId == $instanceId) {
                $instance->setWeight($targetOffset);
            } elseif ($targetOffset < $oldOffset && $otherWeight >= $targetOffset && $otherWeight < $oldOffset) {
                // instance offset is decreasing
                // other instance was in the gap between target offset (low) and old offset (high)
                // => other instance offset increases by one to make way
                $otherInstance->setWeight($otherWeight + 1);
            } elseif ($targetOffset > $oldOffset && $otherWeight <= $targetOffset && $otherWeight > $oldOffset) {
                // instance offset is increasing
                // other instance was in the gap between target offset (high) and old offset (low)
                // => other instance offset decreases by one to make way
                $otherInstance->setWeight($otherWeight - 1);
            }
            // NOTE: in all other cases, nothing needs to happen
            //       instances outside of the range between target and old offset do not need to move
        }
        return true;
    }

    public function countInstances()
    {
        if ($this->instances instanceof Collection) {
            return $this->instances->count();
        } else {
            // assume array; the constructor ensure a collection. If it's not an array either, we have bigger problems.
            return count($this->instances);
        }
    }

    /**
     * @param SourceInstance $instance
     * @return bool if any weights were changed (to guide persist / flush optimizations)
     */
    public function removeInstance(SourceInstance $instance)
    {
        $weightChanges = false;
        if ($this->instances->removeElement($instance)) {
            $oldWeight = $instance->getWeight();
            foreach ($this->instances as $otherInstance) {
                $otherWeight = $otherInstance->getWeight();
                if ($otherWeight > $oldWeight) {
                    $weightChanges = true;
                    $otherInstance->setWeight($otherWeight - 1);
                }
            }
        }
        return $weightChanges;
    }
}
