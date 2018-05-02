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
     * @Route("/consommation/{id}/{start}/{end}/", name="consommation_client", methods={"GET"})
     */
    public function consoClient(Connection $connection, HelperService $helper,$id, $start, $end)
    {

        $sqlBruneau = "SELECT CLC_ID, CL_ID, CC_ID, FO_ID, CLC_DATE, CLC_PRIX_PUBLIC, CLC_PRIX_CENTRALE, INS_DATE, INS_USER , (
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
                      AND FO_ID = 2";
        $conn = $connection->prepare($sqlBruneau);
        $conn->bindValue('id', $id);
        $conn->bindValue('start', $start);
        $conn->bindValue('end', $end);
        $conn->execute();
        $resultBruneau = $conn->fetchAll();
        $sqlBouygues = "SELECT CLC_ID, CL_ID, CC_ID, FO_ID, CLC_DATE, CLC_PRIX_PUBLIC, CLC_PRIX_CENTRALE, INS_DATE, INS_USER , (
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
                      AND FO_ID = 3";
        $conn = $connection->prepare($sqlBouygues);
        $conn->bindValue('id', $id);
        $conn->bindValue('start', $start);
        $conn->bindValue('end', $end);
        $conn->execute();
        $resultBouygues = $conn->fetchAll();
        if (empty($resultBouygues) && empty($resultBruneau))
        {
            throw new \Exception('Aucun resultat');
        }
        $total_bruneau = 0;
        $total_bouygues = 0;
        $total_economie_bouygues = 0;
        $total_economie_bruneau = 0;
        $dataGraphBruneau = [];
        $dataGraphBouygues= [];
        $economie_bruneau = [];
        $economie_bouygues = [];
        $labels = [];

        for($i = 0; $i < count($resultBruneau);$i++){


            $total_bruneau += $resultBruneau[$i]['CLC_PRIX_CENTRALE'];
            $total_bouygues += $resultBouygues[$i]['CLC_PRIX_CENTRALE'];
            $total_economie_bouygues += $resultBruneau[$i]['CLC_PRIX_PUBLIC'] - $resultBruneau[$i]['CLC_PRIX_CENTRALE'];
            $total_economie_bruneau += $resultBruneau[$i]['CLC_PRIX_PUBLIC'] - $resultBruneau[$i]['CLC_PRIX_CENTRALE'];
            array_push($dataGraphBruneau, $resultBruneau[$i]['CLC_PRIX_CENTRALE']);
            array_push($dataGraphBouygues, $resultBouygues[$i]['CLC_PRIX_CENTRALE']);
            array_push($dataGraphBouygues, $resultBouygues[$i]['CLC_PRIX_CENTRALE']);
            array_push($economie_bruneau, $resultBruneau[$i]['CLC_PRIX_PUBLIC'] - $resultBruneau[$i]['CLC_PRIX_CENTRALE']);
            array_push($economie_bouygues, $resultBouygues[$i]['CLC_PRIX_PUBLIC'] - $resultBouygues[$i]['CLC_PRIX_CENTRALE']);
            array_push($labels, $resultBruneau[$i]['Month']);
        }

        $result = [
            "count" => count($resultBruneau),
            "result" => "ok",
            "Total" => [
               "ca" => [
                   "Bruneau" => $total_bruneau,
                   "Bouygues" => $total_bouygues,
               ],
                "economie" =>[
                    "Bruneau" => $total_economie_bruneau,
                    "Bouygues" => $total_economie_bouygues,
                ]

            ],
            "labels" => $labels,
            "dataGraph" => [
               "ca" => [
                   "pricCentraleBruneau" => $dataGraphBruneau,
                   "prixCentraleBouygues" => $dataGraphBouygues
               ],
                "economie" => [
                    "economie_bruneau" => $economie_bruneau,
                    "economie_bouygues" => $economie_bouygues,
                ]

            ]

        ];

        header("Access-Control-Allow-Origin: http://secure.achatcentrale.fr/");


        return new JsonResponse($result, 200);

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
