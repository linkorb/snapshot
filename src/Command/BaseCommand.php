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

abstract class BaseCommand extends Command
{
    protected $snapshot;
    
    protected function init($input, $output)
    {
        $configPath = $input->getOption('config');
        if (!$configPath) {
            $configPath = getcwd() . '/snapshot.yml';
            if (!file_exists($configPath)) {
                $configPath = '/etc/snapshot.yml';
            }
        }
        if (!file_exists($configPath)) {
            throw new RuntimeException("Can't find config file: " . $configPath);
        }
        $loader = new YamlLoader($output);
        $this->snapshot = $loader->loadFile($configPath);
    }
}
