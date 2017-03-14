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
use Pimple\ServiceProviderInterface;
use Tygh\Registry;
use Tygh\Twig\TwigCacheFilesystem;
use Tygh\Twig\TwigCoreExtension;
use Tygh\Twig\TwigEnvironment;

/**
 * The provider class that registers the twig component in the Tygh::$app container.
 * 
 * @package Tygh\Providers
 */
class TwigProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $app)
    {
        $app['twig'] = function ($app) {
            $loader = new \Twig_Loader_Array(array());
            $twig = new TwigEnvironment($loader, array(
                'cache' => new TwigCacheFilesystem(Registry::get('config.dir.cache_twig_templates')),
                'auto_reload' => true,
                'autoescape' => false,
                'debug' => fn_is_development()
            ));

            $twig->addExtension(new TwigCoreExtension());
            $twig->addExtension(new \Twig_Extensions_Extension_Text());
            $twig->addExtension(new \Twig_Extensions_Extension_Array());

            if (fn_is_development()) {
                $twig->addExtension(new \Twig_Extension_Debug());
            }

            return $twig;
        };
    }
}