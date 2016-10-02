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

class SnapshotListCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();

        $this
            ->setName('snapshot:list')
            ->setDescription('Backup all databases on a server')
            ->addArgument(
                'storage',
                InputArgument::REQUIRED,
                'Storage to save the backup'
            )
            ->addArgument(
                'filter',
                InputArgument::OPTIONAL,
                'Filter'
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
        $filter = $input->getArgument('filter');
        
        $output->writeLn(
            "Listing snapshots in: <info>" . $storageName . '</info>'
        );

        $this->snapshot->listSnapshots($storageName, $filter);
    }
}
