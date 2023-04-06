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
 * @package     AuroraExtensions\GoogleCloudStorage\Console\Command
 * @copyright   Copyright (C) 2021 Aurora Extensions <support@auroraextensions.com>
 * @license     MIT
 */
declare(strict_types=1);

namespace AuroraExtensions\GoogleCloudStorage\Console\Command;

use AuroraExtensions\GoogleCloudStorage\Api\StorageTypeMetadataInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\MediaStorage\Model\File\Storage;
use Magento\MediaStorage\Model\File\Storage\Flag;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function strtotime;
use function time;

class SynchronizeCommand extends Command
{
    private const COMMAND_NAME = 'gcs:media:sync';
    private const COMMAND_DESC = 'Synchronize media storage with Google Cloud Storage.';

    /** @var State $state */
    private $state;

    /** @var Storage $storage */
    private $storage;

    /** @var LoggerInterface $logger */
    private $logger;

    /**
     * @param State $state
     * @param Storage $storage
     * @param LoggerInterface $logger
     * @return void
     */
    public function __construct(
        State $state,
        Storage $storage,
        LoggerInterface $logger
    ) {
        $this->state = $state;
        $this->storage = $storage;
        $this->logger = $logger;
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
            $flag = $this->storage->getSyncFlag();

            /** @var int|string|null $lastUpdate */
            $lastUpdate = $flag->getLastUpdate() ?: null;

            if ($flag->getState() === Flag::STATE_RUNNING
                && !empty($lastUpdate)
                && time() <= strtotime($lastUpdate) + Flag::FLAG_TTL
            ) {
                return;
            }

            $flag->setState(Flag::STATE_RUNNING)
                 ->setFlagData([])
                 ->save();

            try {
                $this->storage->synchronize([
                    'type' => StorageTypeMetadataInterface::STORAGE_MEDIA_GCS,
                ]);
            } catch (Throwable $e) {
                $this->logger->critical($e);
                $flag->passError($e);
            }

            $flag->setState(Flag::STATE_FINISHED)
                 ->save();
            $output->writeln('<info>Media synchronized successfully!</info>');
        } catch (Throwable $e) {
            $output->writeln('<error>Media synchronization failed!</error>');
        }
    }
}
