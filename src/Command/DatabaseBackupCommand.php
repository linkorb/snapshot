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

class DatabaseBackupCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();

        $this
            ->setName('database:backup')
            ->setDescription('Backup all databases on a server')
            ->addArgument(
                'server',
                InputArgument::REQUIRED,
                'Server to backup'
            )
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Database name to backup'
            )
            ->addArgument(
                'storage',
                InputArgument::REQUIRED,
                'Storage to save the backup'
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

        $serverName = $input->getArgument('server');
        $databaseName = $input->getArgument('name');
        $storageName = $input->getArgument('storage');
        
        $output->writeLn(
            "Backing up database: <info>" . $serverName . '/' . $databaseName . '</info> to <info>' . $storageName . '</info>'
        );

        $this->snapshot->backup($serverName, $databaseName, $storageName);
    }
}
