<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/support
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/LICENSE.md (MIT License)
 */
namespace UserFrosting\Support\Exception;

/**
 * NotFoundException
 *
 * This exception should be thrown when a resource could not be found.
 *
 * @author Alexander Weissman (https://alexanderweissman.com)
 */
class NotFoundException extends HttpException
{
    /**
     * {@inheritDoc}
     */
    protected $httpErrorCode = 404;
    
    /**
     * {@inheritDoc}
     */     
    protected $defaultMessage = 'ERROR.404.TITLE';
}
