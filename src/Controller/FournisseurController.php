<?php

namespace App\Controller;

use App\Security\ApiKeyAuth;
use App\Service\HelperService;
use Doctrine\DBAL\Driver\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FournisseurController extends Controller
{
    /**
     * @Route("/fournisseurs", name="fournisseurs")
     */
    public function getFournisseurs(Request $request , ApiKeyAuth $auth, Connection $connection, HelperService $helper)
    {


        header("Access-Control-Allow-Origin: *");


        $key = $request->headers->get('X-ac-key');

        if (isset($key) && $auth->grant($key)) {

            $sql = "SELECT *
                    FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS";

            $conn = $connection->prepare($sql);
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
     * @Route("/fournisseur/{id}", name="fournisseur")
     */
    public function getFournisseur(Request $request , ApiKeyAuth $auth, Connection $connection, HelperService $helper, $id)
    {



        header("Access-Control-Allow-Origin: *");


        $key = $request->headers->get('X-ac-key');

        if (isset($key) && $auth->grant($key)) {

            $sql = "SELECT *
                    FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS
                    WHERE FO_ID = :id";

            $conn = $connection->prepare($sql);
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
