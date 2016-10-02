<?php

namespace Snapshot\Loader;

use Snapshot\Snapshot;
use Snapshot\Model\Server;
use Snapshot\Model\Storage;
use Symfony\Component\Yaml\Parser;

use RuntimeException;

class YamlLoader
{
    protected $output;
    public function __construct($output)
    {
        $this->output = $output;
    }
    
    public function loadFile($filename)
    {
        if (!file_exists($filename)) {
            throw new RuntimeException("File not found: " . $filename);
        }
        $yaml = file_get_contents($filename);
        $parser = new Parser();
        $data = $parser->parse($yaml);
        if (!$data) {
            throw new RuntimeException("Failed to load project (yaml error): " . $filename);
        }
        $basePath = dirname($filename);
        return $this->load($data, $basePath);
    }
    
    public function load($data, $basePath)
    {
        $snapshot = new Snapshot($this->output);
        $snapshot->setBasePath($basePath);
        if (isset($data['workdir'])) {
            $snapshot->setWorkDir($data['workdir']);
        }
        foreach ($data['servers'] as $serverName => $serverData) {
            $server = new Server();
            $server->setName($serverName);
            $server->setPassword($serverData['password']);
            $server->setUsername($serverData['username']);
            $server->setAddress($serverData['address']);
            $snapshot->addServer($server);
        }


        foreach ($data['storage'] as $storageName => $storageData) {
            $storage = new Storage();
            $storage->setName($storageName);
            $storage->setType($storageData['type']);
            $storage->setArguments($storageData);
            $snapshot->addStorage($storage);
        }
        
        return $snapshot;
    }
}
