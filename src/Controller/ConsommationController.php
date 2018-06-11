<?php

namespace App\Controller;

use App\Service\HelperService;
use Carbon\Carbon;
use Doctrine\DBAL\Driver\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Flex\Response;

class ConsommationController extends Controller
{

    /**
     * @Route("/conso/index", name="conso_index")
     */
    public function consoIndex(Connection $connection){


        $sql = "SELECT FO_RAISONSOC, FO_ID FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS";
        $conn = $connection->prepare($sql);
        $conn->execute();
        $result = $conn->fetchAll();



        return $this->render('conso/index.html.twig', [
            "fournisseur" => $result
        ]);
    }


    /**
     * @Route("/consommation/{id}/{start}/{end}/", name="consommation_client")
     */
    public function consoClient(Connection $connection, HelperService $helper,$id, $start, $end)
    {



        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: true ");
        header("Access-Control-Allow-Headers: Content-Type, Depth, User-Agent, X-File-Size, X-Requested-With, If-Modified-Since, X-File-Name, Cache-Control, X-ac-key");


        $sql = "SELECT DISTINCT CENTRALE_ACHAT.dbo.CLIENTS_CONSO.FO_ID, FO_RAISONSOC FROM CENTRALE_ACHAT.dbo.CLIENTS_CONSO
                INNER JOIN CENTRALE_PRODUITS.dbo.FOURNISSEURS ON CLIENTS_CONSO.FO_ID = FOURNISSEURS.FO_ID";



        $conn = $connection->prepare($sql);
        $conn->execute();
        $listFourn = $conn->fetchAll();


        $arrayFoId = array();
        $arrayRaisonSoc = array();


        foreach ($listFourn as $fourn){
            array_push($arrayFoId, $fourn['FO_ID']);
            array_push($arrayRaisonSoc, $fourn['FO_RAISONSOC']);
        }


        $data = [
            "count" => 0,
            "result" => "ok",
            "Total" => [
                "ca" =>[],
                "economie" => [],
            ],
            "labels" => [],
            "conso" =>[
                "BRUNEAU" =>[
                    "ca" => [],
                    "eco" => [],
                ],
                "TOSHIBA" =>[
                    "ca" => [],
                    "eco" => [],
                ],
            ]
        ];


        foreach ($arrayFoId as $key => $fo_id){

            $sql = "SELECT CLC_ID, CL_ID, CC_ID, FO_ID, CLC_DATE, CLC_PRIX_PUBLIC, CLC_PRIX_CENTRALE, INS_DATE, INS_USER , (
                          case month(CLC_DATE)
                          WHEN 1 THEN 'janvier'
                          WHEN 2 THEN 'février'
                          WHEN 3 THEN 'mars'
                          WHEN 4 THEN 'avril'
                          WHEN 5 THEN 'mai'
                          WHEN 6 THEN 'juin'
                          WHEN 7 THEN 'juillet'
                          WHEN 8 THEN 'août'
                          WHEN 9 THEN 'septembre'
                          WHEN 10 THEN 'octobre'
                          WHEN 11 THEN 'novembre'
                          ELSE 'décembre'
                          end
                        ) as Month
                    FROM CENTRALE_ACHAT.dbo.CLIENTS_CONSO
                    WHERE CLC_DATE BETWEEN :start AND :end
                          AND CL_ID = :id
                          AND FO_ID = :fournisseur";


            $conn = $connection->prepare($sql);
            $conn->bindValue('id', $id);
            $conn->bindValue('fournisseur', $fo_id);
            $conn->bindValue('start', $start);
            $conn->bindValue('end', $end);
            $conn->execute();
            $resultConso = $conn->fetchAll();

            $ca = 0;
            $eco = 0;

            foreach ($resultConso as $ley => $conso){


                array_push($data["conso"][$arrayRaisonSoc[$key]]["ca"], $conso['CLC_PRIX_CENTRALE'] );
                array_push($data["conso"][$arrayRaisonSoc[$key]]["eco"], $conso['CLC_PRIX_PUBLIC'] - $conso['CLC_PRIX_CENTRALE']  );

                if( $key >= 1 ){
                    array_push($data["labels"], $conso['Month']);

                }


            }

            $ca = array_sum($data['conso']['BRUNEAU']['ca']) + array_sum($data['conso']['TOSHIBA']['ca']);
            $eco = array_sum($data['conso']['BRUNEAU']['eco']) + array_sum($data['conso']['TOSHIBA']['eco']);

            $data['Total']["ca"] = $ca;

            $data['Total']["economie"] = $eco;
        }

