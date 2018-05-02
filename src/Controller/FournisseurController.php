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

        $limit = $request->query->get('limit');

        $grant = $auth->grant($key);

        switch ($grant){
            case $grant['profil'] == "FOURNISSEUR":
                return new JsonResponse("Vous n'avez pas accès a ces ressources", 500);
                break;

            case $grant['profil'] == "CENTRALE":

                $centrale = $grant['centrale'];

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

                break;
        }





    }

    /**
     * @Route("/fournisseur/{id}", name="fournisseur")
     */
    public function getFournisseur(Request $request , ApiKeyAuth $auth, Connection $connection, HelperService $helper, $id)
    {

        header("Access-Control-Allow-Origin: *");
        $key = $request->headers->get('X-ac-key');

        $limit = $request->query->get('limit');

        $grant = $auth->grant($key);

        switch ($grant){
            case $grant['profil'] == "FOURNISSEUR":
                return new JsonResponse("Vous n'avez pas accès a ces ressources", 500);
                break;

            case $grant['profil'] == "CENTRALE":
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

                break;
        }



    }

    /**
     * @Route("/fournisseur/{id}/products", name="fournisseur_products")
     */
    public function getProductsFromFournisseur(Request $request , ApiKeyAuth $auth, Connection $connection, HelperService $helper, $id)
    {
        header("Access-Control-Allow-Origin: *");
        $key = $request->headers->get('X-ac-key');
        if (isset($key) && $auth->grant($key)) {
            $sql = "SELECT *
                    FROM CENTRALE_PRODUITS.dbo.PRODUITS
                    WHERE FO_ID = :id";
            $conn = $connection->prepare($sql);
            $conn->bindValue('id', $id);
            $conn->execute();
            $result = $conn->fetchAll();
            if (!isset($result)) {
                return new JsonResponse("Aucun produit trouvé", 200);}
            $data = $helper->array_utf8_encode($result);
            $id = $helper->getIdFromApiKey($key);
//            $log->logAction($id[0]['APP_ID'], "get:produits");
            return new JsonResponse($data, 200);
        } else {
            return new JsonResponse("Vous n'avez pas accès a ces ressources", 500);
        }

    }

    /**
     * @Route("/fournisseur/conso/all", name="fournisseur_conso")
     */
    public function getFournisseurFromConso(Request $request , ApiKeyAuth $auth, Connection $connection, HelperService $helper)
    {



        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: true ");
        header("Access-Control-Allow-Methods: OPTIONS, GET, POST");
        header("Access-Control-Allow-Headers: Content-Type, Depth, User-Agent, X-File-Size, X-Requested-With, If-Modified-Since, X-File-Name, Cache-Control, X-ac-key");


        $sql = "SELECT DISTINCT FO_ID, FO_NOM_COM
                        FROM CENTRALE_ACHAT.dbo.Vue_Produits_CRFF
                        ORDER BY FO_NOM_COM";

        $conn = $connection->prepare($sql);
        $conn->execute();
        $result = $conn->fetchAll();
        if (!isset($result)) {
            return new JsonResponse("Aucun produit trouvé", 200);
        }
        $data = $helper->array_utf8_encode($result);
//            $log->logAction($id[0]['APP_ID'], "get:produits");
        return new JsonResponse($data, 200);

    }

}
