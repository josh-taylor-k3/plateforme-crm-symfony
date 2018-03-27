<?php

namespace App\Service;

use Doctrine\DBAL\Driver\Connection;

class HelperService
{


    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Encode array to utf8 recursively
     * @param $dat
     * @return array|string
     */
    public function array_utf8_encode($dat)
    {
        if (is_string($dat))
            return utf8_encode($dat);
        if (!is_array($dat))
            return $dat;
        $ret = array();
        foreach ($dat as $i => $d)
            $ret[$i] = self::array_utf8_encode($d);
        return $ret;
    }

    /**
     * @param $length
     * @return string
     */
    public function getToken($length){
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet.= "0123456789";
        $max = strlen($codeAlphabet); // edited

        for ($i=0; $i < $length; $i++) {
            $token .= $codeAlphabet[random_int(0, $max-1)];
        }

        return $token;
    }



    public function getIdFromApiKey($key)
    {
        $sql = "SELECT APP_ID FROM CENTRALE_ACHAT_v2.dbo.API_USER WHERE AU_SECRET = :key";


        $conn = $this->connection->prepare($sql);

        $conn->bindValue('key', $key);



        $conn->execute();
        $result[0] = $conn->fetchAll();

        return $result[0];

    }


    public function getCentraleFromId($id)
    {
        switch ($id){

            case 1:
                return "CENTRALE_ACHAT";
                break;
            case 2:
                return "CENTRALE_GCCP";
                break;
            case 3:
                return "CENTRALE_PROMUCF";
                break;
            case 4:
                return "CENTRALE_FUNECAP";
                break;
            case 5:
                return "CENTRALE_PFPL";
                break;
            case 6:
                return "CENTRALE_ROC_ECLERC";
                break;

        }
    }

}