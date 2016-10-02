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

class ServerBackupCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();

        $this
            ->setName('server:backup')
            ->setDescription('Backup all databases on a server')
            ->addArgument(
                'server',
                InputArgument::REQUIRED,
                'Server to backup'
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
        $storageName = $input->getArgument('storage');
        
        $output->writeLn(
            "Backing up server: <info>" . $serverName . '</info> to <info>' . $storageName . '</info>'
        );

        $server = $this->snapshot->getServer($serverName);
        $pdo = $server->getPdo();
        $databaseNames = $server->getDatabaseNames();
        
        foreach ($databaseNames as $databaseName) {
            $this->snapshot->backup($serverName, $databaseName, $storageName);
        }
    }
}
