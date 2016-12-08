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
            ->setDescription('Backup a single database')
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
            ->addOption(
                'rules',
                'r',
                InputOption::VALUE_REQUIRED,
                null
            )
            ->addOption(
                'inverse',
                'i',
                InputOption::VALUE_NONE,
                false
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
        $rulesString = $input->getOption('rules');
        $inverse = $input->getOption('inverse');
        if ($rulesString) {
            $rules = explode(',', $rulesString);
        } else {
            $rules = [];
        }

        $storageKey = date('Ymd') . '/' . $databaseName . '_' . date('Hi') . '_' . $serverName  . '.sql.gz.gpg';
        
        $this->snapshot->create($serverName, $databaseName, $storageName, $storageKey, $rules, $inverse);
    }
}
