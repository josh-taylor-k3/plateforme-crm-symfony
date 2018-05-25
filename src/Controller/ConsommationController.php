<?php

namespace App\Controller;

use App\Service\HelperService;
use Doctrine\DBAL\Driver\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

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


}
