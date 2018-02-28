<?php


namespace Mapbender\CoreBundle\Component\Base;


/**
 * Workaround for PHP 5 not allowing abstract static methods.
 * Only used to complete the abstract ConfigurationBase class
 */
interface ConfigurationBaseInterface
{
    /**
     * Returns the default population for @see ConfigurationBase::fromArray $options. This is mostly equivalent to constructor / attribute
     * defaults, but some array entries may have different naming from actual class attributes (underscores instead
     * of camelCase etc).
     *
     * Implementing classes must make sure this array lists the complete set of populable attributes. The keys
     * in the returned array are used for $strict validation.
     *
     * @return mixed[]
     */
    public static function defaults();
}
