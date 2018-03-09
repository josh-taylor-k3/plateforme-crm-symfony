<?php

namespace App\Service;

use Doctrine\DBAL\Driver\Connection;

class DbService
{


    private $connection;
    private $helper;

    public function __construct(Connection $connection, HelperService $helper)
    {
        $this->connection = $connection;
        $this->helper = $helper;

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
        $sql = "SELECT TOP 10 * FROM CENTRALE_ACHAT_v2.dbo.API_USER  WHERE CENTRALE_ACHAT_v2.dbo.API_USER.APP_ID = :id";
        $conn = $this->connection->prepare($sql);
        $conn->bindValue('id', $id);
        $conn->execute();
        return $conn->fetchAll();
    }


    public function getRaisonSocFrs($id)
    {
        $sql = "SELECT FO_RAISONSOC FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS  WHERE FO_ID = :id";
        $conn = $this->connection->prepare($sql);
        $conn->bindValue('id', $id);
        $conn->execute();
        return $conn->fetchAll();
    }

    public function getRaisonSocCl($idClient, $idCentrale)
    {


        $centrale = $this->helper->getCentraleFromId($idCentrale);


        $sql = "SELECT CL_RAISONSOC FROM ".$centrale.".dbo.CLIENTS WHERE CL_ID = :id";
        $conn = $this->connection->prepare($sql);
        $conn->bindValue('id', $idClient);
        $conn->execute();

        return $conn->fetchAll();
    }



}