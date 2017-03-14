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

namespace Tygh\Providers;

use Pimple\Container;
use Tygh\Registry;
use Tygh\Tools\Url;

/**
 * Class SessionProvider is used to register session-related components at Application container.
 *
 * @package Tygh\ServiceProviders
 */
class SessionProvider implements \Pimple\ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $app)
    {
        // Session component
        $app['session'] = function ($app) {
            $session = new \Tygh\Web\Session($app);

            // Configure conditions of session start
            if ((defined('NO_SESSION') && NO_SESSION) || (defined('CONSOLE') && CONSOLE)) {
                $session->start_on_init = false;
                $session->start_on_read = false;
                $session->start_on_write = false;

                return $session;
            }

            // Configure session component
            $session->setSessionNamePrefix('sid_');
            $session->setSessionNameSuffix('_' . substr(md5(Registry::get('config.http_location')), 0, 5));
            $session->setName(ACCOUNT_TYPE);
            $session->setSessionIDSuffix('-' . AREA);

            $session->cache_limiter = 'nocache';
            $session->cookie_lifetime = SESSIONS_STORAGE_ALIVE_TIME;
            $session->cookie_path = Registry::ifGet('config.current_path', '/');

            $https_location = new Url(Registry::get('config.https_location'));
            $http_location = new Url(Registry::get('config.http_location'));

            // We shouldn't set secure subdomain as a cookie domain because it will cause
            // two SID cookies with the same name but different domains
            if (defined('HTTPS') && !$https_location->isSubDomainOf($http_location)) {
                $cookie_domain_host = $https_location->getHost();
            } else {
                $cookie_domain_host = $http_location->getHost();
            }

            if (strpos($cookie_domain_host, '.') !== false) {
                // Check if host has www, www2, www4 prefix and remove it
                $cookie_domain_host = preg_replace('/^www[0-9]*\./i', '', $cookie_domain_host);
                $cookie_domain_host = strpos($cookie_domain_host, '.') === 0 ? $cookie_domain_host : '.' . $cookie_domain_host;
            } else {
                // For local hosts set this to empty value
                $cookie_domain_host = '';
            }

            if (!preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/", $cookie_domain_host, $matches)) {
                $cookie_domain = $cookie_domain_host;
            } else {
                $cookie_domain = ini_get('session.cookie_domain');
            }

            $session->cookie_domain = $cookie_domain;

            $session->start_on_init = true;
            $session->start_on_read = true;
            $session->start_on_write = true;

            return $session;
        };

        // Session data storage driver class
        $app['session.storage.class'] = function ($app) {
            $storage_class = Registry::ifGet('config.session_backend', 'database');
            $storage_class = '\\Tygh\\Backend\\Session\\' . ucfirst($storage_class);

            return $storage_class;
        };

        // Session data storage driver instance
        $app['session.storage'] = function ($app) {
            return new $app['session.storage.class'](
                Registry::get('config'),
                array(
                    'ttl' => SESSION_ONLINE,
                    'ttl_storage' => SESSIONS_STORAGE_ALIVE_TIME,
                    'ttl_online' => SESSION_ONLINE
                )
            );
        };
    }
}