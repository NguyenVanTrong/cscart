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

namespace Ebay;
use Ebay\objects\Category;
use Ebay\objects\CategoryFeature;
use Ebay\objects\Shipping;
use Ebay\objects\Site;
use Ebay\responses\Response;
use Tygh\Ajax;
use Tygh\Registry;
use Tygh\Tygh;

/**
 * Class Controller
 * @package Ebay
 */
class Controller
{
    /** The maximum number of products by one exported  */
    const MAX_COUNT_EXPORT_NEW_PRODUCTS = 5;
    /** The maximum number of products by one end  */
    const MAX_COUNT_END_PRODUCTS = 10;

    protected static $errors = array();
    protected static $error_counter = array();
    protected static $count_success = 0;
    protected static $count_fail = 0;
    protected static $count_skip = 0;
    protected static $count_external_error = 0;

    /**
     * Action export products
     * @return array
     */
    public static function actionExportProducts()
    {
        static::exportProducts($_REQUEST['product_ids']);

        return static::returnBackRedirect('products.manage');
    }

    /**
     * Action export template products
     * @return array
     */
    public static function actionExportTemplate()
    {
        $template = Template::getById((int) $_REQUEST['template_id']);

        if (empty($template->id)) {
            return array(CONTROLLER_STATUS_REDIRECT, 'ebay.manage');
        }

        $product_ids = Product::getTemplateProductIds($template->id);
        static::exportProducts($product_ids);

        return static::returnBackRedirect('ebay.manage');
    }

    /**
     * @return array
     */
    public static function actionUpdateTemplateProductStatus()
    {
        $template = Template::getById((int) $_REQUEST['template_id']);

        if (empty($template->id)) {
            return array(CONTROLLER_STATUS_REDIRECT, 'ebay.manage');
        }

        $product_ids = Product::getTemplateProductIds($template->id);

        static::updateProductsStatus($product_ids);

        return static::returnBackRedirect('ebay.manage');
    }

    /**
     * Action update product status.
     * Check all exported products.
     */
    public static function actionUpdateProductStatus()
    {
        static::updateProductsStatus($_REQUEST['product_ids']);

        return static::returnBackRedirect('products.manage');
    }

    /**
     * Action export products
     * @return array
     */
    public static function actionEndProducts()
    {
        static::endProducts($_REQUEST['product_ids']);

        return static::returnBackRedirect('products.manage');
    }

    /**
     * Action export template products
     * @return array
     */
    public static function actionEndTemplate()
    {
        $template = Template::getById((int) $_REQUEST['template_id']);

        if (empty($template->id)) {
            return array();
        }

        $product_ids = Product::getTemplateProductIds($template->id);
        static::endProducts($product_ids);

        return static::returnBackRedirect('ebay.manage');
    }

    /**
     * @param array $product_ids
     */
    protected static function updateProductsStatus(array $product_ids)
    {
        @set_time_limit(0);

        static::$count_success = 0;
        static::$count_fail = 0;
        static::$count_skip = 0;
        static::$count_external_error = 0;
        static::$errors = array();
        static::$error_counter = array();

        $product_ids = array_filter($product_ids);
        fn_set_progress('parts', count($product_ids));

        foreach ($product_ids as $product_id) {
            $product = new Product($product_id, array('external'));
            $external_id = $product->getExternalId();

            if (empty($product->id) || empty($external_id)) {
                if (empty($external_id)) {
                    static::setError(
                        '100_' . $product_id,
                        __('ebay_product_not_exported', array('[product]' => $product->original_title)),
                        true
                    );
                }

                fn_set_progress('echo', '.');
                static::$count_skip++;
                continue;
            }

            fn_set_progress(
                'echo',
                __('ebay_get_product_status', array('[product]' => htmlspecialchars($product->original_title))),
                false
            );

            $api = Client::instance($product->getTemplate());

            $result = $api->getItem($product);

            if ($result) {
                if ($result->isSuccess()) {
                    static::$count_success++;

                    if ($result->statusIsActive() && !$product->statusIsActive()) {
                        $product->setStatusActive();
                    } elseif (!$result->statusIsActive() && !$product->statusIsClosed()) {
                        $product->setStatusClosed();
                    }
                } else {
                    static::$count_fail++;
                }

                static::checkResponse($product, $result, ProductLogger::ACTION_GET_PRODUCT_STATUS);
            } else {
                static::checkInternalErrors($product, $api, ProductLogger::ACTION_GET_PRODUCT_STATUS);
            }

            fn_set_progress('echo', '.');
        }

        /** @var \Smarty $smarty */
        $smarty = Tygh::$app['view'];

        $smarty->assign('update_status_result',array(
            'count_success' => static::$count_success,
            'count_fail' => static::$count_fail,
            'count_skip' => static::$count_skip,
            'errors' => static::$errors,
            'error_counter' => static::$error_counter,
            'count_external_error' => static::$count_external_error
        ));

        fn_set_notification(
            'I',
            __('ebay_update_status_summary_title'),
            $smarty->fetch('addons/ebay/views/ebay/components/update_status_summary.tpl')
        );
    }

