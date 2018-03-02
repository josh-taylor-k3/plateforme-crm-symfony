<?php

namespace App\Controller;

use App\Security\ApiKeyAuth;
use App\Service\HelperService;
use Doctrine\DBAL\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ClientController extends Controller
{
    /**
     * @Route("/clients/{idCentrale}", name="clients")
     */
    public function getClients(Request $request , ApiKeyAuth $auth, Connection $connection, HelperService $helper, $idCentrale)
    {

        header("Access-Control-Allow-Origin: *");


        $key = $request->headers->get('X-ac-key');

        if (isset($key) && $auth->grant($key)) {

            $sql = "SELECT *
                    FROM CENTRALE_ACHAT.dbo.Vue_All_Clients
                    WHERE SO_ID = :id";

            $conn = $connection->prepare($sql);
            $conn->bindValue('id', $idCentrale);
            $conn->execute();
            $result = $conn->fetchAll();
            if (!isset($result)) {
                return new JsonResponse("Aucun produit trouvé", 200);
            }
            $data = $helper->array_utf8_encode($result);
            $id = $helper->getIdFromApiKey($key);
//            $log->logAction($id[0]['APP_ID'], "get:produits");
            return new JsonResponse($data, 200);
        } else {
            return new JsonResponse("Vous n'avez pas accès a ces ressources", 500);
        }

    }


    /**
     * @Route("/client/{id}", name="clients")
     */
    public function getClient(Request $request, ApiKeyAuth $auth, Connection $connection, HelperService $helper, $id)
    {


        header("Access-Control-Allow-Origin: *");


        $key = $request->headers->get('X-ac-key');
        $centrale = $request->query->get('centrale');


        if (isset($key) && $auth->grant($key)) {

            $sql = "SELECT TOP 1 *
                    FROM CENTRALE_ACHAT.dbo.Vue_All_Clients
                    WHERE SO_ID = :idCentrale
                    AND CL_ID = :id
                                        ";

            $conn = $connection->prepare($sql);
            $conn->bindValue('idCentrale', $centrale);
            $conn->bindValue('id', $id);
            $conn->execute();
            $result = $conn->fetchAll();
            if (!isset($result)) {
                return new JsonResponse("Aucun produit trouvé", 200);
            }
            $data = $helper->array_utf8_encode($result);
            $id = $helper->getIdFromApiKey($key);
//            $log->logAction($id[0]['APP_ID'], "get:produits");
            return new JsonResponse($data, 200);
        } else {
            return new JsonResponse("Vous n'avez pas accès a ces ressources", 500);
        }




    }
}
