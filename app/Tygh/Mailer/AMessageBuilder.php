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


use Tygh\Tools\Url;

/**
 * The base abstract class of the message builder.
 *
 * @package Tygh\Mailer
 */
abstract class AMessageBuilder implements IMessageBuilder
{
    /** @var array List of companies */
    protected static $companies = array();

    /** @var null|int Default company identifier */
    protected static $default_company_id;

    /** @var string Current http location (http://example.com) */
    protected $http_location;

    /** @var string Current https location (https://example.com) */
    protected $https_location;

    /** @var string Current path for http location (/) */
    protected $http_path;

    /** @var string Current path for https location (/) */
    protected $https_path;

    /** @var string Absolute path of root directory  */
    protected $root_directory;

    /**
     * AMessageBuilder constructor.
     *
     * ```php
     * $config = [
     *  'http_location' => 'http://example.com',
     *  'https_location' => 'https://example.com',
     *  'http_path' => '/',
     *  'https_path' => '/',
     *  'dir' => [
     *      'root' => '/var/www/example.com'
     *  ]
     * ]
     * ```
     * @param array $config {
     *  @var string $http_location  Current http location (http://example.com)
     *  @var string $https_location Current https location (https://example.com)
     *  @var string $http_path      Current path for http location (/)
     *  @var string $https_path     Current path for https location (/)
     *  @var array  $dir {
     *      @var string $root       Absolute path of root directory
     *  }
     * }
     */
    public function __construct(array $config)
    {
        if (isset($config['http_location'])) {
            $this->http_location = $config['http_location'];
        }

        if (isset($config['https_location'])) {
            $this->https_location = $config['https_location'];
        }

        if (isset($config['http_path'])) {
            $this->http_path = $config['http_path'];
        }

        if (isset($config['https_path'])) {
            $this->https_path = $config['https_path'];
        }

        if (isset($config['dir']['root'])) {
            $this->root_directory = $config['dir']['root'];
        }
    }

    /** @inheritdoc */
    public function createMessage($params, $area, $lang_code)
    {
        $message = new Message();
        $message->setIsHtml(isset($params['is_html']) ? $params['is_html'] : true);

        $params['company_id'] = !empty($params['company_id']) ? $params['company_id'] : 0;
        $company_id = $params['company_id'];
        $company = $this->getCompany($company_id, $lang_code);

        if (!isset($params['data'])) {
            $params['data'] = array();
        }

        if (empty($params['data']['company_data'])) {
            $params['data']['company_data'] = $company;
            $params['data']['company_name'] = $company['company_name'];
        }

        $message->setData($params['data']);

        if (!empty($params['reply_to'])) {
            $emails = $this->getMessageReplyTo($params['reply_to'], $company_id, $lang_code);

            foreach ($emails as $email) {
                $message->addReplyTo($email, '');
            }
        }

        if (!empty($params['cc'])) {
            $emails = $this->getMessageCC($params['cc'], $company_id, $lang_code);

            foreach ($emails as $email) {
                $message->addCC($email, '');
            }
        }

        if (!empty($params['bcc'])) {
            $emails = $this->getMessageBCC($params['bcc'], $company_id, $lang_code);

            foreach ($emails as $email) {
                $message->addBCC($email, '');
            }
        }

        if (!empty($params['to'])) {
            $emails = $this->getMessageTo($params['to'], $company_id, $lang_code);

            foreach ($emails as $email) {
                $message->addTo($email, '');
            }
        }

        if (!empty($params['from'])) {
            $from = $this->getMessageFrom($params['from'], $company_id, $lang_code);

            if (!$from && array_key_exists($params['from'], $company) && $company_id == 0 && $this->allowedFor('ULTIMATE')) {
                $default_company_id = $this->getDefaultCompanyId();
                $from = $this->getMessageFrom($params['from'], $default_company_id, $lang_code);
            }

            if ($from) {
                $name = reset($from);
                $email = key($from);

                $message->setFrom($email, $name);
            }
        }

        if (!empty($params['attachments'])) {
            foreach ($params['attachments'] as $name => $file) {
                $message->addAttachment($file, $name);
            }
        }

        $this->initMessage($message, $params, $area, $lang_code);
        $this->retrieveEmbeddedImages($message);

        return $message;
    }

