<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @package   userfrosting/support
 * @link      https://github.com/userfrosting/support
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/LICENSE.md (MIT License)
 */
namespace UserFrosting\Support\Util;

/**
 * Util Class
 *
 * Static utility functions for UserFrosting.
 *
 * @author Alex Weissman (https://alexanderweissman.com)
 * @author RocketTheme (http://www.rockettheme.com/)
 */
class Util
{
    /**
     * Returns the canonicalized URI on success. The resulting path will have no '/./' or '/../' components.
     * Trailing delimiter `/` is kept.
     *
     * By default (if $throwException parameter is not set to true) returns false on failure.
     *
     * @see https://github.com/rockettheme/toolbox/blob/develop/ResourceLocator/src/UniformResourceLocator.php
     * @param string $uri
     * @param bool $throwException
     * @param bool $splitStream
     * @return string|array|bool
     * @throws \BadMethodCallException
     */
    static public function normalizePath($uri, $throwException = false, $splitStream = false)
    {
        if (!is_string($uri)) {
            if ($throwException) {
                throw new \BadMethodCallException('Invalid parameter $uri.');
            } else {
                return false;
            }
        }

        $uri = preg_replace('|\\\|u', '/', $uri);
        $segments = explode('://', $uri, 2);
        $path = array_pop($segments);
        $scheme = array_pop($segments) ?: 'file';

        if ($path) {
            $path = preg_replace('|\\\|u', '/', $path);
            $parts = explode('/', $path);

            $list = [];
            foreach ($parts as $i => $part) {
                if ($part === '..') {
                    $part = array_pop($list);
                    if ($part === null || $part === '' || (!$list && strpos($part, ':'))) {
                        if ($throwException) {
                            throw new \BadMethodCallException('Invalid parameter $uri.');
                        } else {
                            return false;
                        }
                    }
                } elseif (($i && $part === '') || $part === '.') {
                    continue;
                } else {
                    $list[] = $part;
                }
            }

            if (($l = end($parts)) === '' || $l === '.' || $l === '..') {
                $list[] = '';
            }

            $path = implode('/', $list);
        }

        return $splitStream ? [$scheme, $path] : ($scheme !== 'file' ? "{$scheme}://{$path}" : $path);
    }

    /**
     * Removes a prefix from the beginning of a string, if a match is found.
     *
     * @param string $str The string to process.
     * @param string $prefix The prefix to find and remove.
     * @return string
     */
    static public function stripPrefix($str, $prefix = '')
    {
        if (substr($str, 0, strlen($prefix)) == $prefix) {
            $str = substr($str, strlen($prefix));
        }

        return $str;
    }

    /**
     * Determine if a given string matches one or more regular expressions.
     *
     * @param string|array  $patterns
     * @param string  $subject
     * @param array  &$matches
     * @param string $delimiter
     * @param int $flags
     * @param int $offset
     * @return bool
     */
    public static function stringMatches($patterns, $subject, array &$matches = null, $delimiter = '~', $flags = 0, $offset = 0)
    {
        $matches = [];
        $result = false;
        foreach ((array) $patterns as $pattern) {
            $currMatches = [];
            if ($pattern != '' && preg_match($delimiter . $pattern . $delimiter, $subject, $currMatches, $flags, $offset)) {
                $result = true;
                $matches[$pattern] = $currMatches;
            }
        }
        return $result;
    }

    /**
     * Recursively apply a callback to members of an array.
     *
     * @param array $input
     * @param callback $callback
     * @return array
     */
    static public function arrayFilterRecursive($input, $callback = null)
    {
        foreach ($input as &$value) {
            if (is_array($value)) {
                $value = self::arrayFilterRecursive($value, $callback);
            }
        }

        return array_filter($input, $callback);
    }
}
