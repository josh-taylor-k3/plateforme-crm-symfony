<?php

namespace App\Service;




use Doctrine\DBAL\Driver\Connection;

class LoginHelperService{




    private $connection;

    private $Databases;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;

        // requetes pour avoir toutes les bases de données
        $arrayDatabasesSQL = "SELECT SO_ID, SO_DATABASE FROM CENTRALE_ACHAT.dbo.SOCIETES";

        $conn = $this->connection->prepare($arrayDatabasesSQL);
        $conn->execute();
        $result = $conn->fetchAll(\PDO::FETCH_COLUMN|\PDO::FETCH_GROUP);

        $this->Databases = $result;

    }

    public function isClient($mail, $password){



        //On cherche si il y a des occurences pour un client_user dans chaque base de données
        foreach ($this->Databases as $key => $value){

            $sqlIsclient = sprintf("SELECT *
                        FROM %s.dbo.CLIENTS_USERS
                        WHERE CC_MAIL = :mail AND CC_PASS = :pwd", $value[0]);

            $conn = $this->connection->prepare($sqlIsclient);
            $conn->bindValue('mail', $mail);
            $conn->bindValue('pwd', $password);
            $conn->execute();
            $resultClient = $conn->fetchAll();


            // si il y a une occurence on retourne la données du client ainsi que la centrale associé
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


    public function isValideur($mail, $centrale){


        // on cherche a savoir si il y a l'adresse mail, dans la table USERS

        $sqlIsValideur = sprintf("SELECT * FROM %s.dbo.USERS WHERE US_MAIL = :mail ", $centrale);

        $conn = $this->connection->prepare($sqlIsValideur);
        $conn->bindValue('mail', $mail);
        $conn->execute();
        $resultValideur = $conn->fetchAll();

        if (!empty($resultValideur)) {
            return true;
        }else {
            return false;

        }
    }

}