    /**
     * Initialize message.
     *
     * @param Message   $message    Instance of message
     * @param array     $params     List of message parameters
     * @param string    $area       Current working area
     * @param string    $lang_code  Language code
     */
    abstract protected function initMessage(Message $message, $params, $area, $lang_code);

    /**
     * Retrieve embedded images from message body
     *
     * @param Message $message Instance of message
     */
    public function retrieveEmbeddedImages(Message $message)
    {
        $files = array();
        $body = $message->getBody();

        if (preg_match_all("/(?<=\ssrc=|\sbackground=)('|\")(.*)\\1/SsUi", $body, $matches)) {
            $files = array_merge($files, $matches[2]);
        }

        if (preg_match_all("/(?<=\sstyle=)('|\").*url\(('|\"|\\\\\\1)(.*)\\2\).*\\1/SsUi", $body, $matches)) {
            $files = array_merge($files, $matches[3]);
        }

        if (!empty($files)) {
            $files = array_unique($files);

            foreach ($files as $k => $_path) {
                $cid = 'csimg' . $k;
                $path = str_replace('&amp;', '&', $_path);

                $real_path = '';
                // Replace url path with filesystem if this url is NOT dynamic
                if (strpos($path, '?') === false && strpos($path, '&') === false) {
                    if (($i = strpos($path, $this->http_location)) !== false) {
                        $real_path = substr_replace($path, $this->root_directory, $i, strlen($this->http_location));
                    } elseif (($i = strpos($path, $this->https_location)) !== false) {
                        $real_path = substr_replace($path, $this->root_directory, $i, strlen($this->https_location));
                    } elseif (!empty($this->http_path) && ($i = strpos($path, $this->http_path)) !== false) {
                        $real_path = substr_replace($path, $this->root_directory, $i, strlen($this->http_path));
                    } elseif (!empty($this->https_path) && ($i = strpos($path, $this->https_path)) !== false) {
                        $real_path = substr_replace($path, $this->root_directory, $i, strlen($this->https_path));
                    }
                }

                if (empty($real_path)) {
                    $real_path = (strpos($path, '://') === false) ? $this->http_location .'/'. $path : $path;
                }

                list($width, $height, $mime_type) = $this->getImageSize($real_path);

                if (!empty($width)) {
                    $cid .= '.' . $this->getImageExtension($mime_type);
                    $message->addEmbeddedImages($real_path, $cid, $mime_type);
                    $body = preg_replace("/(['\"])" . str_replace("/", "\/", preg_quote($_path)) . "(['\"])/Ss", "\\1cid:" . $cid . "\\2", $body);
                }
            }

            $message->setBody($body);
        }
    }

    /**
     * Get the From address.
     *
     * @param array|string  $from       Email address or company field (company_users_department)
     * @param int           $company_id Company identifier
     * @param string        $lang_code  Language code
     *
     * @return array|bool Return false if address invalid or an associative array where the keys provide the email addresses
     */
    public function getMessageFrom($from, $company_id, $lang_code)
    {
        $company = $this->getCompany($company_id, $lang_code);
        $email = $name = '';

        if (!is_array($from)) {
            if (!empty($company[$from])) {
                $email =  $company[$from];
                $name = strstr($from, 'default_') ? $company['default_company_name'] : $company['company_name'];
            } elseif ($this->validateAddress($from)) {
                $email = $from;
            }
        } elseif (!empty($from['email'])) {
            if (!empty($company[$from['email']])) {
                $email =  $company[$from['email']];
                if (empty($from['name'])) {
                    $from['name'] = strstr($from['email'], 'default_') ? $company['default_company_name'] : $company['company_name'];
                }
            } else {
                $email = $from['email'];
            }

            if (!empty($from['name'])) {
                $name = !empty($company[$from['name']]) ? $company[$from['name']] : $from['name'];
            }
        }

        if ($email) {
            list($email) = $this->normalizeEmails($email);
        }

        return !empty($email) ? array($email => $name) : false;
    }

    /**
     * Get the Reply-To addresses.
     *
     * @param string|array  $reply_to   Email address or company field (company_users_department)
     * @param int           $company_id Company identifier
     * @param string        $lang_code  Language code
     *
     * @return array
     */
    public function getMessageReplyTo($reply_to, $company_id, $lang_code)
    {
        return $this->normalizeEmails($this->findValuesInCompany($reply_to, $company_id, $lang_code));
    }

