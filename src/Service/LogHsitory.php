<?php

namespace App\Service;

use Doctrine\DBAL\Driver\Connection;

class LogHsitory
{


    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }


    public function logAction($id, $ressource)
    {
        $sql = "INSERT INTO CENTRALE_ACHAT_v2.dbo.API_HISTORIQUE (APP_ID, AH_DATE, AH_RESSOURCE) VALUES (:id, GETDATE(), :ressource)";


        $conn = $this->connection->prepare($sql);

        $conn->bindValue('id', $id);
        $conn->bindValue('ressource', $ressource);



        $conn->execute();
        $result = $conn->fetchAll();

        return $result;
    }


}