    /**
     * @param array $product_ids
     */
    protected static function exportProducts(array $product_ids)
    {
        @set_time_limit(0);

        static::$count_success = 0;
        static::$count_fail = 0;
        static::$count_skip = 0;
        static::$errors = array();
        static::$error_counter = array();

        $product_ids = array_filter($product_ids);
        $templates = $groups = array();

        fn_set_progress('parts', count($product_ids) * 2);

        foreach ($product_ids as $product_id) {
            $product = new Product($product_id);
            $external_id = $product->getExternalId();

            if (empty($product->id)) {
                fn_set_progress('echo', '.');
                static::$count_skip++;
                continue;
            }

            if (empty($product->template_id)) {
                $template = Template::getDefaultByCompanyId($product->company_id);

                if ($template) {
                    $product->setTemplateId($template->id);
                    $product->saveTemplateId();
                    $product->reInitProductIdentifiers();
                } else {
                    $company_name = fn_get_company_name($product->company_id);
                    fn_set_notification('E', __('error'), __('ebay_default_template_not_found', array(
                        '[company_name]' => $company_name
                    )));
                    continue;
                }
            }

            static::synchronizationProductCategoryFeature($product);

            if (!empty($external_id)) {
                $api = Client::instance($product->getTemplate());

                static::uploadProductImages($product);

                fn_set_progress(
                    'echo',
                    __('ebay_export_product', array('[product]' => htmlspecialchars($product->original_title))),
                    false
                );

                $combinations = $product->getCombinations();
                $external_combinations = $product->getExternalCombinations();

                if (!empty($combinations) && empty($external_combinations)) {
                    $result = $api->getItem($product);

                    if ($result && $result->isSuccess()) {
                        $product->setExternalCombinations($result->getProductVariations());
                    }
                }

                $result = $api->reviseItem($product);

                if ($result) {
                    if ($result->isSuccess()) {
                        $product->setExternalCombinations($product->getCombinations());
                        $product->setStatusActive();
                        $product->saveExternalData();
                    } elseif ($result->issetErrorAuctionEnded()) {
                        $result = $api->relistItem($product);

                        if ($result && $result->isSuccess()) {
                            Product::deleteExternalData($product->getExternalId());

                            $product->setExternalId($result->getExternalId());
                            $product->setExternalCombinations($product->getCombinations());
                            $product->saveExternalData();
                            $product->setStatusActive();
                        }
                    }
                }

                if ($result) {
                    if ($result->isSuccess()) {
                        static::$count_success++;

                        ProductLogger::info(ProductLogger::ACTION_UPDATE_PRODUCT, $product, __('ebay_success_exported_product_notice'));
                    } else {
                        static::$count_fail++;
                    }

                    static::checkResponse($product, $result, ProductLogger::ACTION_UPDATE_PRODUCT);
                } else {
                    static::checkInternalErrors($product, $api, ProductLogger::ACTION_UPDATE_PRODUCT);
                }

                fn_set_progress('echo', '.');
            } else {
                $groups[$product->template_id][] = $product;
                $templates[$product->template_id] = $product->getTemplate();

                if (count($groups[$product->template_id]) >= static::MAX_COUNT_EXPORT_NEW_PRODUCTS) {
                    static::exportGroupProducts($product->getTemplate(), $groups[$product->template_id]);
                    unset($groups[$product->template_id]);
                }
            }
        }

        if (!empty($groups)) {
            foreach ($groups as $template_id => $products) {
                static::exportGroupProducts($templates[$template_id], $products);
            }
        }

        /** @var \Smarty $smarty */
        $smarty = Tygh::$app['view'];

        $smarty->assign('export_result',array(
            'count_success' => static::$count_success,
            'count_fail' => static::$count_fail,
            'count_skip' => static::$count_skip,
            'errors' => static::$errors,
            'error_counter' => static::$error_counter,
            'count_external_error' => static::$count_external_error
        ));

        fn_set_notification(
            'I',
            (static::$count_fail == 0) ? __('ebay_export_success'): __('ebay_export_failed'),
            $smarty->fetch('addons/ebay/views/ebay/components/export_summary.tpl')
        );
    }

