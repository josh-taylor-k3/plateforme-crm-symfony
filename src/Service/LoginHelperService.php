<?php

namespace App\Service;




use Doctrine\DBAL\Driver\Connection;

class LoginHelperService{




    private $connection;

    private $Databases;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;

        $arrayDatabasesSQL = "SELECT SO_ID, SO_DATABASE FROM CENTRALE_ACHAT.dbo.SOCIETES";

        $conn = $this->connection->prepare($arrayDatabasesSQL);
        $conn->execute();
        $result = $conn->fetchAll(\PDO::FETCH_COLUMN|\PDO::FETCH_GROUP);


        $this->Databases = $result;

    }


    public function isClient($mail, $password){


        foreach ($this->Databases as $key => $value){
            $sqlIsclient = sprintf("SELECT *
                        FROM %s.dbo.CLIENTS_USERS
                        WHERE CC_MAIL = :mail AND CC_PASS = :pwd", $value[0]);

            $conn = $this->connection->prepare($sqlIsclient);
            $conn->bindValue('mail', $mail);
            $conn->bindValue('pwd', $password);
            $conn->execute();
            $resultClient = $conn->fetchAll();

            if (!empty($resultClient)) {
                $tpl = [
                  "data" => $resultClient[0],
                  "centrale" => $key,
                ];

                return $tpl;
            }
        }
        return false;
    }

    public function isFourn($mail, $password){


        $sqlIsFourn = "SELECT * FROM CENTRALE_PRODUITS.dbo.FOURN_USERS WHERE FC_MAIL = :mail AND FC_PASS = :pass";

        $conn = $this->connection->prepare($sqlIsFourn);
        $conn->bindValue('mail', $mail);
        $conn->bindValue('pass', $password);
        $conn->execute();
        $resultFourn = $conn->fetchAll();

        if (!empty($resultFourn)) {
            return $resultFourn[0];
        }else {
            return false;

        }

    }


}