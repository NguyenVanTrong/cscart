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


namespace Tygh\Addons\GiftCertificates\Documents\GiftCertificate\Variables;

use Tygh\Addons\GiftCertificates\Documents\GiftCertificate\Context;

/**
 * Class CompanyVariable
 * @package Tygh\Addons\GiftCertificates\Documents\GiftCertificate\Variables
 */
class CompanyVariable extends \Tygh\Template\Document\Variables\CompanyVariable
{
    public function __construct(Context $context, array $config = array())
    {
        $gift_certificate = $context->getCertificateData();
        $company_id = isset($gift_certificate['company_id']) ? $gift_certificate['company_id'] : 0;

        parent::__construct($config, $company_id, $context->getLangCode());
    }
}