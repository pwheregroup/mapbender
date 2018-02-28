<?php


namespace Mapbender\CoreBundle\Component\Base;


/**
 * Base class for simple "plain old data" configuration carriers that support
 * * conversion TO array
 * * instantiation and prepopulation FROM array
 *
 * Going back and forth should be idempotent. fromArray has a (by default enabled) strict mode that
 * enforces that only known value keys can appear in the input array.
 */
abstract class ConfigurationBase implements ConfigurationBaseInterface
{
    /**
     * Convert the instance to an array representation.
     *
     * @return mixed[]
     */
    abstract public function toArray();

    /**
     * Factory method. Instantiates class and prepopulates it with values from given $options.
     *
     * @param mixed[] $options
     * @param boolean $strict to throw an Exception if unrecognized options were passed
     * @return static
     * @throws \InvalidArgumentException if $options is not an array
     * @throws \RuntimeException if unrecognized keys are found in $options
     */
    public static function fromArray($options, $strict = true)
    {
        if (!is_array($options)) {
            if ($strict) {
                throw new \InvalidArgumentException('Options must be an array, is: ' . print_r($options, true));
            } else {
                $options = array();
            }
        }
        $instance = new static();
        $instance->populateAttributes($options, static::defaults(), $strict);
        return $instance;
    }

    /**
     * Perform mass attribute population from array via magic attribute access.
     *
     * @param mixed[] $options
     * @param mixed[] $defaults
     * @param boolean $strict
     * @throws \RuntimeException if unsupported value keys are found and $strict == true
     */
    protected function populateAttributes($options, $defaults, $strict)
    {
        $mergedOptions = array_replace($defaults, $options);
        if ($strict) {
            $validateAgainst = $defaults ?: $this->defaults();
            $badKeys = array_keys(array_diff_key($options, $validateAgainst));
            if ($badKeys) {
                $message = "Unsupported keys in options: " . implode(", ", $badKeys);
                $message .= "; have: " . implode(", ", array_keys($validateAgainst));
                throw new \RuntimeException($message);
            }
        }
        foreach ($mergedOptions as $key => $value) {
            $this->{$key} = $value;
        }
    }
}
