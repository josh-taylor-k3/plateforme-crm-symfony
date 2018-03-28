<?php

namespace App\Controller;

use App\Security\ApiKeyAuth;
use App\Service\DbService;
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
     * @Route("/clients", name="clients")
     */
    public function getClients(Request $request ,DbService $db, ApiKeyAuth $auth, Connection $connection, HelperService $helper)
    {


        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: true ");
        header("Access-Control-Allow-Methods: OPTIONS, GET, POST");
        header("Access-Control-Allow-Headers: Content-Type, Depth, User-Agent, X-File-Size, X-Requested-With, If-Modified-Since, X-File-Name, Cache-Control");
        $key = $request->headers->get('X-ac-key');

        $limit = $request->query->get('limit');

        $grant = $auth->grant($key);

        switch ($grant){
            case $grant['profil'] == "FOURNISSEUR":
                $frsRaisonSoc = $db->getRaisonSocFrs($grant['fo_id']);
                $sql = "SELECT TOP ".$limit." * FROM CENTRALE_ACHAT.dbo.Vue_All_Clients";

                $conn = $connection->prepare($sql);
                $conn->execute();
                $result = $conn->fetchAll();

                if (!empty($result)) {

                    $data = $helper->array_utf8_encode($result);

                    return new JsonResponse($data, 200);
                }
                return new JsonResponse("Aucun tickets trouvé ", 200);


                break;

            case $grant['profil'] == "CENTRALE":

                $centrale = $grant['centrale'];

                $sql = "SELECT * FROM CENTRALE_ACHAT.dbo.Vue_All_Clients WHERE SO_ID = :id";
                $conn = $connection->prepare($sql);
                $conn->bindValue('id', $centrale);
                $conn->execute();
                $result = $conn->fetchAll();


                if (!empty($result)) {
                    $data = $helper->array_utf8_encode($result);
                    return new JsonResponse($data, 200);
                }
                return new JsonResponse("Aucun clients trouvé ", 200);

                break;
        }


        return new JsonResponse('Vous n\'avez pas acces a ces ressources', 200);


    }


    /**
     * @Route("/client/{id}", name="clients_by_id")
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
                    AND CL_ID = :id";

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

    /**
     * @Route("/client/{id}/user", name="clients_user")
     */
    public function getClientsUsers(Request $request,DbService $db , ApiKeyAuth $auth, Connection $connection, HelperService $helper, $id)
    {


        header("Access-Control-Allow-Origin: *");
        $key = $request->headers->get('X-ac-key');

        $limit = $request->query->get('limit');

        $grant = $auth->grant($key);

        switch ($grant){
            case $grant['profil'] == "FOURNISSEUR":
                $frsRaisonSoc = $db->getRaisonSocFrs($grant['fo_id']);
                $sql = "SELECT CL_ID, CC_MAIL, CC_NOM, CC_PRENOM FROM CENTRALE_ACHAT.dbo.Vue_All_Clients";

                $conn = $connection->prepare($sql);
                $conn->execute();
                $result = $conn->fetchAll();

                if (!empty($result)) {

                    $data = $helper->array_utf8_encode($result);

                    return new JsonResponse($data, 200);
                }
                return new JsonResponse("Aucun tickets trouvé ", 200);


                break;

            case $grant['profil'] == "CENTRALE":

                $centrale = $grant['centrale'];

                $centrale = $helper->getCentraleFromId($grant['centrale']);

                $sql = "SELECT * FROM ".$centrale.".dbo.CLIENTS_USERS WHERE CL_ID = :id";
                $conn = $connection->prepare($sql);
                $conn->bindValue('id', $id);
                $conn->execute();
                $result = $conn->fetchAll();


                if (!empty($result)) {
                    $data = $helper->array_utf8_encode($result);
                    return new JsonResponse($data, 200);
                }
                return new JsonResponse("Aucun clients trouvé ", 200);

                break;
        }


        return new JsonResponse('Vous n\'avez pas acces a ces ressources', 200);


//
//        header("Access-Control-Allow-Origin: *");
//
//
//        $key = $request->headers->get('X-ac-key');
//        $centrale = $request->query->get('centrale');
//
//
//        if (isset($key) && $auth->grant($key)) {
//
//            $sql = "SELECT CC_MAIL, CC_NOM, CC_PRENOM
//                    FROM CENTRALE_ACHAT.dbo.Vue_All_Clients
//                    WHERE SO_ID = :idCentrale
//                    AND CL_ID = :id";
//
//            $conn = $connection->prepare($sql);
//            $conn->bindValue('idCentrale', $idCentrale);
//            $conn->bindValue('id', $id);
//            $conn->execute();
//            $result = $conn->fetchAll();
//            if (!isset($result)) {
//                return new JsonResponse("Aucun produit trouvé", 200);
//            }
//            $data = $helper->array_utf8_encode($result);
//            $id = $helper->getIdFromApiKey($key);
////            $log->logAction($id[0]['APP_ID'], "get:produits");
//            return new JsonResponse($data, 200);
//        } else {
//            return new JsonResponse("Vous n'avez pas accès a ces ressources", 500);
//        }



    }

    /**
     * @Route("/client/{idCentrale}/{id}/regions", name="clients_regions")
     */
    public function getClientsRegion(Request $request, ApiKeyAuth $auth, Connection $connection, HelperService $helper, $id, $idCentrale)
    {

        header("Access-Control-Allow-Origin: *");


        $key = $request->headers->get('X-ac-key');
        $centrale = $request->query->get('centrale');


        if (isset($key) && $auth->grant($key)) {

            $sql = "SELECT CC_MAIL, CC_NOM, CC_PRENOM
                    FROM CENTRALE_ACHAT.dbo.Vue_All_Clients
                    WHERE SO_ID = :idCentrale
                    AND CL_ID = :id";

            $conn = $connection->prepare($sql);
            $conn->bindValue('idCentrale', $idCentrale);
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
