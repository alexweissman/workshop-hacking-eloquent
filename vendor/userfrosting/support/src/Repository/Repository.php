<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/support
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/LICENSE.md (MIT License)
 */
namespace UserFrosting\Support\Repository;

use Illuminate\Config\Repository as IlluminateRepository;
use UserFrosting\Support\Util\Util;

/**
 * Repository Class
 *
 * Represents an extendable repository of key->value mappings.
 *
 * @author Alexander Weissman (https://alexanderweissman.com)
 */
class Repository extends IlluminateRepository
{
    /**
     * Recursively merge values (scalar or array) into this repository.
     *
     * If no key is specified, the items will be merged in starting from the top level of the array.
     * If a key IS specified, items will be merged into that key.
     * Nested keys may be specified using dot syntax.
     * @param string|null $key
     * @param mixed $items
     */
    public function mergeItems($key = null, $items)
    {
        $targetValues = array_get($this->items, $key);

        if (is_array($targetValues)) {
            $modifiedValues = array_replace_recursive($targetValues, $items);
        } else {
            $modifiedValues = $items;
        }

        array_set($this->items, $key, $modifiedValues);
        return $this;
    }

    /**
     * Get the specified configuration value, recursively removing all null values.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getDefined($key = null)
    {
        $result = $this->get($key);
        if (!is_array($result)) {
            return $result;
        }

        return Util::arrayFilterRecursive($result, function ($value) {
            return !is_null($value);
        });
    }
}
