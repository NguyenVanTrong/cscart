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
 * Class Message
 * @package Tygh\Mailer
 */
class Message
{
    /**
     * @var string Message identifier
     */
    protected $id;

    /**
     * @var array Message params
     */
    protected $params = array();

    /**
     * @var array Message data
     */
    protected $data = array();

    /**
     * @var array Email addresses on reply to
     */
    protected $reply_to = array();

    /**
     * @var array Email addresses on carbon copy
     */
    protected $cc = array();

    /**
     * @var array Email addresses on blind carbon copy
     */
    protected $bcc = array();

    /**
     * @var array Email addresses recipients
     */
    protected $to = array();

    /**
     * @var array Email address from
     */
    protected $from;

    /**
     * @var string Email subject
     */
    protected $subject;

    /**
     * @var string Email body
     */
    protected $body;

    /**
     * @var string Charset
     */
    protected $charset;

    /**
     * @var bool Is html mail
     */
    protected $is_html = false;

    /**
     * @var array Email attachments
     */
    protected $attachments = array();

    /**
     * @var array Email embedded images
     */
    protected $embedded_images = array();

    /**
     * Mail constructor.
     */
    public function __construct()
    {
        if (defined('CHARSET')) {
            $this->charset = CHARSET;
        }
    }

    /**
     * Get message identifier
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set message identifier
     *
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get message params
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set message params
     *
     * @param array $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * Get message data
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set message data
     *
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Get the Reply-To addresses for this message.
     * This method always returns an associative array where the keys provide the email addresses.
     *
     * @return array
     */
    public function getReplyTo()
    {
        return $this->reply_to;
    }

    /**
     * Add a Reply-To address to this message.
     *
     * @param string $address   Email address
     * @param string $name      Name associated with address
     */
    public function addReplyTo($address, $name = '')
    {
        $this->reply_to[$address] = $name;
    }

    /**
     * Get the carbon copy addresses for this message.
     * This method always returns an associative array where the keys provide the email addresses.
     *
     * @return array
     */
    public function getCC()
    {
        return $this->cc;
    }

    /**
     * Add a carbon copy address to this message.
     *
     * @param string $address   Email address
     * @param string $name      Name associated with address
     */
    public function addCC($address, $name)
    {
        $this->cc[$address] = $name;
    }

    /**
     * Get the blind carbon copy addresses for this message.
     * This method always returns an associative array where the keys provide the email addresses.
     *
     * @return array
     */
    public function getBCC()
    {
        return $this->bcc;
    }

    /**
     * Add a blind carbon copy address to this message.
     *
     * @param string $address   Email address
     * @param string $name      Name associated with address
     */
    public function addBCC($address, $name = '')
    {
        $this->bcc[$address] = $name;
    }

    /**
     * Get the From address of this message.
     * This method always returns an associative array where the keys provide the email addresses.
     *
     * @return array
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Set the From address of this message.
     *
     * @param string $address   Email address
     * @param string $name      Name associated with address
     */
    public function setFrom($address, $name = '')
    {
        $this->from = array($address => $name);
    }

    /**
     * Get the subject of the message.
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set the subject of the message.
     *
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->subject = trim($subject);
    }

    /**
     * Get the body of the message.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set the body of the message.
     *
     * @param string $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * Get the To addresses of this message.
     * This method always returns an associative array where the keys provide the email addresses.
     *
     * @return array
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Add a To address to this message.
     *
     * @param string $address   Email address
     * @param string $name      Name associated with address
     */
    public function addTo($address, $name = '')
    {
        $this->to[$address] = $name;
    }

    /**
     * Get the charset of the message.
     *
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * Set the charset of the message.
     *
     * @param string $charset
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;
    }

    /**
     * Is html
     *
     * @return boolean
     */
    public function isIsHtml()
    {
        return $this->is_html;
    }

    /**
     * Set flag is html
     *
     * @param boolean $is_html
     */
    public function setIsHtml($is_html)
    {
        $this->is_html = (bool) $is_html;
    }

    /**
     * Get the attachments of the message.
     * This method always returns an associative array where the keys provide the file path.
     *
     * @return array
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * Set the attachment of the message.
     *
     * @param string $file Absolute path to file
     * @param string $name File name
     */
    public function addAttachment($file, $name)
    {
        $this->attachments[$file] = $name;
    }

    /**
     * Get the embedded images
     *
     * @return array
     */
    public function getEmbeddedImages()
    {
        return $this->embedded_images;
    }

    /**
     * Set the embedded image of the message.
     *
     * @param string $file      Absolute path to file
     * @param string $cid       Email image identifier
     * @param string $mime_type Email image mime type
     */
    public function addEmbeddedImages($file, $cid, $mime_type)
    {
        $this->embedded_images[] = array(
            'file' => $file,
            'cid' => $cid,
            'mime_type' => $mime_type
        );
    }
}