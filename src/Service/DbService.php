<?php

namespace App\Service;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Types\IntegerType;

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

    public function isInCentrale($idClient, $centrale){


        $centrale = $this->helper->getCentraleFromId($centrale);


        $sql = "SELECT CL_ID FROM ".$centrale.".dbo.CLIENTS WHERE CL_ID = :id";
        $conn = $this->connection->prepare($sql);
        $conn->bindValue('id', $idClient);
        $conn->execute();
        $result = $conn->fetchAll();

        return !empty($result) ? true : false;

    }


    public function getTicketsForFrs($idTicket){

        $sql = "SELECT SO_ID FROM CENTRALE_ACHAT.dbo.Vue_All_Tickets
                        WHERE ME_ID = :id";
        $conn = $this->connection->prepare($sql);
        $conn->bindValue('id', $idTicket );
        $conn->execute();
        $idCentrale = $conn->fetchAll()[0];


        $centrale = $this->helper->getCentraleFromId($idCentrale['SO_ID']);


        $sqlTicket = "SELECT * FROM ".$centrale.".dbo.MESSAGE_ENTETE WHERE ME_ID = :id";
        $connTickets = $this->connection->prepare($sqlTicket);
        $connTickets->bindValue('id', $idTicket );
        $connTickets->execute();
        $ticket = $connTickets->fetchAll()[0];


        $data = [
            "ticket" => $ticket,
            "centrale" => $centrale
        ];

        return $data;
    }


}