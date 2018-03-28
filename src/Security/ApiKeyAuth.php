<?php

namespace App\Security;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
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

        $sql = "SELECT * FROM CENTRALE_ACHAT_v2.dbo.API_USER WHERE AU_SECRET = :api_key";
        $conn = $this->connection->prepare($sql);
        $conn->bindValue('api_key', $key);
        try {
            $conn->execute();
        } catch (DBALException $e) {
            dump($e);
        }
        $result = $conn->fetchAll();



        if (!empty($result)){
            if ($result[0]['AU_PROFIL'] == "CENTRALE"){
                $data = [
                    "success" => "true",
                    "profil" => $result[0]['AU_PROFIL'],
                    "centrale" => $result[0]['AU_DATABASE'],
                ];

                return $data;
            }elseif ($result[0]['AU_PROFIL'] == "FOURNISSEUR"){
                $data = [
                    "success" => "true",
                    "profil" => $result[0]['AU_PROFIL'],
                    "fo_id" => $result[0]['FO_ID'],
                ];
                return $data;
            }
        }else {
            dump($result);
            throw new Exception("Mauvaise clé api", 500);
        }







    }

}