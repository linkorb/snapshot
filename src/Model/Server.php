<?php

namespace Snapshot\Model;

use PDO;

class Server
{
    protected $name;
    protected $address;
    protected $port = 3306;
    protected $username;
    protected $password;
    
    public function getName()
    {
        return $this->name;
    }
    
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    
    public function getAddress()
    {
        return $this->address;
    }
    
    public function setAddress($address)
    {
        $this->address = $address;
        return $this;
    }
    
    public function getPort()
    {
        return $this->port;
    }
    
    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }
    
    public function getUsername()
    {
        return $this->username;
    }
    
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }
    
    public function getPassword()
    {
        return $this->password;
    }
    
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }
    
    public function getPdo()
    {
        $pdo = new PDO(
            'mysql:host=' . $this->getAddress() . ';port=' . $this->getPort(),
            $this->getUsername(),
            $this->getPassword()
        );
        return $pdo;
    }
    
    public function getDatabaseNames()
    {
        $pdo = $this->getPdo();
        $statement = $pdo->prepare('SHOW DATABASES');
        $statement->execute([]);
        $res = [];
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $name = (string)$row['Database'];
            switch ($name) {
                case 'information_schema':
                case 'mysql':
                    break;
                default:
                    $res[] = $name;
            }
        }
        return $res;
    }
}
