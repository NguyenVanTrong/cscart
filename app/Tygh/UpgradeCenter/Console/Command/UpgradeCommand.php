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


namespace Tygh\UpgradeCenter\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Tygh\UpgradeCenter\Console\Question\ConfirmationQuestion;
use Tygh\UpgradeCenter\App;
use Tygh\UpgradeCenter\Log;
use Tygh\UpgradeCenter\Validators\IValidator;

/**
 * Class UpgradeCommand is a command that allows store upgrade via cli.
 *
 * @package Tygh\UpgradeCenter\Console\Command
 */
class UpgradeCommand extends Command
{
    /** @var App An Upgrade center application instance. */
    private $upgrade_app;

    /** @var null|array List of available upgrade packages. */
    private $upgrade_packages;

    /** @var OutputInterface An OutputInterface instance. */
    private $output;

    /** @var InputInterface An InputInterface instance. */
    private $input;

    /**
     * UpgradeCommand constructor.
     *
     * @param App $upgrade_app  An Upgrade center application instance.
     */
    public function __construct(App $upgrade_app)
    {
        $this->upgrade_app = $upgrade_app;
        $this->upgrade_app->output_enabled = false;

        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('upgrade')
            ->setDefinition(array(
                new InputArgument('id', InputArgument::OPTIONAL, "Identifier of the upgrade package (core, addon_name)"),
                new InputOption('no-backup', null, InputOption::VALUE_NONE, "Do not create a backup"),
                new InputOption('skip-validator', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, "Skip validator (collisions, restore, permissions, etc)"),
            ))
            ->setDescription("Upgrade center")
            ->setHelp(<<<EOF
The <info>%command.name%</info> command runs the store upgrade.

<info>php %command.full_name% core</info> - Run core upgrade.
<info>php %command.full_name% core --no-backup</info> - Run core upgrade without backup.
<info>php %command.full_name% core --no-backup --skip-validator=*</info> - Run core upgrade without backup and skip all validators.
<info>php %command.full_name% core --no-backup --skip-validator=collisions --skip-validator=restore</info> - Run core upgrade without backup and skip collision and restore validators.
EOF
            )
        ;
    }

    /**
     * @inheritDoc
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $upgrade_packages = $this->getUpgradePackages();

        if (empty($upgrade_packages)) {
            return;
        }

        /** @var QuestionHelper $question_helper */
        $question_helper = $this->getHelper('question');

        $id = $input->getArgument('id');
        $no_backup = $input->getOption('no-backup');

        if (!$id) {
            $this->renderUpgradePackagesTable($output);

            $input->setArgument(
                'id',
                $question_helper->ask($input, $output, $this->getChoiceUpgradePackageQuestion())
            );
        }

