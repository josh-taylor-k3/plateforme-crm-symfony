<?php

namespace App\Controller;

use App\Security\ApiKeyAuth;
use App\Service\DbService;
use App\Service\HelperService;
use App\Service\LogHsitory;
use Doctrine\DBAL\Driver\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HistoriqueController extends Controller
{
    /**
     * @Route("/historique/{client_id}", name="historique_client")
     */
    public function historyForClien(Connection $connection,DbService $db, Request $request, $client_id, HelperService $helper, ApiKeyAuth $auth,LogHsitory $log)
    {

        $key = $request->headers->get('X-ac-key');
        $grant = $auth->grant($key);

        switch ($grant){
            case $grant['profil'] == "FOURNISSEUR":

                return new JsonResponse("Vous n'avez pas accès a ces ressources ", 200);
                break;

            case $grant['profil'] == "CENTRALE":

                $centrale = $helper->getCentraleFromId($grant['centrale']);

                $sql = "SELECT (SELECT CL_RAISONSOC FROM CENTRALE_ACHAT.dbo.CLIENTS WHERE CL_ID = LO_IDENT_NUM) as Clients, LO_CODE, LO_DATE, LO_DESCR as Clients FROM CENTRALE_ACHAT.dbo.LOGS  WHERE LO_IDENT = 'CL_ID' AND LO_IDENT_NUM = :id";
                $conn = $connection->prepare($sql);
                $conn->bindValue('id', $client_id);
                $conn->execute();
                $result = $conn->fetchAll();





                if (!empty($result)) {
                    $data = [
                        "historique_agence" => $helper->array_utf8_encode($result),
                    ];
                    return new JsonResponse($data, 200);
                }
                return new JsonResponse("Aucun tickets trouvé ", 200);

                break;
        }

    }



    /**
     * @Route("/historique/user/{client_user_id}", name="historique_client_user")
     */
    public function historyForClientUSer(Connection $connection,DbService $db, Request $request, $client_user_id, HelperService $helper, ApiKeyAuth $auth,LogHsitory $log)
    {

        $key = $request->headers->get('X-ac-key');
        $grant = $auth->grant($key);

        switch ($grant){
            case $grant['profil'] == "FOURNISSEUR":

                return new JsonResponse("Vous n'avez pas accès a ces ressources ", 200);
                break;

            case $grant['profil'] == "CENTRALE":

                $centrale = $helper->getCentraleFromId($grant['centrale']);

                $sql = "SELECT (SELECT CC_NOM + ' ' + CC_PRENOM FROM CENTRALE_ACHAT.dbo.CLIENTS_USERS WHERE CC_ID = LO_IDENT_NUM), LO_CODE, LO_DATE, LO_DESCR as Clients  FROM CENTRALE_ACHAT.dbo.LOGS  WHERE LO_IDENT = 'CC_ID' AND LO_IDENT_NUM = :id";


                $conn = $connection->prepare($sql);
                $conn->bindValue('id', $client_user_id);
                $conn->execute();
                $result = $conn->fetchAll();





                if (!empty($result)) {
                    $data = [
                        "historique_user" => $helper->array_utf8_encode($result),
                    ];
                    return new JsonResponse($data, 200);
                }
                return new JsonResponse("Aucun tickets trouvé ", 200);

                break;
        }

    }
}
