<?php

namespace App\Security;

use Doctrine\DBAL\Connection;

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



        if (isset($result)){

            return true;
        }else {
            return false;
        }


    }

}