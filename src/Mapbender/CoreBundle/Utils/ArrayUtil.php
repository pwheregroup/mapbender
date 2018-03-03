<?php
namespace Mapbender\CoreBundle\Utils;

/**
 * Description of ArrayUtil
 *
 * @author Paul Schmidt
 */
class ArrayUtil
{
    /**
     * Is array associative
     *
     * @param $array
     * @return bool
     */
    public static function isAssoc($array)
    {
        foreach (array_keys($array) as $key) {
            if (!is_int($key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get value from array
     *
     * @param array $list
     * @param null  $value
     * @param int   $default
     * @return mixed|null
     */
    public static function getValueFromArray(array $list, $value = null, $default = 0)
    {
        if (count($list) > 0) {
            $default = is_int($default) && $default < count($list) ? $default : 0;
            if (!self::isAssoc($list)) {
                return $value && in_array($value, $list) ? $value : $list[$default];
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * Check if array has a key and return the value, other way set new one and return it.
     *
     * @deprecated THIS MODIFIES THE ARRAY BY WRITING THE KEY INTO THE KEY NOT THE VALUE YOU HAVE BEEN WARNED
     * @internal
     *
     * @param array $arr array
     * @param string $key array key to check for existens
     * @param null  $value default value if key doesn't exists
     * @return mixed new value
     */
    public static function hasSet(array &$arr, $key, $value = null){
        if(isset($arr[$key])){
            return $arr[$key];
        }else{
            $arr[$key] = $key;
            return $value;
        }
    }

    /**
     * Extract and return the value (or $default if missing) with given $key from given array.
     *
     * @param array $arr
     * @param string|integer $key
     * @param mixed $default
     * @return mixed
     */
    public static function getDefault(array $arr, $key, $default=null)
    {
        if (array_key_exists($key, $arr)) {
            return $arr[$key];
        } else {
            return $default;
        }
    }

    /**
     * Extract and return the value (or $default if missing) with given $key from given array. Keys are compared
     * in case-insensitive fashion.
     *
     * @param array $arr
     * @param string|integer $key
     * @param mixed $default
     * @return mixed
     */
    public static function getDefaultCaseInsensitive(array $arr, $key, $default=null)
    {
        // make an equivalent array with all keys lower-cased, then look up $key (also lower-cased) inside it
        // NOTE: if multiple keys exist in the input array that differ only in case, they will fold to a single mapped
        //       value post-strtolower. Due to array_combine behaviour, the value mapped to the LAST such input key
        //       will be used.
        // @todo: evaluate if this is a problem / if we require first-key behavior
        //        (solutions: A. replace getDefault delegation with loop
        //                    B. array_reverse both keys and values before array_combine)
        $lcKeys = array_map('strtolower', array_keys($arr));
        $arrWithLcKeys = array_combine($lcKeys, array_values($arr));
        return static::getDefault($arrWithLcKeys, strtolower($key), $default);
    }

    /**
     * Legacy ALMOST-equivalent to array_replace_recursive($a, $b), but
     * 1) result key ordering follows $b first, not $a
     * 2) null values from $b will not integrate into result, $a values will be kept
     *
     * If neither quirk matters to you, just call array_replace_recursive directly.
     *
     * Used exclusively by (also legacy) Element::mergeArrays
     *
     * @param mixed[] $a
     * @param mixed[] $b
     * @return mixed[]
     */
    public static function mergeHashesRecursive($a, $b)
    {
        $result = array();
        foreach ($b as $key => $value) {
            if (is_array($value)) {
                if (isset($a[$key])) {
                    $result[$key] = static::mergeHashesRecursive($a[$key], $b[$key]);
                } else {
                    $result[$key] = $b[$key];
                }
            } else {
                $result[$key] = $value;
            }
        }
        if (is_array($a)) {
            foreach ($a as $key => $value) {
                if (!isset($result[$key])) {
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }

    /**
     * Legacy emulation shim for Element::mergeArrays that enforces the passing of a "$result" into
     * the merge operation via its signature. All known callers of Element::mergeArrays use an
     * empty array though.
     * We detect that and short-circuit to mergeHashesRecursive.
     * Should $target ever be non-empty, we perform a three-way merge, $main into $default, then
     * the result of that into $target.
     *
     * NOTE: if you do pass a target: use only 1D. Subarrays in $target are NOT handled recursively.
     *       They are either copied unmodified or replaced entirely at the top level.
     *
     * Same quirks apply as mergeHashesRecursive:
     * 1) result key ordering follows $b first, not $a
     * 2) null values from $b will not integrate into resut, $a values will be kept
     *
     * @param mixed[] $target
     * @param mixed[] $a
     * @param mixed[] $b
     * @return mixed[]
     */
    public static function mergeHashesRecursiveInto($target, $a, $b)
    {
        if ($target) {
            $firstMergeResult = static::mergeHashesRecursive($a, $b);
            return $firstMergeResult + $target;
        } else {
            return static::mergeHashesRecursive($a, $b);
        }
    }

}
