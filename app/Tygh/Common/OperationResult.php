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

namespace Tygh\Common;

/**
 * Class OperationResult
 * @package Tygh\Common
 */
class OperationResult
{
    protected $errors = array();

    protected $warnings = array();

    protected $messages = array();

    protected $success = false;

    protected $data;

    /**
     * OperationResult constructor.
     *
     * @param bool $success
     * @param null $data
     */
    public function __construct($success = false, $data = null)
    {
        $this->setSuccess($success);
        $this->setData($data);
    }

    /**
     * Sets operation data.
     *
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Gets operation data.
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return boolean
     */
    public function isSuccess()
    {
        return $this->success;
    }

    /**
     * @param boolean $success
     */
    public function setSuccess($success)
    {
        $this->success = (bool) $success;
    }

    /**
     * Add error.
     *
     * @param string $code  Error code.
     * @param string $error Error message.
     */
    public function addError($code, $error)
    {
        $this->errors[$code] = $error;
    }

    /**
     * Remove error by error code.
     *
     * @param string $code Error code.
     */
    public function removeError($code)
    {
        unset($this->errors[$code]);
    }

    /**
     * Sets errors.
     *
     * @param array $errors List of errors.
     */
    public function setErrors(array $errors)
    {
        foreach ($errors as $code => $error) {
            $this->addError($code, $error);
        }
    }

    /**
     * Gets errors.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Gets first error.
     *
     * @return string|false
     */
    public function getFirstError()
    {
        return reset($this->errors);
    }

    /**
     * Add message.
     *
     * @param string $code      Message code.
     * @param string $message   Message.
     */
    public function addMessage($code, $message)
    {
        $this->messages[$code] = $message;
    }

    /**
     * Remove message by code.
     *
     * @param string $code  Message code.
     */
    public function removeMessage($code)
    {
        unset($this->messages[$code]);
    }

    /**
     * Sets messages.
     *
     * @param array $messages
     */
    public function setMessages(array $messages)
    {
        foreach ($messages as $code => $message) {
            $this->addMessage($code, $message);
        }
    }

    /**
     * Gets messages.
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Add warning.
     *
     * @param string $code      Warning code.
     * @param string $warning   Warning message.
     */
    public function addWarning($code, $warning)
    {
        $this->warnings[$code] = $warning;
    }

    /**
     * Remove warning by code.
     *
     * @param string $code
     */
    public function removeWarning($code)
    {
        unset($this->warnings[$code]);
    }

    /**
     * Sets warnings.
     *
     * @param array $messages
     */
    public function setWarnings(array $messages)
    {
        foreach ($messages as $code => $message) {
            $this->addWarning($code, $message);
        }
    }

    /**
     * Gets warnings.
     *
     * @return array
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * Show notifications.
     * Call fn_set_notification for errors, warnings and messages.
     */
    public function showNotifications()
    {
        foreach ($this->errors as $error) {
            fn_set_notification('E', __('error'), $error);
        }

        foreach ($this->warnings as $warning) {
            fn_set_notification('W', __('warning'), $warning);
        }

        foreach ($this->messages as $message) {
            fn_set_notification('N', __('successful'), $message);
        }
    }
}