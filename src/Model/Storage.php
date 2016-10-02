<?php

namespace Snapshot\Model;

use Aws\S3\S3Client;

class Storage
{
    protected $name;
    protected $type;
    protected $arguments = [];
    
    public function getName()
    {
        return $this->name;
    }
    
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    
    public function getType()
    {
        return $this->type;
    }
    
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
    
    public function getArguments()
    {
        return $this->arguments;
    }
    
    public function setArguments($arguments)
    {
        $this->arguments = $arguments;
        return $this;
    }
    
    public function getArgument($name)
    {
        if (!isset($this->arguments[$name])) {
            throw new RuntimeException("Missing storage argument: " . $name);
        }
        return $this->arguments[$name];
    }
    
    public function getS3Client()
    {
        $s3 = new S3Client([
            'version' => 'latest',
            'region'  => $this->getArgument('region'),
            'key' => $this->getArgument('access_key'),
            'secret' => $this->getArgument('secret_key')
        ]);
        return $s3;
    }
}
