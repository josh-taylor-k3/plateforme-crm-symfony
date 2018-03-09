<?php

namespace App\Controller;

use App\Security\ApiKeyAuth;
use App\Service\DbService;
use App\Service\HelperService;
use App\Service\LogHsitory;
use Doctrine\DBAL\Driver\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TicketsController extends Controller
{


    /**
     * @Route("/tickets", name="tickets_all")
     */
    public function ticketsAll(Connection $connection,DbService $db, Request $request, HelperService $helper, ApiKeyAuth $auth,LogHsitory $log)
    {

        header("Access-Control-Allow-Origin: *");

        $key = $request->headers->get('X-ac-key');
        $grant = $auth->grant($key);

        switch ($grant){
            case $grant['profil'] == "FOURNISSEUR":
                $frsRaisonSoc = $db->getRaisonSocFrs($grant['fo_id']);
                $sql = "SELECT * FROM CENTRALE_ACHAT.dbo.Vue_All_Tickets
                        WHERE FO_RAISONSOC = :id_four";
                $conn = $connection->prepare($sql);
                $conn->bindValue('id_four', $frsRaisonSoc[0]['FO_RAISONSOC'] );
                $conn->execute();
                $result = $conn->fetchAll();

                if (!empty($result)) {
                    $data = $helper->array_utf8_encode($result);
                    return new JsonResponse($data, 200);
                }
                return new JsonResponse("Aucun tickets trouvé ", 200);
                break;

            case $grant['profil'] == "CENTRALE":

                $centrale = $helper->getCentraleFromId($grant['centrale']);



                $sql = "SELECT * FROM ".$centrale.".dbo.Vue_All_Tickets";
                $conn = $connection->prepare($sql);
                $conn->execute();
                $result = $conn->fetchAll();

                if (!empty($result)) {
                    $data = $helper->array_utf8_encode($result);
                    return new JsonResponse($data, 200);
                }
                return new JsonResponse("Aucun tickets trouvé ", 200);




                break;
        }




        return new JsonResponse('ok', 200);

    }

    /**
     * @Route("/ticket/{id}", name="ticket_byId")
     */
    public function ticketsById(Connection $connection,DbService $db, Request $request, HelperService $helper, ApiKeyAuth $auth,LogHsitory $log, $id)
    {

        header("Access-Control-Allow-Origin: *");
        $key = $request->headers->get('X-ac-key');
        $grant = $auth->grant($key);
        switch ($grant){
            case $grant['profil'] == "FOURNISSEUR":
                $frsRaisonSoc = $db->getRaisonSocFrs($grant['fo_id']);
                $sql = "SELECT * FROM CENTRALE_ACHAT.dbo.Vue_All_Tickets
                        WHERE ME_ID = :id
                        AND FO_RAISONSOC = :id_four";


                $conn = $connection->prepare($sql);
                $conn->bindValue('id', $id);
                $conn->bindValue('id_four', $frsRaisonSoc[0]['FO_RAISONSOC'] );
                $conn->execute();
                $result = $conn->fetchAll();

                if (!empty($result)) {

                    $data = $helper->array_utf8_encode($result);

                    return new JsonResponse($data, 200);
                }
                return new JsonResponse("Aucun tickets trouvé ", 200);


                break;

            case $grant['profil'] == "CENTRALE":

                $centrale = $helper->getCentraleFromId($grant['centrale']);

                $sql = "SELECT * FROM ".$centrale.".dbo.MESSAGE_ENTETE WHERE ME_ID = :id";
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




        return new JsonResponse('ok', 200);



    }

    /**
     * @Route("/tickets/client/{id}", name="ticket_client")
     */
    public function TicketsByClients(Connection $connection,DbService $db, Request $request, HelperService $helper, ApiKeyAuth $auth,LogHsitory $log, $id)
    {


        header("Access-Control-Allow-Origin: *");
        $key = $request->headers->get('X-ac-key');
        $grant = $auth->grant($key);

        switch ($grant){
            case $grant['profil'] == "FOURNISSEUR":



                $frsRaisonSoc = $db->getRaisonSocFrs($grant['fo_id']);
                $sql = "SELECT * FROM CENTRALE_ACHAT.dbo.Vue_All_Tickets
                        WHERE CL_ID = :id
                        AND FO_RAISONSOC = :id_four";


                $conn = $connection->prepare($sql);
                $conn->bindValue('id', $id);
                $conn->bindValue('id_four', $frsRaisonSoc[0]['FO_RAISONSOC'] );
                $conn->execute();
                $result = $conn->fetchAll();

                if (!empty($result)) {

                    $data = $helper->array_utf8_encode($result);

                    return new JsonResponse($data, 200);
                }
                return new JsonResponse("Aucun tickets trouvé ", 200);


                break;

            case $grant['profil'] == "CENTRALE":

                $centrale = $helper->getCentraleFromId($grant['centrale']);

                $sql = "SELECT * FROM ".$centrale.".dbo.MESSAGE_ENTETE WHERE ME_ID = :id";
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


        return new JsonResponse('ok', 200);





    }


    /**
     * @Route("/tickets/new", name="ticket_new")
     */
    public function TicketsNew(Connection $connection, Request $request, HelperService $helper, ApiKeyAuth $auth,LogHsitory $log, $id)
    {


        header("Access-Control-Allow-Origin: *");


        $key = $request->headers->get('X-ac-key');
        $produit = $request->request->get('produit');
        $fournisseur = $request->request->get('fournisseur');
        $client = $request->request->get('client');



        $data = [
            "produit" => $produit,
            "fournisseur" => $fournisseur,
            "client" => $client,
        ];


        $sql = "SELECT * FROM CENTRALE_ACHAT.dbo.Vue_All_Tickets
                WHERE CL_ID = :id";


        $conn = $connection->prepare($sql);
        $conn->bindValue('id', $id);
        $conn->execute();
        $result = $conn->fetchAll();

        if (!isset($result)) {
            return new JsonResponse("Aucun produit trouvé", 200);

        }


        $data = $helper->array_utf8_encode($result);



        return new JsonResponse($data, 200);








    }





}