    /**
     * Upload product images
     *
     * @param Product $product
     * @return bool
     */
    protected static function uploadProductImages(Product $product)
    {
        $return = true;

        fn_set_progress(
            'echo',
            __('exporting_images_to_ebay', array('[product]' => htmlspecialchars($product->original_title))),
            false
        );

        foreach ($product->getPictures() as $item) {
            if (empty($item->external_path)) {
                $api = Client::instance($product->getTemplate());

                $result = $api->uploadImage($item->path, $item->hash);

                if ($result) {
                    static::checkResponse($product, $result, ProductLogger::ACTION_UPLOAD_IMAGE);

                    if ($result->isSuccess()) {
                        $product->setExternalPicture($item, $result->getUrl());
                    } else {
                        $return = false;
                    }
                } else {
                    static::checkInternalErrors($product, $api, ProductLogger::ACTION_UPLOAD_IMAGE);
                    $return = false;
                }
            }
        }

        fn_set_progress('echo', '.');

        return $return;
    }

    /**
     * Export new products on ebay
     *
     * @param Template  $template
     * @param Product[] $products
     */
    protected static function exportGroupProducts(Template $template, array $products)
    {
        $api = Client::instance($template);
        $names = array();

        foreach ($products as $product) {
            static::uploadProductImages($product);
            $names[] = htmlspecialchars($product->original_title);
        }

        fn_set_progress(
            'echo',
            __('ebay_export_products', array('[product]' => implode('", "', $names))),
            false
        );

        $result = $api->addItems($products);

        if ($result) {
            foreach ($products as $product) {
                $productResult = $result->getItem($product->id);

                if ($productResult) {
                    if ($productResult->isSuccess()) {
                        static::$count_success++;

                        $product->setExternalId($productResult->getExternalId());
                        $product->saveExternalData();
                        $product->setStatusActive();
                        ProductLogger::info(ProductLogger::ACTION_EXPORT_PRODUCT, $product, __('ebay_success_exported_product_notice'));
                    } else {
                        static::$count_fail++;
                    }

                    static::checkResponse($product, $productResult, ProductLogger::ACTION_EXPORT_PRODUCT);
                } elseif (!$result->isSuccess()) {
                    static::$count_fail++;

                    static::checkResponse($product, $result, ProductLogger::ACTION_EXPORT_PRODUCT);
                }
            }
        } else {
            static::checkInternalErrors($products, $api, ProductLogger::ACTION_EXPORT_PRODUCT);
        }

        foreach ($products as $product) {
            fn_set_progress('echo', '.');
        }
    }

    /**
     * @param $product_ids
     * @throws \Exception
     * @throws \SmartyException
     */
    protected static function endProducts(array $product_ids)
    {
        @set_time_limit(0);

        static::$count_success = 0;
        static::$count_fail = 0;
        static::$count_skip = 0;
        static::$errors = array();
        static::$error_counter = array();

        $product_ids = array_filter($product_ids);
        $templates = $groups = array();

        fn_set_progress('parts', count($product_ids));

        foreach ($product_ids as $product_id) {
            $product = new Product($product_id);
            $external_id = $product->getExternalId();

            if (empty($external_id) || $product->statusIsClosed()) {
                if (empty($external_id)) {
                    static::setError(
                        '100_' . $product_id,
                        __('ebay_product_not_exported', array(
                            '[product]' => $product->original_title
                        )),
                        true
                    );
                } else {
                    static::setError(
                        '101_' . $product_id,
                        __('ebay_product_already_sales_closed', array(
                            '[product]' => $product->original_title
                        )),
                        true
                    );
                }

                fn_set_progress('echo', '.');
                static::$count_skip++;
                continue;
            }

            if (!isset($templates[$product->template_id])) {
                $templates[$product->template_id] = $product->getTemplate();
            }

            $groups[$product->template_id][] = $product;

            if (count($groups[$product->template_id]) >= static::MAX_COUNT_END_PRODUCTS) {
                static::endGroupProducts($templates[$product->template_id], $groups[$product->template_id]);
                unset($groups[$product->template_id]);
            }
        }

        if (!empty($groups)) {
            foreach ($groups as $template_id => $products) {
                static::endGroupProducts($templates[$template_id], $products);
            }
        }

        /** @var \Smarty $smarty */
        $smarty = Tygh::$app['view'];

        $smarty->assign('end_result',array(
            'count_success' => static::$count_success,
            'count_fail' => static::$count_fail,
            'count_skip' => static::$count_skip,
            'errors' => static::$errors,
            'error_counter' => static::$error_counter,
            'count_external_error' => static::$count_external_error
        ));

        fn_set_notification(
            'I',
            __('ebay_end_summary_title'),
            $smarty->fetch('addons/ebay/views/ebay/components/end_summary.tpl')
        );
    }

