<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/support
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/LICENSE.md (MIT License)
 */
namespace UserFrosting\Support\Repository\PathBuilder;

/**
 * An example builder class that simply globs together all PHP files in each search path.
 *
 * @author Alexander Weissman (https://alexanderweissman.com)
 */
class SimpleGlobBuilder extends PathBuilder
{
    /**
     * Glob together all file paths in each search path from the locator.
     *
     * @return array
     */
    public function buildPaths($extension = 'php')
    {
        // Get all paths from the locator that match the uri.
        // Put them in reverse order to allow later files to override earlier files.
        $searchPaths = array_reverse($this->locator->findResources($this->uri, true, true));

        $filePaths = [];
        foreach ($searchPaths as $path) {
            $globs = glob(rtrim($path, '/\\') . '/*.' . $extension);
            $filePaths = array_merge($filePaths, $globs);
        }

        return $filePaths;
    }
}
