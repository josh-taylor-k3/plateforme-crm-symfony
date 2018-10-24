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


    public function getFournFromUser($fc_id){

        $sql = "SELECT FO_ID FROM CENTRALE_PRODUITS.dbo.FOURN_USERS WHERE FC_ID = :fc_id";


        $conn = $this->connection->prepare($sql);

        $conn->bindValue('fc_id', $fc_id);

        $conn->execute();
        $result = $conn->fetchAll();

        return $result[0];


    }

    /*
     * Returns an array with the $number of specified month
     */
    public function getArrayOfMonth($number){


        $monthArraySource = ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Decembre"];


        return array_slice($monthArraySource, 0, $number);




    }

    public function get_months($date1, $date2) {
        $time1  = strtotime($date1);
        $time2  = strtotime($date2);
        $my     = date('n-Y', $time2);
        $mesi = array("Janv","Févr","Mars","Avr","Mai","Juin","Juill","Août","Sept","Oct","Nov","Déc");

        //$months = array(date('F', $time1));
        $months = array();
        $f      = '';

        while($time1 < $time2) {
            if(date('n-Y', $time1) != $f) {
                $f = date('n-Y', $time1);
                if(date('n-Y', $time1) != $my && ($time1 < $time2)) {
                    $str_mese=$mesi[(date('n', $time1)-1)];
                    $months[] = $str_mese;
                }
            }
            $time1 = strtotime((date('Y-n-d', $time1).' +15days'));
        }

        $str_mese=$mesi[(date('n', $time2)-1)];
        $months[] = $str_mese;
        return $months;
    }

    public function Pourcentage($Nombre, $Total) {

        if($Total == 0){

            return 0;
        }

        return "-" .round($Nombre * 100 / $Total);
    }

    public function getCentrale($so_id)
    {

        $sql = "SELECT SO_DATABASE FROM CENTRALE_ACHAT.dbo.SOCIETES WHERE SO_ID = :id";


        $conn = $this->connection->prepare($sql);
        $conn->bindValue(':id', $so_id);
        $conn->execute();
        $result = $conn->fetchAll();


        return $result[0];
    }

    public function setTokenApp($so_database, $cc_id, $token)
    {

       if ($so_database == "CENTRALE_PRODUITS"){
           $sqlUpdateToken = "UPDATE CENTRALE_PRODUITS.dbo.FOURN_USERS
                                      SET FC_TOKEN_APP = :token, MAJ_DATE = GETDATE(), MAJ_USER = 'APP_TOKEN'
                                      WHERE FC_ID = :fc_id";

           $conn = $this->connection->prepare($sqlUpdateToken);
           $conn->bindValue('token', $token);
           $conn->bindValue('fc_id', $cc_id);
           $conn->execute();
           $result = $conn->fetchAll();

           return $result;


       }else {
           $sqlInsert = sprintf("UPDATE %s.dbo.CLIENTS_USERS
                                      SET CC_TOKEN_APP = :token, MAJ_DATE = GETDATE(), MAJ_USER = 'APP_TOKEN'
                                      WHERE CC_ID = :cc_id", $so_database);

           $conn = $this->connection->prepare($sqlInsert);
           $conn->bindValue('token', $token);
           $conn->bindValue('cc_id', $cc_id);
           $conn->execute();
           $result = $conn->fetchAll();

           return $result;
       }

    }

    public function getBaseUrl($centrale)
    {

        $sqlInsert = "SELECT SO_WEB FROM CENTRALE_ACHAT.dbo.SOCIETES WHERE SO_DATABASE = :database";

        $conn = $this->connection->prepare($sqlInsert);
        $conn->bindValue("database", $centrale);
        $conn->execute();
        $result = $conn->fetchAll();



        $base_url = $result[0]["SO_WEB"];

        if ($base_url){

            return $base_url;
        }else {

            return "";
        }

    }

    public function verifyTokenApp($token, $database)
    {


        $sqlTokenApp = sprintf("SELECT CL_ID, CC_ID FROM %s.dbo.CLIENTS_USERS WHERE CC_TOKEN_APP = :token", $database);

        $conn = $this->connection->prepare($sqlTokenApp);
        $conn->bindValue('token', $token);
        $conn->execute();
        $result = $conn->fetchAll();


        if (empty($result)){


            $sqlTokenAppFourn = "SELECT FC_ID, FO_ID FROM CENTRALE_PRODUITS.dbo.FOURN_USERS WHERE FC_TOKEN_APP = :token";

            $conn = $this->connection->prepare($sqlTokenAppFourn);
            $conn->bindValue('token', $token);
            $conn->execute();
            $resultFourn = $conn->fetchAll();

            if (empty($resultFourn)) {
                return false;
            }else {
                return $resultFourn[0]["FC_ID"];

            }


        }
        return $result[0]["CC_ID"];


    }

    public function extractTokenDb($token){

        $tiretPosition = strpos($token, "-");

        $centrale = substr($token, 0, $tiretPosition);
        $token_propre = substr($token, $tiretPosition+1, strlen($token));

        if (!isset($centrale)){
            return false;
        }

        $tpl = [
            "database" => $centrale,
            "token" =>$token_propre
        ];


        return $tpl;
    }

    public function gen_uuid() {
        return sprintf( '%04x%04x%04x%04x%04x%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    public function getTypeMessage($client, $fournisseur){


        if ($client !== null && $fournisseur == null){

            return "client";
        }else if ($fournisseur !== null && $client == null){

            return "fournisseur";
        }

    }


}