    /**
     * @param Template  $template
     * @param Product[] $products
     */
    protected static function endGroupProducts(Template $template, array $products)
    {
        $api = Client::instance($template);

        $names = array_map(function (Product $product) {
            return htmlspecialchars($product->original_title);
        }, $products);

        fn_set_progress(
            'echo',
            __('ebay_end_products', array('[product]' => implode('", "', $names))),
            false
        );

        $result = $api->endItems($products);

        if ($result) {
            foreach ($products as $product) {
                $productResult = $result->getItem($product->id);

                if ($productResult) {
                    if ($productResult->isSuccess()) {
                        static::$count_success++;

                        $product->setStatusClosed();
                        ProductLogger::info(ProductLogger::ACTION_END_PRODUCT, $product, __('ebay_success_close_product_notice'));
                    } else {
                        static::$count_fail++;
                    }

                    static::checkResponse($product, $productResult, ProductLogger::ACTION_END_PRODUCT);
                }
            }
        } else {
            static::checkInternalErrors($products, $api, ProductLogger::ACTION_END_PRODUCT);
        }

        foreach ($products as $product) {
            fn_set_progress('echo', '.');
        }
    }

    /**
     * @param Product|Product[] $product
     * @param Client $api
     * @param int $action
     */
    protected static function checkInternalErrors($product, Client $api, $action)
    {
        $products = array();

        if ($product instanceof Product) {
            $products = array($product);
        } elseif (is_array($product)) {
            $products = $product;
        }

        $errors = $api->getErrors();

        foreach ($errors as $error) {
            $code = crc32($error);

            static::setError($code, $error);

            foreach ($products as $product) {
                if ($product instanceof Product) {
                    ProductLogger::error($action, $product, $error, $code);
                }
            }
        }
    }

    /**
     * @param Product $product
     * @param Response $response
     * @param int $action
     */
    protected static function checkResponse(Product $product, Response $response, $action)
    {
        $errors = $response->getErrors();
        $warnings = $response->getWarnings();

        foreach ($errors as $error) {
            ProductLogger::error($action, $product, $error['message'], $error['code']);
            static::setError($error['code'], $error['message']);
        }

        foreach ($warnings as $warning) {
            ProductLogger::warning($action, $product, $warning['message'], $warning['code']);
        }
    }

    /**
     * @param string $code
     * @param string $error
     * @param bool $system
     */
    protected static function setError($code, $error, $system = false)
    {
        if ($system) {
            $code = 'internal_' . $code;
        } else {
            static::$count_external_error++;
        }

        static::$errors[$code] = $error;

        if (!isset(static::$error_counter[$code])) {
            static::$error_counter[$code] = 0;
        }

        static::$error_counter[$code]++;
    }

    /**
     * @param string $default_url
     * @param bool $check_request
     * @return array
     */
    protected static function returnBackRedirect($default_url, $check_request = true)
    {
        /** @var Ajax $ajax */
        $ajax = Tygh::$app['ajax'];

        if ($check_request) {
            $url = isset($_REQUEST['redirect_url']) ? $_REQUEST['redirect_url'] : $default_url;
        } else {
            $url = $default_url;
        }

        if (defined('AJAX_REQUEST')) {
            $ajax->assign('non_ajax_notifications', true);
            $ajax->assign('force_redirection', fn_url($url));
            exit;
        } else {
            return array(CONTROLLER_STATUS_REDIRECT, $url);
        }
    }

    /**
     * Sync ebay objects
     */
    public static function actionSynchronizationObjects()
    {
        $site_id = isset($_REQUEST['site_id']) ? (int) $_REQUEST['site_id'] : null;
        $category_id = !empty($_REQUEST['category_id']) ? (int) $_REQUEST['category_id'] : null;

        if ($site_id === null || !defined('AJAX_REQUEST')) {
            return static::returnBackRedirect('ebay.manage');
        }

        try {
            static::synchronizationObjects($site_id, $category_id);
        } catch(\Exception $e) {
            fn_set_notification('E', __('error'), $e->getMessage());

            return static::returnBackRedirect('ebay.manage', false);
        }

        return static::returnBackRedirect('ebay.manage');
    }

