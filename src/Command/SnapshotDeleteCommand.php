<?php

namespace Snapshot\Command;

use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Snapshot\Loader\YamlLoader;
use Snapshot\Snapshot;
use RuntimeException;

class SnapshotDeleteCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();

        $this
            ->setName('snapshot:delete')
            ->setDescription('Deletes a snapshot from storage')
            ->addArgument(
                'storage',
                InputArgument::REQUIRED,
                'Storage to save the backup'
            )
            ->addArgument(
                'key',
                InputArgument::REQUIRED,
                'Key'
            )
            ->addOption(
                'config',
                'c',
                InputOption::VALUE_REQUIRED,
                null
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->init($input, $output);

        $storageName = $input->getArgument('storage');
        $key = $input->getArgument('key');

        $output->writeLn(
            "Deleting: <info>" . $key . "</info> from <info>" . $storageName . "</info>"
        );

        $this->snapshot->delete($storageName, $key);
    }
}
