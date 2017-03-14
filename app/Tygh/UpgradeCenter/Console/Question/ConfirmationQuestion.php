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


namespace Tygh\UpgradeCenter\Console\Question;

use Symfony\Component\Console\Question\Question;
use InvalidArgumentException;

/**
 * Class ConfirmationQuestion is a yes/no question with answer validation.
 *
 * @package Tygh\UpgradeCenter\Console\Question
 */
class ConfirmationQuestion extends Question
{
    /** Default max answer attempts */
    const DEFAULT_MAX_ATTEMPTS = 5;

    /** @var string The error message for invalid value */
    private $error_message = 'Value "%s" is invalid';

    /**
     * @inheritdoc
     */
    public function __construct($question, $default)
    {
        parent::__construct($question, $default);

        $this->setValidator($this->getDefaultValidator());
        $this->setNormalizer($this->getDefaultNormalizer());
        $this->setMaxAttempts(self::DEFAULT_MAX_ATTEMPTS);
    }

    /**
     * Sets the error message for invalid value.
     *
     * The error message has a string placeholder (%s) for the invalid value.
     *
     * @param string $error_message
     *
     * @return ConfirmationQuestion The current instance
     */
    public function setErrorMessage($error_message)
    {
        $this->error_message = $error_message;

        return $this;
    }

    /**
     * Gets the default answer validator.
     *
     * @return callable
     */
    private function getDefaultValidator()
    {
        $error_message = $this->error_message;

        return function ($answer) use ($error_message) {
            if ($answer === 'y') {
                return true;
            } elseif ($answer === 'n') {
                return false;
            }

            throw new InvalidArgumentException(sprintf($error_message, $answer));
        };
    }

    /**
     * Gets the default answer normalizer.
     *
     * @return callable
     */
    private function getDefaultNormalizer()
    {
        $default = $this->getDefault();

        return function ($answer) use ($default) {
            if (is_bool($answer)) {
                return $answer;
            }

            return strtolower($answer);
        };
    }
}