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
        $mesi = array("Janvier","Février","Mars","Avril","Mai","Juin","Juillet","Aout","Septembre","Octobre","Novembre","Decembre");

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

        return "- " .round($Nombre * 100 / $Total);
    }

}