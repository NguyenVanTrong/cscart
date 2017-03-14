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

namespace Ebay\requests;

/**
 * Class Request
 * @package Ebay\requests
 */
abstract class Request
{
    /** @var string|null Language for error message */
    protected $error_language = 'en_US';
    /** @var string|null Request identifier */
    protected $message_id = '0';
    /** @var string|null Level for warnings */
    protected $warning_level = 'Low';
    /** @var string|null Application token */
    protected $token;
    /** @var string Xml namespace*/
    protected $xmlns = "urn:ebay:apis:eBLBaseComponents";

    /**
     * Return request xml body
     * @return string
     */
    abstract public function xml();

    /**
     * Set application token
     * @param null|string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * Return warning level
     * @return null|string
     */
    public function getWarningLevel()
    {
        return $this->warning_level;
    }

    /**
     * Set warning level
     * @param null|string $warning_level
     * @see http://developer.ebay.com/Devzone/XML/docs/Reference/ebay/types/WarningLevelCodeType.html
     */
    public function setWarningLevel($warning_level)
    {
        $this->warning_level = $warning_level;
    }

    /**
     * Return message id
     * @return null|string
     */
    public function getMessageId()
    {
        return $this->message_id;
    }

    /**
     * Set message id
     *
     * ```
     * Most Trading API calls support a MessageID element in the request and a CorrelationID element in the response.
     * If you pass in a MessageID in a request, the same value will be returned in the CorrelationID field in the response.
     * Pairing these values can help you track and confirm that a response is returned for every request and to match specific responses to specific requests.
     * If you do not pass a MessageID value in the request, CorrelationID is not returned.
     * ```
     * @param null|string $message_id
     */
    public function setMessageId($message_id)
    {
        $this->message_id = $message_id;
    }

    /**
     * Return language for error
     * @return null|string
     */
    public function getErrorLanguage()
    {
        return $this->error_language;
    }

    /**
     * Set language for error
     * @param null|string $error_language
     */
    public function setErrorLanguage($error_language)
    {
        $this->error_language = $error_language;
    }

    /**
     * Set xml namespace
     * @param string|null $value
     */
    public function setXmlNS($value)
    {
        $this->xmlns = $value;
    }

    /**
     * Wrap body xml
     *
     * @param $xml
     * @return string
     */
    protected function wrapXml($xml)
    {
        $warning_level = $this->warning_level;

        if (defined('DEVELOPMENT') && DEVELOPMENT) {
            $warning_level = 'High';
        }

        $rootName = $this->getXmlRootName();

        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<{$rootName} xmlns="{$this->xmlns}">
    <RequesterCredentials>
        <eBayAuthToken>{$this->token}</eBayAuthToken>
    </RequesterCredentials>
    {$xml}
    <MessageID>{$this->message_id}</MessageID>
    <WarningLevel>{$warning_level}</WarningLevel>
</{$rootName}>
XML;
    }

    /**
     * Return request xml
     * @return string
     */
    public function getXml()
    {
        return $this->wrapXml($this->xml());
    }

    /**
     * Return request xml
     * @return string
     */
    public function __toString()
    {
        return $this->getXml();
    }

    /**
     * Return request method name
     * @return string
     */
    public function getMethodName()
    {
        $reflexion = new \ReflectionObject($this);

        return strtr($reflexion->getShortName(), array('Request' => ''));
    }

    /**
     * Return request xml root name
     * @return string
     */
    public function getXmlRootName()
    {
        $reflexion = new \ReflectionObject($this);

        return $reflexion->getShortName();
    }
}
