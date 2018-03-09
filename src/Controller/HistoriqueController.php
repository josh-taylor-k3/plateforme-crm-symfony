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
    public function index(Connection $connection,DbService $db, Request $request, $client_id, HelperService $helper, ApiKeyAuth $auth,LogHsitory $log)
    {

        $key = $request->headers->get('X-ac-key');
        $grant = $auth->grant($key);

        switch ($grant){
            case $grant['profil'] == "FOURNISSEUR":

                return new JsonResponse("Vous n'avez pas accès a ces ressources ", 200);
                break;

            case $grant['profil'] == "CENTRALE":

                $centrale = $helper->getCentraleFromId($grant['centrale']);

                $sql = "SELECT * FROM CENTRALE_ACHAT.dbo.LOGS WHERE CL = :id";
                $conn = $connection->prepare($sql);
                $conn->bindValue('id', $id);
                $conn->execute();
                $result = $conn->fetchAll();


                if (!empty($result)) {
                    $data = $helper->array_utf8_encode($result);
                    return new JsonResponse($data, 200);
                }
                return new JsonResponse("Aucun tickets trouvé ", 200);

                break;
        }

    }
}
