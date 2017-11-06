<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/support
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/LICENSE.md (MIT License)
 */
namespace UserFrosting\Support\Exception;

use UserFrosting\Support\Message\UserMessage as UserMessage;

/**
 * HttpException
 *
 * Child classes of HttpException should be thrown when we want to return
 * an HTTP status code and user-viewable message(s) during the application lifecycle.
 *
 * @author Alexander Weissman (https://alexanderweissman.com)
 */
class HttpException extends \Exception
{
    /**
     * @var integer Default HTTP error code associated with this exception.
     */
    protected $httpErrorCode = 500;

    /**
     * @var array[UserMessage]
     */
    protected $messages = [];

    /**
     * @var string Default user-viewable error message associated with this exception.
     */
    protected $defaultMessage = "SERVER_ERROR";

    /**
     * Return the HTTP status code associated with this exception.
     *
     * @return int
     */
    public function getHttpErrorCode()
    {
        return $this->httpErrorCode;
    }

    /**
     * Return the user-viewable messages associated with this exception.
     *
     * @return array[UserMessage]
     */
    public function getUserMessages()
    {
        if (empty($this->messages)) {
            $this->addUserMessage($this->defaultMessage);
        }

        return $this->messages;
    }

    /**
     * Add a user-viewable message for this exception.
     *
     * @param UserMessage|string $message
     * @param array $parameters The parameters to be filled in for any placeholders in the message.
     */
    public function addUserMessage($message, $parameters = [])
    {
        if ($message instanceof UserMessage) {
            $this->messages[] = $message;
        } else {
            // Tight coupling is probably OK here
            $this->messages[] = new UserMessage($message, $parameters);
        }
    }
}
