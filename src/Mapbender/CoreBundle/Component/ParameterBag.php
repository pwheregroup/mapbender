<?php
namespace Mapbender\CoreBundle\Component;

use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;

/**
 * Class ParameterBag
 *
 * @package Mapbender\CoreBundle\Component
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 */
class ParameterBag extends \Symfony\Component\DependencyInjection\ParameterBag\ParameterBag
{
    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        $name = strtolower($name);

        if (!array_key_exists($name, $this->parameters)) {

            if (strpos($name, ".")) {
                $names  = explode(".", $name);
                $result = $this->parameters;
                foreach ($names as $key) {
                    if (!array_key_exists($key, $result)) {
                        throw new ParameterNotFoundException($name);
                    }
                    $result = $result[ $key ];
                }

                return $result;
            }

            if (!$name) {
                throw new ParameterNotFoundException($name);
            }

            $alternatives = array();
            foreach ($this->parameters as $key => $parameterValue) {
                $lev = levenshtein($name, $key);
                if ($lev <= strlen($name) / 3 || false !== strpos($key, $name)) {
                    $alternatives[] = $key;
                }
            }

            throw new ParameterNotFoundException($name, null, null, null, $alternatives);
        }

        return $this->parameters[$name];
    }
}