        return new JsonResponse($data, 200);

    }


    /**
     * @Route("v2/consommation/{id}/{start}/{end}/", name="consommation_client_v2")
     */
    public function consoClientV2(Connection $connection, HelperService $helper, $id, $start, $end)
    {

        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: true ");
        header("Access-Control-Allow-Headers: Content-Type, Depth, User-Agent, X-File-Size, X-Requested-With, If-Modified-Since, X-File-Name, Cache-Control, X-ac-key");


        // on obtient la liste des fournisseurs ayant des conso dans la table conso
        $sqlFourn = "SELECT DISTINCT
                      FO_ID,
                      (SELECT FO_RAISONSOC FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS WHERE CENTRALE_PRODUITS.dbo.FOURNISSEURS.FO_ID = CENTRALE_ACHAT.dbo.CLIENTS_CONSO.FO_ID GROUP BY FO_RAISONSOC) as FO_RAISONSOC
                    FROM CENTRALE_ACHAT.dbo.CLIENTS_CONSO";

        $conn = $connection->prepare($sqlFourn);
        $conn->execute();
        $ListFourn = $conn->fetchAll();

        $data = [
            "count" => count($ListFourn),
            "result" => "ok",
            "Total" => [
                "ca" =>[],
                "economie" => [],
            ],
            "labels" => [],
            "conso" =>[

            ]
        ];


        $ca_total = 0;

        $eco_total = 0;


        foreach ($ListFourn as $key => $fourn){

            $sqlConso = "SELECT
                          CLC_ID,
                          CL_ID,
                          CC_ID,
                          FO_ID,
                          CLC_DATE,
                          CLC_PRIX_PUBLIC,
                          CLC_PRIX_CENTRALE,
                          INS_DATE,
                          INS_USER ,
                          (case month(CLC_DATE)
                                WHEN 1 THEN 'janvier'
                                WHEN 2 THEN 'février'
                                WHEN 3 THEN 'mars'
                                WHEN 4 THEN 'avril'
                                WHEN 5 THEN 'mai'
                                WHEN 6 THEN 'juin'
                                WHEN 7 THEN 'juillet'
                                WHEN 8 THEN 'août'
                                WHEN 9 THEN 'septembre'
                                WHEN 10 THEN 'octobre'
                                WHEN 11 THEN 'novembre'
                                ELSE 'décembre'
                           end) 
                            as Month
                        FROM CENTRALE_ACHAT.dbo.CLIENTS_CONSO
                        WHERE CLC_DATE BETWEEN :start AND :end
                              AND CL_ID = :id
                              AND FO_ID = :fournisseur";


            $conn = $connection->prepare($sqlConso);
            $conn->bindValue('id', $id);
            $conn->bindValue('fournisseur', $fourn['FO_ID']);
            $conn->bindValue('start', $start);
            $conn->bindValue('end', $end);
            $conn->execute();
            $conso = $conn->fetchAll();

            $cons_ca = [];
            $cons_eco = [];

            foreach ($conso as $cons){
                array_push($cons_eco, $cons['CLC_PRIX_PUBLIC'] - $cons["CLC_PRIX_CENTRALE"]);

                $ca_total += $cons["CLC_PRIX_CENTRALE"];
                $eco_total += $cons['CLC_PRIX_PUBLIC'] - $cons["CLC_PRIX_CENTRALE"];
            }

            if(count($ListFourn) ==  0){
                    if (array_sum($cons_ca) == 0 && array_sum($cons_ca) == 0){
                        return new JsonResponse("none", 200);
                    }
            }


            $tpl = Array($fourn['FO_RAISONSOC'] => [

                "id" => $fourn['FO_ID'],
                "CA" => $cons_ca,
                "ECO" => $cons_eco,
                "total_ca" => array_sum($cons_ca),
                "total_eco" => array_sum($cons_eco)
            ]);
            array_push($data["conso"], $tpl);


            $data['Total']['ca'] = $ca_total;
            $data['Total']['economie'] = $eco_total;


        }




        $months = $helper->get_months( $start, $end );


        foreach($months as $mois){


            array_push($data['labels'], $mois);

        }


        return new JsonResponse($data, 200);
    }


    /**
     * @Route("/consommation/years", name="conso_years")
     */
    public function consoYears(Connection $connection, HelperService $helper){



        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: true ");
        header("Access-Control-Allow-Methods: OPTIONS, GET, POST");
        header("Access-Control-Allow-Headers: Content-Type, Depth, User-Agent, Cache-Control");

        $sql = "SELECT DISTINCT year(CLC_DATE) as date
                FROM CENTRALE_ACHAT.dbo.CLIENTS_CONSO
                ORDER BY date desc";



        $conn = $connection->prepare($sql);
        $conn->execute();
        $result = $conn->fetchAll();

        $data = $result;




        return new JsonResponse($data, 200);

    }


    /**
     * @Route("/consommation/years/{id}/{year}", name="consommation_client_year")
     */
    public function consoForTheYear(Connection $connection, HelperService $helper, $id, $year)
    {

        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: true ");
        header("Access-Control-Allow-Methods: OPTIONS, GET, POST");
        header("Access-Control-Allow-Headers: Content-Type, Depth, User-Agent, Cache-Control");

        $sqlFourn = "SELECT DISTINCT
                      FO_ID,
                      (SELECT FO_RAISONSOC FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS WHERE CENTRALE_PRODUITS.dbo.FOURNISSEURS.FO_ID = CENTRALE_ACHAT.dbo.CLIENTS_CONSO.FO_ID GROUP BY FO_RAISONSOC) as FO_RAISONSOC
                    FROM CENTRALE_ACHAT.dbo.CLIENTS_CONSO";

        $conn = $connection->prepare($sqlFourn);
        $conn->execute();
        $fournisseur = $conn->fetchAll();

        $sqlMonth = "SELECT
                      (case month(CLC_DATE)
                       WHEN 1 THEN 'Jan'
                       WHEN 2 THEN 'Fév'
                       WHEN 3 THEN 'Mar'
                       WHEN 4 THEN 'Avr'
                       WHEN 5 THEN 'Mai'
                       WHEN 6 THEN 'Jun'
                       WHEN 7 THEN 'Jul'
                       WHEN 8 THEN 'Aoû'
                       WHEN 9 THEN 'Sep'
                       WHEN 10 THEN 'Oct'
                       WHEN 11 THEN 'Nov'
                       ELSE 'Déc'
                       end
                      ) as Month FROM CENTRALE_ACHAT.dbo.CLIENTS_CONSO
                    WHERE CL_ID = :id
                          AND year(CLC_DATE) = :date
                    group by MONTH(CLC_DATE)";

        $conn = $connection->prepare($sqlMonth);
        $conn->bindValue('id', $id);
        $conn->bindValue('date', $year);
        $conn->execute();
        $month = $conn->fetchAll();



        if (count($month) == 0){
            return new JsonResponse("none", 200);
        }



        $list_month = [];

        $tplMoisTemp = "";
        foreach ($month as $mois){
            array_push($list_month, $mois["Month"]);
            $tplMoisTemp .= "<th>". $mois["Month"] ."</th>";
        }



        $tplDataFinal = "";
        foreach ($fournisseur as $fourn){

            $sqlMonth = "SELECT CLC_PRIX_CENTRALE, CLC_PRIX_PUBLIC FROM CENTRALE_ACHAT.dbo.CLIENTS_CONSO WHERE CL_ID = :id AND FO_ID = :fourn AND year(CLC_DATE) = :date";

            $conn = $connection->prepare($sqlMonth);
            $conn->bindValue('id', $id);
            $conn->bindValue('fourn', $fourn["FO_ID"]);
            $conn->bindValue('date', $year);
            $conn->execute();
            $conso = $conn->fetchAll();


            $tempCa = 0;
            $tempEco = 0;
            $tplTempCa = "";
            $tplTempEco = "";
            $total_eco = 0;
            $total_ca = 0;

            foreach ($conso as $key=>$cons){

                $tplTempCa .= "<td>".$cons["CLC_PRIX_CENTRALE"] ." €</td>";
                $eco = $cons["CLC_PRIX_PUBLIC"] - $cons["CLC_PRIX_CENTRALE"];
                $tplTempEco .= "<td>".$eco." € (<b>". $helper->Pourcentage($eco,$cons["CLC_PRIX_PUBLIC"] )  ."%</b>)</td>";

                $tempCa = $tempCa +  $cons["CLC_PRIX_CENTRALE"];


                $total_ca = $total_ca +  intval($cons["CLC_PRIX_CENTRALE"]);
                $total_eco = $total_eco + $eco;




            }


            $tplTempCa .= "<td>".$total_ca ." €</td>";
            $tplTempEco .= "<td>".$total_eco." € (<b>". $helper->Pourcentage($total_eco, $total_ca + $total_eco)  ."%</b>)</td>";


            $tplMois = "<tr style='font-size: 13pt'>
            <th></th>
            <th></th>
            ". $tplMoisTemp ."
            <th style=\"background-color: #D3D3D3;\" >Total</th>
            </tr>";



            $tplData = "<tr style='font-size: 9pt'>
            <td rowspan=\"2\">".$fourn["FO_RAISONSOC"]."</td>
            <td>Mes achats</td>".
                $tplTempCa
                ."</tr>
        <tr style='font-size: 9pt'>
            <td>Economies</td>".
                $tplTempEco
                ."
        </tr>";

            $tplDataFinal .= $tplData;
        }



        $tplFinal = " <table id=\"table_conso\" class=\"table compact table-striped table-bordered\" style=\"width: 95%;margin: 0 auto;\">
        <thead>
        ".$tplMois."
        </thead>
        <tbody>
        ". $tplDataFinal ."
        </tbody>
    </table>";

        return new JsonResponse($tplFinal, 200);

    }


}
