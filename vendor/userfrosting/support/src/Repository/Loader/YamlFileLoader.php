<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/support
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/LICENSE.md (MIT License)
 */
namespace UserFrosting\Support\Repository\Loader;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use UserFrosting\Support\Exception\FileNotFoundException;
use UserFrosting\Support\Exception\JsonException;

/**
 * Load content from yaml/json files.
 *
 * @author Alexander Weissman (https://alexanderweissman.com)
 */
class YamlFileLoader extends FileRepositoryLoader
{
    /**
     * @return array
     */
    protected function parseFile($path)
    {
        $doc = file_get_contents($path);
        if ($doc === false) {
            throw new FileNotFoundException("The file '$path' could not be read.");
        }

        try {
            $result = Yaml::parse($doc);
        } catch (ParseException $e) {
            // Fallback to try and parse as JSON, if it fails to be parsed as YAML
            $result = json_decode($doc, true);
            if ($result === null) {
                throw new JsonException("The file '$path' does not contain a valid YAML or JSON document.  JSON error: " . json_last_error());
            }
        }

        return $result;
    }
}
