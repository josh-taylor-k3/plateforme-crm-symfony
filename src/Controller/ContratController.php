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

class ContratController extends Controller
{
    /**
     * @Route("/contrats/{client_id}", name="contrats_byUser")
     */
    public function contactByClient(Connection $connection,DbService $db, $client_id, Request $request, HelperService $helper, ApiKeyAuth $auth,LogHsitory $log)
    {

        $key = $request->headers->get('X-ac-key');
        $grant = $auth->grant($key);

        switch ($grant){
            case $grant['profil'] == "FOURNISSEUR":

                return new JsonResponse("Vous n'avez pas accès a ces ressources ", 200);
                break;

            case $grant['profil'] == "CENTRALE":

                $centrale = $helper->getCentraleFromId($grant['centrale']);

                $sql = "SELECT * FROM ".$centrale.".dbo.CONTRATS WHERE CL_ID = :id";
                $conn = $connection->prepare($sql);
                $conn->bindValue('id', $client_id);
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
