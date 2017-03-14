<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/


namespace Tygh\Mailer;

/**
 * Class SendResult
 * @package Tygh\Mailer
 */
class SendResult
{
    /** @var array  */
    private $errors = array();

    /** @var bool  */
    private $is_success = false;

    /**
     * SendResult constructor.
     *
     * @param bool  $is_success Success flag
     * @param array $errors     List of error messages
     */
    public function __construct($is_success = false, array $errors = array())
    {
        foreach ($errors as $error) {
            $this->setError($error);
        }

        $this->setIsSuccess($is_success);
    }

    /**
     * Get result of sending
     *
     * @return bool
     */
    public function isSuccess()
    {
        return $this->is_success;
    }

    /**
     * Get error messages
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Set error message
     *
     * @param string $error
     */
    public function setError($error)
    {
        $this->errors[] = $error;
    }

    /**
     * Set result of sending
     *
     * @param boolean $is_success
     */
    public function setIsSuccess($is_success)
    {
        $this->is_success = (bool) $is_success;
    }
}