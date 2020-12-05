<?php
/**
 * SynchronizeCommand.php
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT license, which
 * is bundled with this package in the file LICENSE.txt.
 *
 * It is also available on the Internet at the following URL:
 * https://docs.auroraextensions.com/magento/extensions/2.x/googlecloudstorage/LICENSE.txt
 *
 * @package       AuroraExtensions\GoogleCloudStorage\Console\Command
 * @copyright     Copyright (C) 2019 Aurora Extensions <support@auroraextensions.com>
 * @license       MIT
 */
declare(strict_types=1);

namespace AuroraExtensions\GoogleCloudStorage\Console\Command;

use Exception;
use AuroraExtensions\GoogleCloudStorage\Api\StorageTypeMetadataInterface;
use Magento\Framework\{
    App\Area,
    App\State
};
use Magento\MediaStorage\{
    Model\File\Storage,
    Model\File\Storage\Flag
};
use Psr\Log\LoggerInterface;
use Symfony\Component\{
    Console\Command\Command,
    Console\Input\InputInterface,
    Console\Output\OutputInterface
};

use function strtotime;
use function time;

class SynchronizeCommand extends Command
{
    /** @constant string COMMAND_NAME */
    private const COMMAND_NAME = 'gcs:media:sync';

    /** @constant string COMMAND_DESC */
    private const COMMAND_DESC = 'Synchronize media storage with Google Cloud Storage.';

    /** @var Storage $fileSync */
    private $fileSync;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var State $state */
    private $state;

    /**
     * @param Storage $fileSync
     * @param LoggerInterface $logger
     * @param State $state
     * @return void
     */
    public function __construct(
        Storage $fileSync,
        LoggerInterface $logger,
        State $state
    ) {
        $this->fileSync = $fileSync;
        $this->logger = $logger;
        $this->state = $state;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription(self::COMMAND_DESC);
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->state->setAreaCode(Area::AREA_ADMINHTML);

            /** @var Flag $flag */
            $flag = $this->fileSync->getSyncFlag();

            if ($flag->getState() === Flag::STATE_RUNNING && $flag->getLastUpdate() && time() <= strtotime($flag->getLastUpdate()) + Flag::FLAG_TTL) {
                return;
            }

            $flag->setState(Flag::STATE_RUNNING)->setFlagData([])->save();

            try {
                $this->fileSync->synchronize([
                    'type' => StorageTypeMetadataInterface::STORAGE_MEDIA_GCS,
                ]);
            } catch (Exception $e) {
                $this->logger->critical($e);
                $flag->passError($e);
            }

            $flag->setState(Flag::STATE_FINISHED)->save();
            $output->writeln('<info>Media synchronized successfully!</info>');
        } catch (Exception $e) {
            $output->writeln('<error>Media synchronization failed!</error>');
        }
    }
}
