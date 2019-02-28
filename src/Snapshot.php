<?php

namespace Snapshot;

use Snapshot\Model\Config;
use Snapshot\Model\Server;
use Snapshot\Model\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use RuntimeException;
use PDO;

class Snapshot
{
    protected $output;
    protected $basePath;
    protected $workDir = '/snapshot';
    protected $servers = [];
    protected $storage = [];

    public function __construct($output)
    {
        $this->output = $output;
    }

    public function getWorkDir()
    {
        return $this->workDir;
    }

    public function setWorkDir($workDir)
    {
        $this->workDir = $workDir;
        return $this;
    }


    public function getBasePath()
    {
        return $this->basePath;
    }

    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
        return $this;
    }


    public function addServer(Server $server)
    {
        $this->servers[$server->getName()] = $server;
    }

    public function getServers()
    {
        return $this->servers;
    }

    public function hasServer($name)
    {
        return isset($this->servers[$name]);
    }

    public function getServer($name)
    {
        if (!$this->hasServer($name)) {
            throw new RuntimeException("No such server: " . $name);
        }
        return $this->servers[$name];
    }

    public function addStorage(Storage $storage)
    {
        $this->storages[$storage->getName()] = $storage;
    }

    public function getStorages()
    {
        return $this->storages;
    }

    public function hasStorage($name)
    {
        return isset($this->storages[$name]);
    }

    public function getStorage($name)
    {
        if (!$this->hasStorage($name)) {
            throw new RuntimeException("No such storage: " . $name);
        }
        return $this->storages[$name];
    }

    public function getCommandPath($name)
    {
        $filename = '/usr/local/bin/' . $name;
        if (file_exists($filename)) {
            return $filename;
        }
        $filename = '/usr/bin/' . $name;
        if (file_exists($filename)) {
            return $filename;
        }

        throw new RuntimeException("Can't find path for command " . $name);
    }

    public function create($serverName, $name, $storageName, $storageKey, $rules=[], $inverse = false)
    {

        $this->output->writeLn(
            "Creating database snapshot: <info>" . $serverName . '/' . $name . '</info> to <info>' . $storageName . '</info>'
        );

        if (count($rules)==0) {
            $rules[] = '*';
        } else {
            $this->output->write('Table rules: <info>');
            foreach ($rules as $rule) {
                $this->output->write($rule . ' ');
            }
            if ($inverse) {
                $this->output->write('(inverse)');
            }
            $this->output->writeLn('</info>');
        }

        $timeout = 60*30;
        $server = $this->getServer($serverName);
        $storage = $this->getStorage($storageName);

        $pdo = $server->getPdo();
        $statement = $pdo->prepare(
            "SELECT TABLE_TYPE, TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='" . $name ."';"
        );
        $statement->execute([]);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows)==0) {
            throw new RuntimeException("No such database on server (or without schema): " . $name);
        }
        $tables = [];
        $tableNames = [];
        foreach ($rows as $row) {
            $tableName = $row['TABLE_NAME'];
            $tables[] = $tableName;
            $match = false;
            foreach ($rules as $rule) {
                if (fnmatch($rule, $tableName)) {
                    $match = true;
                }
            }
            if ($row['TABLE_TYPE']=='BASE TABLE') {
                if ($match && !$inverse) {
                    $tableNames[] = $tableName;
                }
                if (!$match && $inverse) {
                    $tableNames[] = $tableName;
                }
            }
        }


        $this->cleanupTmp($server, $name);
        $this->output->write(" * " . date('Ymd H:i') . " <info>" . $server->getName() . '/' . $name . '</info>:');
        $filename = $this->getWorkDir() . '/tmp/' . $server->getName() . '/' . $name . '.sql.gz';
        if (!file_exists(dirname($filename))) {
            mkdir(dirname($filename), 0755, true);
        }

        $cmd = '';
        //$cmd .= '/usr/bin/ionice -c3 ';
        $mysqldump = $this->getCommandPath('mysqldump');

        $cmd .= $mysqldump . ' -f -u ' . $server->getUsername() . ' -p' . $server->getPassword();
        $cmd .= ' -h' . $server->getAddress();
        $cmd .= ' --single-transaction';
        $cmd .= ' --triggers --opt --routines';
        //$cmd .= ' --master-data=2';
        //$cmd .= ' --result-file "' . $filename . '"';
        $cmd .= ' ' . $name . '';
        foreach ($tableNames as $tableName) {
            $cmd .= ' ' . $tableName;
        }
        $cmd .= ' | gzip > ' . $filename;
        //echo $cmd . "\n";exit();

        $this->output->write(" [Dump+Compress]");
        $process = new Process($cmd);
        $process->setTimeout($timeout);
        $process->setIdleTimeout($timeout);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $size = filesize($filename);
        if ($size<(1024*1024)) {
            // 1mb
            //throw new RuntimeException("Snapshot suspiciously small: " . $size . " bytes");
        }

        $this->output->write(" [Size: " . round($size / (1024*1024),1) . 'MB]');

        $gpgPassword = $storage->getArgument('gpg_password');
        $this->output->write(" [Encrypt]");
        $gpg = $this->getCommandPath('gpg');
        $cmd = '';
        $cmd .= 'export HOME=/tmp &&';
        $cmd .= ' echo "' . $gpgPassword . '" | ' . $gpg;
        $cmd .= ' --batch -q --passphrase-fd 0 --cipher-algo AES256 -c "' . $filename . '"';

        $process = new Process($cmd);
        $process->setTimeout($timeout);
        $process->setIdleTimeout($timeout);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        $filename .= '.gpg';

        $s3 = $storage->getS3Client();

        $bucket = $storage->getArgument('bucket');
        $prefix = $storage->getArgument('prefix');

        $this->output->write(" [Upload]");
        $s3->putObject(array(
            'Bucket' => $bucket,
            'Key'    => $prefix . $storageKey,
            'Body'   => fopen($filename, 'r+')
        ));
        $this->output->writeln(" <info>Success</info>");
        $this->cleanupTmp($server, $name);
        return [
            'size' => $size
        ];
    }

    public function download($storageName, $key, $filename)
    {
        $timeout = 60*30;
        $storage = $this->getStorage($storageName);
        if (!file_exists(dirname($filename))) {
            mkdir(dirname($filename), 0755, true);
        }
        if (!file_exists(dirname($filename))) {
            throw new RuntimeException("Failed to create working directory: " . dirname($filename));
        }

        $s3 = $storage->getS3Client();

        $bucket = $storage->getArgument('bucket');
        $prefix = $storage->getArgument('prefix');

        $tmpFilename = $filename . '.gpg';

        $this->output->write(" [Download]");
        $res = $s3->getObject(array(
            'Bucket' => $bucket,
            'Key'    => $prefix . $key . '.sql.gz.gpg',
            'SaveAs'   => $tmpFilename
        ));


        $gpgPassword = $storage->getArgument('gpg_password');
        $this->output->write(" [Decrypt]");
        $gpg = $this->getCommandPath('gpg');
        $cmd = '';
        $cmd .= 'export HOME=/tmp &&';
        $cmd .= ' echo "' . $gpgPassword . '" | ' . $gpg;
        $cmd .= ' --batch --no-tty -q --passphrase-fd 0 --decrypt "' . $tmpFilename . '" > "' . $filename . '"';

        $process = new Process($cmd);
        $process->setTimeout($timeout);
        $process->setIdleTimeout($timeout);
        $process->run();
        unlink($tmpFilename);
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        $this->output->writeln(" <info>Success</info>");
        return true;
    }

    public function delete($storageName, $key)
    {
        $storage = $this->getStorage($storageName);

        $s3 = $storage->getS3Client();

        $bucket = $storage->getArgument('bucket');
        $prefix = $storage->getArgument('prefix');

        $this->output->write(" [Delete]");
        $res = $s3->deleteObject(array(
            'Bucket' => $bucket,
            'Key'    => $prefix . $key . '.sql.gz.gpg',
        ));

        $this->output->writeln(" <info>Success</info>");
        return true;
    }


    public function restore($storageName, $serverName, $key, $name = null)
    {
        $timeout = 60*30;
        $server = $this->getServer($serverName);

        if (!$name) {
            $part = explode('/', $key);
            if (count($part)!=3) {
                throw new RuntimeException("Invalid key format: " . $key);
            }
            $name = $part[2];
        }
        $this->cleanupTmp($server, $name);

        $filename = $this->getWorkDir() . '/tmp/' . $server->getName() . '/' . $name . '.sql.gz';
        $this->download($storageName, $key, $filename);

        $pdo = $server->getPdo();

        /*
        $this->output->write(" [Drop]");
        $statement = $pdo->prepare('DROP DATABASE ' . $name);
        $statement->execute([]);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        */

        $this->output->write(" [Create]");
        $statement = $pdo->prepare('CREATE DATABASE ' . $name);
        $statement->execute([]);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        $cmd = '';
        $mysql = $this->getCommandPath('mysql');

        $cmd .= 'pv -n -i 30 ' . $filename . ' | gunzip | ';

        $cmd .= $mysql . ' -u ' . $server->getUsername() . ' -p' . $server->getPassword();
        $cmd .= ' -h' . $server->getAddress();
        $cmd .= ' ' . $name;

        $this->output->write(" [Decompress+Importing]");
        $process = new Process($cmd);
        $process->setTimeout($timeout);
        $process->setIdleTimeout($timeout);
        $process->run(function ($type, $buffer) {
            if (Process::OUT === $type) {
                echo "OUT: " . $buffer;
            } else {
                echo "Progress: " . $buffer;
            }
        });
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $this->output->writeln(" <info>Success</info>");
        //$this->cleanupTmp($server, $name);
    }

    public function listSnapshots($storageName, $filter = null)
    {
        $keys = [];
        $storage = $this->getStorage($storageName);
        $s3 = $storage->getS3Client();
        $bucket = $storage->getArgument('bucket');
        $prefix = $storage->getArgument('prefix');
        $subPrefix = null;
        if ($filter) {
            $part = explode('*', $filter);
            $subPrefix = $part[0];
        }
        $objects = $s3->getIterator('ListObjects', array('Bucket' => $bucket, 'Prefix' => $prefix . $subPrefix));
        foreach ($objects as $object) {
            $key = $object['Key'];
            $key = substr($key, strlen($prefix));
            $hit = true;
            if ($filter) {
                if (!fnmatch($filter . '.*', $key)) {
                    $hit = false;
                }
            }
            if ($hit) {
                $key = substr($key, 0, -11); // strip postfixes .sql.gz.gpg
                $keys[] = $key;
                $this->output->writeLn($key);
            }
        }
        return $keys;
    }

    public function cleanupTmp(Server $server, $name)
    {
        $filename = $this->getWorkDir() . '/tmp/' . $server->getName() . '/' . $name . '.sql';
        @unlink($filename);
        $filename .= '.gz';
        @unlink($filename);
        $filename .= '.gpg';
        @unlink($filename);
    }
}
