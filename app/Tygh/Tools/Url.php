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

namespace Tygh\Tools;

class Url
{
    const PUNYCODE_PREFIX = 'xn--';

    /**
     * @var string Input URL
     */
    protected $string_url = '';

    /**
     * @var array Result of parse_url() function
     */
    protected $parsed_url = array();

    /**
     * @var array Query parameters list that will be used when building URL
     */
    protected $query_params = array();

    /**
     * @var bool Was input URL encoded
     */
    protected $is_encoded = false;

    /**
     * Creates URL object and parses given URL to its components.
     *
     * @param string|null $url Input URL
     */
    public function __construct($url = null)
    {
        if ($url) {
            $url = trim($url);

            if (PHP_VERSION_ID < 50407 && strncmp($url, '//', 2) === 0) {
                $parsed = parse_url('http:' . $url);
                unset($parsed['scheme']);
            } else {
                $parsed = parse_url($url);
            }

            // Gracefully supress potential errors
            if ($parsed === false) {
                $parsed = array();
            }

            $this->string_url = $url;
            $this->parsed_url = $parsed;

            if (!empty($parsed['path'])) {
                $this->setPath($parsed['path']);
            }

            if (isset($parsed['query'])) {
                $this->setQueryString($parsed['query']);
            }
        }
    }

    /**
     * Sets URL schema.
     *
     * @param string $protocol
     */
    public function setProtocol($protocol)
    {
        $this->parsed_url['scheme'] = $protocol;
    }

    /**
     * @return string|null URL schema if it exists, null otherwise.
     */
    public function getProtocol()
    {
        return isset($this->parsed_url['scheme']) ? $this->parsed_url['scheme'] : null;
    }

    /**
     * Sets URL hostname.
     *
     * @param string $host
     */
    public function setHost($host)
    {
        $this->parsed_url['host'] = $host;
    }

    /**
     * @return string|null URL hostname if it exists, null otherwise.
     */
    public function getHost()
    {
        return isset($this->parsed_url['host']) ? $this->parsed_url['host'] : null;
    }

    /**
     * Sets URL query string and renews internal query parameters list.
     *
     * @param string $query_string
     */
    public function setQueryString($query_string)
    {
        if (strpos($query_string, '&amp;') !== false) {
            $this->is_encoded = true;
            $query_string = str_replace('&amp;', '&', $query_string);
        }
        $this->parsed_url['query'] = $query_string;

        parse_str($query_string, $this->query_params);
    }

    /**
     * @return string|null URL query string if it exists, null otherwise.
     */
    public function getQueryString()
    {
        return isset($this->parsed_url['query']) ? $this->parsed_url['query'] : null;
    }

    /**
     * Sets URL path.
     *
     * @param string $path
     */
    public function setPath($path)
    {
        $this->parsed_url['path'] = rawurldecode($path);
    }

    /**
     * @return string|null URL path if it exists, null otherwise.
     */
    public function getPath()
    {
        return isset($this->parsed_url['path']) ? $this->parsed_url['path'] : null;
    }

    /**
     * @return bool Whether input URL was encoded
     */
    public function getIsEncoded()
    {
        return $this->is_encoded;
    }

    /**
     * @return array List of query parameters and their values
     */
    public function getQueryParams()
    {
        return $this->query_params;
    }

    /**
     * Sets query parameters
     *
     * @param array $params Query parameters and their values
     */
    public function setQueryParams(array $params)
    {
        $this->query_params = $params;
    }

    /**
     * Removes given query parameters from query string.
     *
     * @param array $param_names Parameter names
     */
    public function removeParams(array $param_names)
    {
        foreach ($param_names as $param_name) {
            if (isset($this->query_params[$param_name])) {
                unset ($this->query_params[$param_name]);
            }
        }
    }

    /**
     * @return int|null URL port if it exists, null otherwise.
     */
    public function getPort()
    {
        return isset($this->parsed_url['port']) ? $this->parsed_url['port'] : null;
    }

