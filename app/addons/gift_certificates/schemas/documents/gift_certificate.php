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

$schema = array(
    'gift_certificate' => array(
        'class' => '\Tygh\Addons\GiftCertificates\Documents\GiftCertificate\Variables\GiftCertificate',
        'arguments' => array('#context', '#config', '@formatter'),
    ),
    'company' => array(
        'class' => '\Tygh\Addons\GiftCertificates\Documents\GiftCertificate\Variables\CompanyVariable',
        'alias' => 'c',
        'email_separator' => '<br/>'
    ),
    'settings' => array(
        'class' => '\Tygh\Template\Document\Variables\SettingsVariable',
    ),
    'runtime' => array(
        'class' => '\Tygh\Template\Document\Variables\RuntimeVariable'
    )
);

return $schema;