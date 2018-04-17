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
     * @Route("/consommation/{id}/{start}/{end}/{fournisseur}", name="consommation_client")
     * @throws \Exception
     */
    public function consoClient(Connection $connection, HelperService $helper,$id, $start, $end, $fournisseur)
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: true ");
        header("Access-Control-Allow-Methods: OPTIONS, GET, POST");
        header("Access-Control-Allow-Headers: Content-Type, Depth, User-Agent, Cache-Control, X-ac-key");


        $sql = "SELECT *
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
        for($i = 0; $i < count($result);$i++){
           $total_prix_public += $result[$i]["CLC_PRIX_PUBLIC"];
           $total_prix_centrale += $result[$i]["CLC_PRIX_CENTRALE"];
           array_push($dataGraphPublic, $result[$i]['CLC_PRIX_PUBLIC']);
           array_push($dataGraphCentrale, $result[$i]['CLC_PRIX_CENTRALE']);
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
            "labels" => $helper->getArrayOfMonth(count($result) ),
            "dataGraph" => [
                "dataPublic" => $dataGraphPublic,
                "dataCentrale" => $dataGraphCentrale

            ]

        ];


        return new JsonResponse($result, 200);

    }
}