    /**
     * Sets URL port.
     *
     * @param int $port
     */
    public function setPort($port)
    {
        $this->parsed_url['port'] = (int) $port;
    }

    /**
     * Creates string representation of URL from current state of the object.
     *
     * @param bool $encode Whether to encode ampersands
     * @param bool $puny   - encode URL host to punycode
     *
     * @return string Result URL
     */
    public function build($encode = false, $puny = false)
    {
        // Build and encode query string if needed
        $query_string = http_build_query(
            $this->query_params,
            null,
            ($encode ? '&amp;' : '&')
        );
        if (!empty($query_string)) {
            $this->parsed_url['query'] = $query_string;
        } elseif (isset($this->parsed_url['query'])) {
            unset ($this->parsed_url['query']);
        }

        // Encode URL's path parts
        if (isset($this->parsed_url['path'])) {
            $this->parsed_url['path'] = implode('/',
                array_map('rawurlencode',
                    explode('/', $this->parsed_url['path'])
                )
            );
        }

        if ($puny) {
            $this->punyEncode();
        }

        $scheme = isset($this->parsed_url['scheme']) ? $this->parsed_url['scheme'] . '://' : '';
        $host = isset($this->parsed_url['host']) ? $this->parsed_url['host'] : '';
        $port = isset($this->parsed_url['port']) ? ':' . $this->parsed_url['port'] : '';
        $user = isset($this->parsed_url['user']) ? $this->parsed_url['user'] : '';
        $pass = isset($this->parsed_url['pass']) ? ':' . $this->parsed_url['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = isset($this->parsed_url['path']) ? $this->parsed_url['path'] : '';
        $query = isset($this->parsed_url['query']) ? '?' . $this->parsed_url['query'] : '';
        $fragment = isset($this->parsed_url['fragment']) ? '#' . $this->parsed_url['fragment'] : '';

        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    /**
     * Checks whether given URL is a subpart of the current URL by matching their paths.
     *
     * @param \Tygh\Tools\Url $url URL to check against
     *
     * @TODO: Write unit-tests
     *
     * @return bool Whether current URL path contains given URL path along with their hostnames do match.
     */
    public function containsAsSubpath(self $url)
    {
        return ($this->getHost() == $url->getHost()) && (
            trim($this->getPath(), '/') === trim($url->getPath(), '/')
            ||
            strpos($this->getPath(), rtrim($url->getPath(), '/') . '/') === 0
        );
    }

    /**
     * Checks whether the current URL's hostname is a subdomain of the given URL's hostname.
     *
     * @param \Tygh\Tools\Url $url URL to check against
     *
     * @return bool Checking result
     */
    public function isSubDomainOf(self $url)
    {
        $my_host = $this->getHost();
        $subject_host = $url->getHost();

        if ($my_host == $subject_host) {
            return false;
        }

        $exploded_subject_host = explode('.', $subject_host);
        $exploded_host = explode('.', $my_host);

        $exploded_subject_host = array_reverse($exploded_subject_host);
        $exploded_host = array_reverse($exploded_host);

        foreach ($exploded_subject_host as $i => $subject_host_part) {
            if (isset($exploded_host[$i])) {
                if ($exploded_host[$i] == $subject_host_part) {
                    continue;
                } else {
                    // Subject host differs
                    return false;
                }
            } else {
                // Subject host contains more parts than current host
                return false;
            }
        }

        return true;
    }


    /**
     * Decode the host from Punycode.
     */
    public function punyDecode()
    {
        $host = $this->getHost();

        if ($host && self::isPunycoded($host)) {
            try {
                $idn = new \Net_IDNA2();
                $this->setHost($idn->decode($host));
            } catch (\InvalidArgumentException $e) {}
        }
    }

    /**
     * Encode the host to Punycode.
     */
    public function punyEncode()
    {
        $host = $this->getHost();

        if ($host && !self::isPunycoded($host)) {
            try {
                $idn = new \Net_IDNA2();
                $this->setHost($idn->encode($host));
            } catch (\InvalidArgumentException $e) {}
        }
    }

    /**
     * Normalize URL to pass it to parse_url function
     *
     * @param string $url URL
     *
     * @return string normalized URL
     */
    private static function fix($url)
    {
        $url = trim($url);
        $url = preg_replace('/^(http[s]?:\/\/|\/\/)/', '', $url);

        if (!empty($url)) {
            $url = 'http://' . $url;
        }

        return $url;
    }

    /**
     * Cleans up URL, leaving domain and path only
     *
     * @param string $url URL
     *
     * @return string cleaned up URL
     */
    public static function clean($url)
    {
        $url = self::fix($url);
        if ($url) {
            $domain = self::normalizeDomain($url);
            $path = parse_url($url, PHP_URL_PATH);

            return $domain . rtrim($path, '/');
        }

        return '';
    }

    /**
     * Normalizes domain name and punycode's it
     *
     * @param string $url URL
     *
     * @return mixed string with normalized domain on success, boolean false otherwise
     */
    public static function normalizeDomain($url)
    {
        $url = self::fix($url);
        if ($url) {
            $domain = parse_url($url, PHP_URL_HOST);
            $port = parse_url($url, PHP_URL_PORT);
            if (!empty($port)) {
                $domain .= ':' . $port;
            }
            if (!self::isPunycoded($domain)) {
                try {
                    $idn = new \Net_IDNA2();
                    $domain = $idn->encode($domain);
                } catch (\InvalidArgumentException $e) {}
            }

            return strtolower($domain);
        }

        return false;
    }

    /**
     * Normalizes email name and punycode's it
     *
     * @param  string $email E-mail
     * @return mixed  string with normalized email on success, boolean false otherwise
     */
    public static function normalizeEmail($email)
    {
        list($name, $domain) = explode('@', $email, 2);
        $domain = self::normalizeDomain($domain);
        if ($domain) {
            return $name . '@' . $domain;
        }

        return false;
    }

    /**
     * Decodes punycoded'd URL
     *
     * @param string $url URL
     * @param bool $return_url Whether to return url instead of host and path. Default false.
     *
     * @return string|false String with decoded host on success, boolean false otherwise
     */
    public static function decode($url, $return_url = false)
    {
        $url = trim($url);

        if (empty($url)) {
            return false;
        }

        $url_object = new self($url);
        $protocol = $url_object->getProtocol();
        $host = $url_object->getHost();

        if (empty($host) && empty($protocol)) {
            $url = 'http://' . $url;
            $url_object = new self($url);
            $url_object->setProtocol(null);
        }

        $url_object->punyDecode();

        if ($return_url) {
            return $url_object->build($url_object->getIsEncoded());
        } else {
            $host = $url_object->getHost();
            $port = $url_object->getPort();
            $path = (string) $url_object->getPath();

            if (!empty($port)) {
                $host .= ':' . $port;
            }

            return $host . rtrim($path, '/');
        }
    }

    /**
     * Resolves relative url
     *
     * @param string $url  relative url
     * @param string $base url base
     *
     * @return string $url resolved url
     */
    public static function resolve($url, $base)
    {
        if ($url[0] == '/') {
            $_pbase = parse_url(self::fix($base));
            $url = $_pbase['protocol'] . '://' . $_pbase['host'] . $url;
        } else {
            $url = $base . '/' . $url;
        }

        return $url;
    }

    protected static function isPunycoded($domain)
    {
        $has_prefix = strpos($domain, self::PUNYCODE_PREFIX) === 0;
        $has_content = strpos($domain, '.' . self::PUNYCODE_PREFIX) !== false;

        return $has_prefix || $has_content;
    }

    /**
     * Check valid url
     *
     * @param string $url
     * @return bool
     */
    public static function isValid($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
            return true;
        }

        try {
            $idn = new \Net_IDNA2();
            $url = $idn->encode($url);

            if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
                return true;
            }
        } catch (\InvalidArgumentException $e) {}

        return false;
    }
}