    /**
     * Get the carbon copy addresses.
     *
     * @param string|array  $cc         Email address or company field (company_users_department)
     * @param int           $company_id Company identifier
     * @param string        $lang_code  Language code
     *
     * @return array
     */
    public function getMessageCC($cc, $company_id, $lang_code)
    {
        return $this->normalizeEmails($this->findValuesInCompany($cc, $company_id, $lang_code));
    }

    /**
     * Get the blind carbon copy addresses
     *
     * @param string|array  $bcc        Email address or company field (company_users_department)
     * @param int           $company_id Company identifier
     * @param string        $lang_code  Language code
     *
     * @return array
     */
    public function getMessageBCC($bcc, $company_id, $lang_code)
    {
        return $this->normalizeEmails($this->findValuesInCompany($bcc, $company_id, $lang_code));
    }

    /**
     * Get the to addresses.
     *
     * @param string|array  $to         Email address or company field (company_users_department)
     * @param int           $company_id Company identifier
     * @param string        $lang_code  Language code
     *
     * @return array
     */
    public function getMessageTo($to, $company_id, $lang_code)
    {
        return $this->normalizeEmails($this->findValuesInCompany($to, $company_id, $lang_code));
    }

    /**
     * Validate email.
     *
     * @param string $email Email address
     *
     * @return bool If email is invalid return false.
     */
    public function validateAddress($email)
    {
        return fn_validate_email($email, false);
    }

    /**
     * Normalize emails.
     *
     * @param string|array $emails Email addresses
     *
     * @return array
     */
    public function normalizeEmails($emails)
    {
        $result = array();
        foreach ((array) $emails as $email) {
            $email = str_replace(';', ',', $email);
            $res = explode(',', $email);

            foreach ($res as &$v) {
                $v = trim($v);
            }
            unset($v);

            $result = array_merge($result, $res);
        }

        $result = array_unique($result);

        foreach ($result as $k => $email) {
            if ($this->validateAddress($email)) {
                $result[$k] = Url::normalizeEmail($email);

                if (!$result[$k]) {
                    unset($result[$k]);
                }
            } else {
                unset($result[$k]);
            }
        }

        return $result;
    }

    /**
     * Get company data.
     *
     * @param int       $company_id Company identifier
     * @param string    $lang_code  Language code
     *
     * @return array
     */
    protected function getCompany($company_id, $lang_code)
    {
        if (!isset(self::$companies[$company_id][$lang_code])) {
            self::$companies[$company_id][$lang_code] = fn_get_company_placement_info($company_id, $lang_code);
        }

        return self::$companies[$company_id][$lang_code];
    }

    /**
     * Get default company id.
     *
     * @return int
     */
    protected function getDefaultCompanyId()
    {
        if (self::$default_company_id === null) {
            self::$default_company_id = (int) fn_get_default_company_id();
        }

        return self::$default_company_id;
    }

    /**
     * Find message values in company data.
     *
     * @param string|array  $values     List of message value (to, replay-to, cc, bcc)
     * @param int           $company_id Company identifier
     * @param string        $lang_code  Language code
     *
     * @return array
     */
    protected function findValuesInCompany($values, $company_id, $lang_code)
    {
        $result = array();
        $values = (array) $values;
        $company = $this->getCompany($company_id, $lang_code);

        foreach ($values as $key) {
            $result[] = !empty($company[$key]) ? $company[$key] : $key;
        }

        return $result;
    }

    /**
     * Gets image info by image path
     *
     * @param string $real_path Absolute path to image
     * 
     * @return array
     */
    protected function getImageSize($real_path)
    {
        return fn_get_image_size($real_path);
    }

    /**
     * Gets image extension by mime type
     *
     * @param string $mime_type Mime type returned by getImageSize
     *
     * @return string
     */
    protected function getImageExtension($mime_type)
    {
        return fn_get_image_extension($mime_type);
    }

    /**
     * Check edition to allowed for action
     *
     * @param string $edition Edition name
     *
     * @return string
     */
    protected function allowedFor($edition)
    {
        return fn_allowed_for($edition);
    }
}