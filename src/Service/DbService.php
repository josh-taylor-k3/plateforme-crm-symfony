<?php

namespace App\Service;

use Doctrine\DBAL\Driver\Connection;

class DbService
{


    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }


    public function getHistoryById($id)
    {
        $sql = "SELECT * FROM CENTRALE_ACHAT_v2.dbo.API_HISTORIQUE WHERE CENTRALE_ACHAT_v2.dbo.API_HISTORIQUE.APP_ID = :id";
        $conn = $this->connection->prepare($sql);
        $conn->bindValue('id', $id);
        $conn->execute();
        return $conn->fetchAll();
    }


    public function getDetailById($id)
    {
        $sql = "SELECT TOP 10 * FROM CENTRALE_ACHAT_v2.dbo.API_USER INNER JOIN CENTRALE_ACHAT_v2.dbo.API_DROITS ON API_USER.APP_ID = API_DROITS.APP_ID WHERE CENTRALE_ACHAT_v2.dbo.API_USER.APP_ID = :id";
        $conn = $this->connection->prepare($sql);
        $conn->bindValue('id', $id);
        $conn->execute();
        return $conn->fetchAll();
    }


    public function setDroitsById($id)
    {


    }

}