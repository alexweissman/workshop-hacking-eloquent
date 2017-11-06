<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/support
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/LICENSE.md (MIT License)
 */
namespace UserFrosting\Support\Exception;

/**
 * ForbiddenException
 *
 * This exception should be thrown when a user has attempted to perform an unauthorized action.
 *
 * @author Alexander Weissman (https://alexanderweissman.com)
 */
class ForbiddenException extends HttpException
{
    /**
     * {@inheritDoc}
     */
    protected $httpErrorCode = 403;
    
    /**
     * {@inheritDoc}
     */     
    protected $defaultMessage = "ACCESS_DENIED";
}
