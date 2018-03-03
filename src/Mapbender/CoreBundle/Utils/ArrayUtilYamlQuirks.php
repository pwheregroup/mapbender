<?php


namespace Mapbender\CoreBundle\Utils;

/**
 * Quirks-mode ArrayUtil extension to emulate ApplicationYAMLMapper legacy behavior
 */
class ArrayUtilYamlQuirks extends ArrayUtil
{
    /**
     * Recursive merging of element default configuration with config read from a Yaml
     * application definition.
     *
     * Bonus quirk:
     * 1) sub-arrays are never merged, but always fully replaced, even if the value from
     *    $b ("the Yaml") is of different type (including null!).
     *
     * @param mixed[] $a
     * @param mixed[] $b
     * @param bool $unused
     * @return mixed[]
     */
    public static function combineRecursive($a, $b, $unused = true)
    {
        // Quirk 1: force list replacement by passing non-default $replaceLists = true, always
        $result = parent::combineRecursive($a, $b, true);
        // Quirk 2: revert parent quirk, allow replacement of sub-array with null
        foreach (array_intersect_key($result, $b) as $key => $resultValue) {
            if ($resultValue !== $b[$key] && is_array($resultValue)) {
                $result[$key] = $b[$key];
            }
        }
        return $result;
    }
}
