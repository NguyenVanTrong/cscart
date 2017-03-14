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

namespace Tygh;

/**
 * Instance of an SoftwareProductEnvironment class is intented to be used an immutable container for information
 * related to currently running software product licensing environment.
 *
 * The instance of this class will be registeted in an application container with "product.env" identifier:
 *
 * ```php
 * $environment = Tygh::$app['product.env'];
 * ```
 *
 * For example, the instance is passed to the Marketplace add-ons upgrade connector, providing information to the
 * Marketplace.
 *
 * @see     \Tygh\Providers\EnvironmentProvider
 */
class SoftwareProductEnvironment
{
    /**
     * @var string The currently running product's name. This can be either "CS-Cart" or "Multivendor".
     */
    protected $product_name;

    /**
     * @var string The version of a product in a semantic versioning format, i.e. "x.x.x".
     */
    protected $product_version;

    /**
     * @var string A licensing mode currently being used. For CS-Cart this can be either "trial", "ultimate" and
     *      "professional". For Multivendor this can only accept one value - "full". Depending on this mode some
     *      store functionality is either accessible or not.
     */
    protected $store_mode;

    /**
     * @var string Development status of a product. This can be either "dev" - meaning it is a pre-release version, or
     *      an empty string, meaining it is a regular production-ready release.
     */
    protected $product_status;

    /**
     * @var string This parameter can be filled by an OEM resellers to segregate their own modified product. For
     *      example, the "CS-Cart Russian Build" fills this parameter with "ru" value.
     */
    protected $product_build;

    /**
     * @var string This is an obsolete legacy parameter which always accepts either the "ULTIMATE" value for the
     *      "CS-Cart" product and the "MULTIVENDOR" value for "Multivendor" product.
     */
    protected $product_edition;

    /**
     * SoftwareProductEnvironment constructor.
     *
     * @param string $product_name
     * @param string $product_version
     * @param string $store_mode
     * @param string $product_status
     * @param string $product_build
     * @param string $product_edition
     */
    public function __construct(
        $product_name,
        $product_version,
        $store_mode,
        $product_status,
        $product_build,
        $product_edition
    ) {
        $this->product_name = $product_name;

        $this->product_version = $product_version;

        $this->store_mode = $store_mode;

        $this->product_status = $product_status;

        $this->product_build = $product_build;

        $this->product_edition = $product_edition;
    }

    /**
     * @return string The currently running product's name. This can either be "CS-Cart" or "Multivendor".
     */
    public function getProductName()
    {
        return $this->product_name;
    }

    /**
     * @return string The version of a product in a semantic versioning format, i.e. "x.x.x".
     */
    public function getProductVersion()
    {
        return $this->product_version;
    }

    /**
     * @return string A licensing mode currently being used. For CS-Cart this can be either "trial", "ultimate" and
     *                "professional". For Multivendor this can only accept one value - "full". Depending on this mode
     *                some store functionality is either accessible or not.
     */
    public function getStoreMode()
    {
        return $this->store_mode;
    }

    /**
     * @return string Development status of a product. This can be either "dev" - meaning it is a pre-release version,
     *                or an empty string, meaining it is a regular production-ready release.
     */
    public function getProductStatus()
    {
        return $this->product_status;
    }

    /**
     * @return string This parameter can be filled by an OEM resellers to segregate their own modified product. For
     *                example, the "CS-Cart Russian Build" fills this parameter with "ru" value.
     */
    public function getProductBuild()
    {
        return $this->product_build;
    }

    /**
     * @return string This is an obsolete legacy parameter which always accepts either the "ULTIMATE" value for the
     *                "CS-Cart" product and the "MULTIVENDOR" value for "Multivendor" product. However, this is still
     *                exists and mustn't be deleted in order to preserve the backward compatibility.
     */
    public function getProductEdition()
    {
        return $this->product_edition;
    }
}
