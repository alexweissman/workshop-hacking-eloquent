<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/support
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/LICENSE.md (MIT License)
 */
namespace UserFrosting\Support\Repository\PathBuilder;

use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Base PathBuilder class
 *
 * @author Alexander Weissman (https://alexanderweissman.com)
 */
abstract class PathBuilder
{
    /**
     * @var UniformResourceLocator Locator service to use when searching for files.
     */
    protected $locator;

    /**
     * @var string Virtual path to search in the locator.
     */
    protected $uri;

    /**
     * Create the loader.
     *
     * @param RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator $locator
     * @param string $uri
     */
    public function __construct($locator, $uri)
    {
        $this->locator = $locator;
        $this->uri = $uri;
    }

    /**
     * Build out the ordered list of file paths, using the designated locator and uri for this loader.
     *
     * @return array
     */
    abstract public function buildPaths();
}
