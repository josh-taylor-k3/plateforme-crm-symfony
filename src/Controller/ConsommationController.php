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
     * @Route("/consommation/{id}/{start}/{end}/{fournisseur}", name="consommation_client")
     * @throws \Exception
     */
    public function consoClient(Connection $connection, HelperService $helper,$id, $start, $end, $fournisseur)
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: true ");
        header("Access-Control-Allow-Methods: OPTIONS, GET, POST");
        header("Access-Control-Allow-Headers: Content-Type, Depth, User-Agent, Cache-Control, X-ac-key");


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
                      AND FO_ID = :fournisseur"
                        ;


        $conn = $connection->prepare($sql);
        $conn->bindValue('id', $id);
        $conn->bindValue('start', $start);
        $conn->bindValue('end', $end);
        $conn->bindValue('fournisseur', $fournisseur);
        $conn->execute();
        $result = $conn->fetchAll();

        if (empty($result))
        {
            throw new \Exception('Aucun resultat');
        }


        $total_prix_public = 0;
        $total_prix_centrale = 0;
        $dataGraphPublic = [];
        $dataGraphCentrale = [];
        $labels = [];

        for($i = 0; $i < count($result);$i++){
           $total_prix_public += $result[$i]["CLC_PRIX_PUBLIC"];
           $total_prix_centrale += $result[$i]["CLC_PRIX_CENTRALE"];
           array_push($dataGraphPublic, $result[$i]['CLC_PRIX_PUBLIC']);
           array_push($dataGraphCentrale, $result[$i]['CLC_PRIX_CENTRALE']);
           array_push($labels, $result[$i]['Month']);
        }
        $result = [
            "count" => count($result),
            "result" => "ok",
            "data" => $result,
            "Total" => [
                "CLC_PRIX_PUBLIC" => $total_prix_public,
                "CLC_PRIX_CENTRALE" => $total_prix_centrale,
                "ECONOMIE" => $total_prix_public - $total_prix_centrale,

            ],
            "labels" => $labels,
            "dataGraph" => [
                "dataPublic" => $dataGraphPublic,
                "dataCentrale" => $dataGraphCentrale

            ]

        ];


        return new JsonResponse($result, 200);

    }

}
