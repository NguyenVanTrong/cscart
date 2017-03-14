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

namespace Tygh\UpgradeCenter\Validators;

/**
 * Upgrade validators interface
 */
interface IValidator
{
    /**
     * Validates specified data by schema
     *
     * @param  array $schema  Incoming validator schema
     * @param  array $request Request data
     * @return array Validation result and Data to be displayed
     */
    public function check($schema, $request);

    /**
     * Gets validator name (ID)
     *
     * @return string Name
     */
    public function getName();
}