    /**
     * Sync ebay objects
     * @param int $site_id
     * @param int $category_id
     * @return bool
     */
    public static function synchronizationObjects($site_id, $category_id)
    {
        @set_time_limit(600);

        $start_time = fn_get_storage_data('ebay_synchronization_start_time');

        if (!empty($start_time) && $start_time > strtotime('-10 minutes')) {
            $time = time();
            $current_step = fn_get_storage_data('ebay_synchronization_step');
            $count_steps = fn_get_storage_data('ebay_synchronization_step_count');

            fn_set_progress('title', __('ebay_synchronization_title'));
            fn_set_progress('parts', $count_steps);

            for ($i = 1; $i < $current_step; $i++) {
                fn_set_progress('echo', '.');
            }

            fn_set_progress('echo', fn_get_storage_data('ebay_synchronization_step_title'), false);

            while (true) {
                //TODO move logic to same function fn_get_storage_data
                Registry::del('storage_data.ebay_synchronization_step');
                Registry::del('storage_data.ebay_synchronization_step_title');
                $step = fn_get_storage_data('ebay_synchronization_step');

                if (empty($step)) {
                    return true;
                }

                if ($step != $current_step) {
                    fn_set_progress('echo', '.');
                    fn_set_progress('echo', fn_get_storage_data('ebay_synchronization_step_title'), false);
                    $current_step = $step;
                }

                if ($time < strtotime('-10 minutes')) {
                    return true;
                }
                sleep(1);
            }
            return true;
        }

        $objects = fn_ebay_get_objects_needed_synchronization($site_id, $category_id);

        if (!empty($objects)) {
            $current_step = 1;

            register_shutdown_function(function() {
                fn_set_storage_data('ebay_synchronization_start_time', null);
                fn_set_storage_data('ebay_synchronization_step_title', null);
                fn_set_storage_data('ebay_synchronization_step', null);
                fn_set_storage_data('ebay_synchronization_step_count', null);
            });

            fn_set_storage_data('ebay_synchronization_start_time', time());
            fn_set_storage_data('ebay_synchronization_step_count', count($objects));
            fn_set_storage_data('ebay_synchronization_step', $current_step);

            fn_set_progress('title', __('ebay_synchronization_title'));
            fn_set_progress('parts', count($objects));

            if (in_array('Site', $objects)) {
                fn_set_progress('echo', __('ebay_synchronization_regions'), false);
                fn_set_storage_data('ebay_synchronization_step_title', __('ebay_synchronization_regions'));

                Site::synchronization();
                fn_set_storage_data('ebay_synchronization_step', ++$current_step);

                fn_set_progress('echo', '.');
            }

            if (in_array('SiteDetail', $objects)) {
                fn_set_progress('echo', __('ebay_synchronization_region_details'), false);
                fn_set_storage_data('ebay_synchronization_step_title', __('ebay_synchronization_region_details'));

                Site::synchronizationDetail($site_id);
                fn_set_storage_data('ebay_synchronization_step', ++$current_step);

                fn_set_progress('echo', '.');
            }

            if (in_array('Category', $objects)) {
                fn_set_progress('echo', __('ebay_synchronization_categories'), false);
                fn_set_storage_data('ebay_synchronization_step_title', __('ebay_synchronization_categories'));

                Category::synchronization($site_id);
                fn_set_storage_data('ebay_synchronization_step', ++$current_step);

                fn_set_progress('echo', '.');
            }

            if (in_array('Shipping', $objects)) {
                fn_set_progress('echo', __('ebay_synchronization_shipping_services'), false);
                fn_set_storage_data('ebay_synchronization_step_title', __('ebay_synchronization_shipping_services'));

                Shipping::synchronization($site_id);
                fn_set_storage_data('ebay_synchronization_step', ++$current_step);

                fn_set_progress('echo', '.');
            }

            if (in_array('CategoryFeature', $objects)) {
                fn_set_progress('echo', __('ebay_synchronization_category_features'), false);
                fn_set_storage_data('ebay_synchronization_step_title', __('ebay_synchronization_category_features'));

                CategoryFeature::synchronization($site_id, $category_id);
                fn_set_storage_data('ebay_synchronization_step', ++$current_step);

                fn_set_progress('echo', '.');
            }
        }

        return true;
    }

    /**
     * Synchronization Category Feature by Product
     * 
     * @param Product $product
     */
    protected static function synchronizationProductCategoryFeature(Product $product)
    {
        $template = $product->getTemplate();

        if (CategoryFeature::isNeedSynchronization($template->site_id, $product->getExternalCategoryId())) {
            try {
                CategoryFeature::synchronization($template->site_id, $product->getExternalCategoryId());
                $product->reInitProductIdentifiers();
            } catch (\Exception $e) {}
        }
    }
}
