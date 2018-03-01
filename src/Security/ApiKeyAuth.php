<?php

namespace App\Security;

use Doctrine\DBAL\Connection;
use Symfony\Component\Config\Definition\Exception\Exception;

class ApiKeyAuth
{


    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }


    public function grant($key)
    {


        $sql = "SELECT APP_ID FROM CENTRALE_ACHAT_v2.dbo.API_USER WHERE AU_SECRET = :api_key";

        $conn = $this->connection->prepare($sql);

        $conn->bindValue('api_key', $key);

        $conn->execute();
        $result = $conn->fetchAll();

        dump($result);

        if (!empty($result)){
            return true;
        }else {
            throw new Exception("Mauvaise clé api", 500);
        }


    }

}