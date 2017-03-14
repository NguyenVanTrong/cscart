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


namespace Tygh\Template\Document\Variables;


use Tygh\Template\IActiveVariable;

/**
 * The class of the `company` variable; it allows access to company data in the document editor.
 *
 * @package Tygh\Template\Document\Variables
 */
class CompanyVariable implements IActiveVariable, \ArrayAccess
{
    /** @var array */
    public $logos = array();

    /** @var string */
    public $storefront_url;

    public $logo_mail = array();

    /** @var array */
    protected $data = array();

    /** @var array  */
    protected $config = array();

    /**
     * CompanyVariable constructor.
     *
     * @param array     $config     Variable config.
     * @param int       $company_id Company identifier.
     * @param string    $lang_code  Language code.
     */
    public function __construct($config, $company_id, $lang_code)
    {
        $this->data = fn_get_company_placement_info($company_id, $lang_code);
        $this->logos = fn_get_logos($company_id);

        if (fn_allowed_for('ULTIMATE')) {
            $this->storefront_url = fn_url('?company_id=' . $company_id, 'C', 'http');
        } else {
            $this->storefront_url = fn_url('', 'C', 'http');
        }

        if (!empty($this->logos['mail']['image'])) {
            $this->logo_mail = array(
                'path' => $this->logos['mail']['image']['image_path'],
                'alt' => $this->logos['mail']['image']['alt'],
                'height' => $this->logos['mail']['image']['image_y'],
                'width' => $this->logos['mail']['image']['image_x'],
            );
        }
        $this->config = $config;

        $email_fields = isset($this->config['email_fields'])
            ? $this->config['email_fields']
            : array(
                'company_users_department', 'company_site_administrator', 'company_orders_department',
                'company_support_department', 'company_newsletter_email'
            );

        foreach ($email_fields as $field) {
            $this->data[$field . '_display'] = strtr($this->data[$field], array(',' => $this->config['email_separator'], ' ' => ''));
        }
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        $offset = $this->normalizeOffset($offset);

        return isset($this->data[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        $offset = $this->normalizeOffset($offset);

        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        $offset = $this->normalizeOffset($offset);
        $this->data[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        $offset = $this->normalizeOffset($offset);
        unset($this->data[$offset]);
    }

    /**
     * @param string $offset
     * @return string
     */
    protected function normalizeOffset($offset)
    {
        if (strpos($offset, 'default_') === 0) {
            $offset = 'company_default_' . substr($offset, 8);
        } else {
            $offset = 'company_' . $offset;
        }

        return $offset;
    }

    /**
     * @inheritDoc
     */
    public static function attributes()
    {
        return array(
            'name', 'address', 'city', 'country', 'country_descr', 'state', 'state_descr', 'zipcode', 'phone', 'phone_2',
            'fax', 'website', 'storefront_url', 'start_year', 'users_department', 'site_administrator', 'orders_department',
            'support_department', 'newsletter_email', 'users_department_display', 'site_administrator_display', 'orders_department_display',
            'support_department_display', 'newsletter_email_display',
            'logo_mail' => array(
                'path', 'alt', 'width', 'height'
            ),
            'logos' => array(
                'theme' => array(
                    'logo_id', 'layout_id', 'style_id', 'type',
                    'image' => array(
                        'image_path', 'alt', 'image_x', 'image_y',
                        'http_image_path', 'https_image_path', 'absolute_path',
                        'relative_path'
                    )
                ),
                'mail' => array(
                    'logo_id', 'layout_id', 'style_id', 'type',
                    'image' => array(
                        'image_path', 'alt', 'image_x', 'image_y',
                        'http_image_path', 'https_image_path', 'absolute_path',
                        'relative_path'
                    )
                ),
                'favicon' => array(
                    'logo_id', 'layout_id', 'style_id', 'type',
                    'image' => array(
                        'image_path', 'alt', 'image_x', 'image_y',
                        'http_image_path', 'https_image_path', 'absolute_path',
                        'relative_path'
                    )
                )
            ),
        );
    }
}