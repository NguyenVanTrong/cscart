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

namespace Ebay\responses;

/**
 * Class Response
 * @package Ebay\responses
 */
class Response
{
    /** @var array  */
    protected $errors = array();

    /** @var array  */
    protected $error_codes = array();

    /** @var array  */
    protected $warnings = array();

    /** @var bool */
    protected $success;

    /** @var string */
    protected $correlation_id;

    /**
     * Constructor
     * @param \SimpleXMLElement $response
     */
    public function __construct(\SimpleXMLElement $response)
    {
        if (!empty($response->Errors)) {
            foreach ($response->Errors as $error) {
                $code = (string) $error->ErrorCode;
                $item = array(
                    'code' => $code,
                    'title' => (string) $error->ShortMessage,
                    'message' => (string) $error->LongMessage
                );

                if ((string) $error->SeverityCode === 'Error') {
                    $this->errors[] = $item;
                    $this->error_codes[$code] = $code;
                } else {
                    $this->warnings[] = $item;
                }
            }
        }

        if (!empty($response->CorrelationID)) {
            $this->correlation_id = (string) $response->CorrelationID;
        }

        $this->success = empty($this->errors);
    }

    /**
     * Return flag success
     * @return bool
     */
    public function isSuccess()
    {
        return $this->success === true;
    }

    /**
     * Return errors
     * @return array
     * ```php
     * array(
     *  code => array(
     *      code => string,
     *      title => string,
     *      message => string
     *  )
     * )
     * ```
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Return error messages
     * @return array
     */
    public function getErrorMessages()
    {
        return array_map(function($item) {
            return $item['message'];
        }, $this->errors);
    }

    /**
     * Return warnings
     * @return array
     * ```php
     * array(
     *  code => array(
     *      code => string,
     *      title => string,
     *      message => string
     *  )
     * )
     * ```
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * Return correlation id
     * @return string
     */
    public function getCorrelationId()
    {
        return $this->correlation_id;
    }

    /**
     * Return success rate response
     * @return int|float
     */
    public function getSuccessRate()
    {
        return $this->isSuccess() ? 1 : 0;
    }

    /**
     * Return class name
     * @return string
     */
    public static function className()
    {
        return get_called_class();
    }
}