        if (!$no_backup) {
            $input->setOption(
                'no-backup',
                !$question_helper->ask($input, $output, $this->getConfirmationCreateBackupQuestion())
            );
        }
    }

    /**
     * @inheritdoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id = $input->getArgument('id');

        if (empty($id)) {
            $output->writeln('<error>Identifier of the upgrade package is not defined</error>');
            return 1;
        }

        $upgrade_packages = $this->getUpgradePackages();

        if (empty($upgrade_packages)) {
            $output->writeln('<error>No upgrades are currently available</error>');
            return 1;
        }

        $upgrade_package = $this->findUpgradePackage($id);

        if (empty($upgrade_package)) {
            $output->writeln('<error>Upgrade package not found</error>');
            return 1;
        }

        $this->upgrade_app->perform_backup = !$input->getOption('no-backup');
        $this->upgrade_app->validator_callback = array($this, 'upgradeCenterValidatorCallback');

        if ($this->upgrade_app->downloadPackage($id)) {
            list($result) = $this->upgrade_app->install($id, array());

            if ($result === App::PACKAGE_INSTALL_RESULT_SUCCESS) {
                $output->writeln('<info>Successful</info>');
                return 0;
            }
        }

        $output->writeln('<error>Upgrade process of your store has failed</error>');
        return 1;
    }

    /**
     * Gets available upgrade packages.
     *
     * @return array
     */
    private function getUpgradePackages()
    {
        if ($this->upgrade_packages === null) {
            $this->upgrade_app->clearDownloadedPackages();
            $this->upgrade_app->checkUpgrades(false);

            $this->upgrade_packages = $this->upgrade_app->getPackagesList();
        }

        return $this->upgrade_packages;
    }

    /**
     * Gets the identifiers of available upgrade packages
     *
     * @return array
     */
    private function getUpgradePackagesIds()
    {
        $ids = array();
        $upgrade_packages = $this->getUpgradePackages();

        foreach ($upgrade_packages as $items) {
            $ids = array_merge($ids, array_keys($items));
        }

        return $ids;
    }

    /**
     * Finds an upgrade package by identifier.
     *
     * @param string $id Upgrade package identifier (core, addon_name).
     *
     * @return null|array
     */
    private function findUpgradePackage($id)
    {
        if (empty($id)) {
            return null;
        }

        $upgrade_packages = $this->getUpgradePackages();

        $type = $id === 'core' ? 'core' : 'addon';

        return isset($upgrade_packages[$type][$id]) ? $upgrade_packages[$type][$id] : null;
    }

    /**
     * Gets the question about selecting an upgrade package.
     *
     * @return ChoiceQuestion
     */
    private function getChoiceUpgradePackageQuestion()
    {
        return new ChoiceQuestion('<info>Select an upgrade package</info>', $this->getUpgradePackagesIds());
    }

    /**
     * Gets question of the create backup confirmation.
     *
     * @return ConfirmationQuestion
     */
    private function getConfirmationCreateBackupQuestion()
    {
        return new ConfirmationQuestion('<info>Do you want to create a backup?</info> [<comment>Y,n</comment>]: ', true);
    }

    /**
     * Gets question of the skip validator confirmation.
     *
     * @param string $validator_name Validator name.
     *
     * @return ConfirmationQuestion
     */
    private function getConfirmationSkipValidatorQuestion($validator_name)
    {
        return new ConfirmationQuestion(
            sprintf('<info>Do you want to skip validator %s and continue?</info> [<comment>Y,n</comment>]: ', $validator_name),
            true
        );
    }

    /**
     * Render the list of the available upgrade packages.
     *
     * @param OutputInterface $output An OutputInterface instance.
     */
    private function renderUpgradePackagesTable(OutputInterface $output)
    {
        $rows = array();
        $upgrade_packages = $this->getUpgradePackages();

        foreach ($upgrade_packages as $type => $items) {
            foreach ($items as $id => $item) {
                $rows[] = array(
                    $id,
                    $item['name'],
                    $item['to_version'],
                    fn_date_format($item['timestamp']),
                );
            }
        }

        $table = new Table($output);
        $table
            ->setHeaders(array('id', 'name', 'new version', 'release date'))
            ->addRows($rows)
            ->render();
    }

    /**
     * Callback function for the upgrade package validation process.
     *
     * @param IValidator    $validator  An IValidator instance.
     * @param bool          $result     Validation result flag.
     * @param array         $data       Validation notices.
     * @param Log           $logger     An Log instance.
     *
     * @return array
     */
    public function upgradeCenterValidatorCallback(IValidator $validator, $result, $data, Log $logger)
    {
        if ($result) {
            return array($result, $data);
        }

        /** @var QuestionHelper $question_helper */
        $question_helper = $this->getHelper('question');
        $data = (array) $data;
        $skip = $this->isSkippedValidator($validator);

        $this->output->writeln(sprintf('<info>Validator %s notices:</info>', $validator->getName()));

        $this->renderUpgradeCenterValidatorNotices($validator, $data);

        if (!$skip && $this->input->isInteractive()) {
            $skip = $question_helper->ask(
                $this->input,
                $this->output,
                $this->getConfirmationSkipValidatorQuestion($validator->getName())
            );
        }

        if ($skip) {
            $logger->add(sprintf('Validator %s skipped', $validator->getName()));
            $this->output->writeln(sprintf('<info>Validator %s skipped</info>', $validator->getName()));

            return array(true, array());
        }

        return array($result, $data);
    }

    /**
     * Render list of the validation notices.
     *
     * @param IValidator $validator  An IValidator instance.
     * @param array      $data       List of notice.
     */
    private function renderUpgradeCenterValidatorNotices(IValidator $validator, array $data)
    {
        foreach ($data as $key => $item) {
            if (is_array($item)) {
                $this->renderUpgradeCenterValidatorNotices($validator, $item);
            } else {
                $item = str_replace(array('<br>', '<br />', '<br/>'), "\n", $item);
                $item = strip_tags($item);

                $this->output->writeln($item);
            }
        }
    }

    /**
     * Checks whether or not the validator is skipped.
     *
     * @param IValidator $validator An IValidator instance.
     *
     * @return bool
     */
    private function isSkippedValidator(IValidator $validator)
    {
        $skip_validators = $this->input->getOption('skip-validator');

        return in_array('*', $skip_validators, true) || in_array($validator->getName(), $skip_validators, true);
    }
}