<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/support
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/LICENSE.md (MIT License)
 */
namespace UserFrosting\Support\Exception;

/**
 * BadRequestException
 *
 * This exception should be thrown when a user has submitted an ill-formed request, or other incorrect data.
 *
 * @author Alexander Weissman (https://alexanderweissman.com)
 */
class BadRequestException extends HttpException
{
    /**
     * {@inheritDoc}
     */
    protected $httpErrorCode = 400;

    /**
     * {@inheritDoc}
     */
    protected $defaultMessage = "NO_DATA";
}
