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

                $limit = $request->query->get('limit');


                $frsRaisonSoc = $db->getRaisonSocFrs($grant['fo_id']);
                $sql = "SELECT TOP ".$limit."  * FROM CENTRALE_ACHAT.dbo.Vue_All_Tickets
                        WHERE FO_RAISONSOC = :id_four
                        ORDER BY ME_DATE DESC";
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

                $limit = $request->query->get('limit');


                $sql = "SELECT TOP ".$limit."  * FROM ".$centrale.".dbo.Vue_All_Tickets
                ORDER BY ME_DATE DESC";
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
     * @Method("POST")
     */
    public function TicketsNew(Connection $connection,DbService $db, Request $request, HelperService $helper, ApiKeyAuth $auth,LogHsitory $log)
    {


        header("Access-Control-Allow-Origin: *");


        $key = $request->headers->get('X-ac-key');
        $client = $request->request->get('client');
        $fournisseur = $request->request->get('fournisseur');
        $sujet = $request->request->get('sujet');
        $body = $request->request->get('body');
        $clientUser = $request->request->get('client_user');
        $FournUser = $request->request->get('fourn_user');
        $grant = $auth->grant($key);
        $token = $helper->getToken(50);

        $data = [
            "client" => $client,
            "fournisseur" => $fournisseur,
            "sujet" => $sujet,
            "body" => $body,
        ];

        if(!$db->isInCentrale($client, $grant['centrale'])){
            return new JsonResponse('Ce client ne fait pas parti de votre centrale', 500);

        }


        switch ($grant){
            case $grant['profil'] == "FOURNISSEUR":

//                $limit = $request->query->get('limit');
//
//
//                $frsRaisonSoc = $db->getRaisonSocFrs($grant['fo_id']);
//                $sql = "SELECT TOP ".$limit."  * FROM CENTRALE_ACHAT.dbo.Vue_All_Tickets
//                        WHERE FO_RAISONSOC = :id_four
//                        ORDER BY ME_DATE DESC";
//                $conn = $connection->prepare($sql);
//                $conn->bindValue('id_four', $frsRaisonSoc[0]['FO_RAISONSOC'] );
//                $conn->execute();
//                $result = $conn->fetchAll();
//
//                if (!empty($result)) {
//                    $data = $helper->array_utf8_encode($result);
//                    return new JsonResponse($data, 200);
//                }
                return new JsonResponse("Aucune config pour l'instant FOURNISSEUR ", 200);
                break;

            case $grant['profil'] == "CENTRALE":

                $centrale = $helper->getCentraleFromId($grant['centrale']);


                $sql = "SET XACT_ABORT ON;
                        
                        BEGIN TRANSACTION
                        INSERT INTO ".$centrale.".dbo.MESSAGE_ENTETE (SO_ID, CL_ID, CC_ID, FO_ID, FC_ID, PR_ID, ME_DATE, ME_SUJET, ME_STATUS, ME_LU_C, ME_LU_F, ME_ADR_FAC, ME_ADR_LIV, ME_TEMPO, INS_DATE, INS_USER, MAJ_DATE, MAJ_USER)
                        VALUES(1, :client, :cc_id, :fournisseur, :fc_id, 0, GETDATE(), :sujet, 0,0, 0, 0, 0,  :token, GETDATE(), 'API', GETDATE(), 'API');
                        INSERT INTO ".$centrale.".dbo.MESSAGE_DETAIL (ME_ID, MD_DATE, MD_CORPS,CC_ID,FC_ID, INS_DATE, INS_USER)
                        VALUES(SCOPE_IDENTITY(),  GETDATE(), :body,:cc_id,:fc_id, GETDATE(), 'API');
                        COMMIT
                        ";
                $conn = $connection->prepare($sql);
                $conn->bindValue(':centrale', $grant['centrale']);
                $conn->bindValue(':client', $client);
                $conn->bindValue(':fournisseur', $fournisseur);
                $conn->bindValue(':sujet', $sujet);
                $conn->bindValue(':body', $body);
                $conn->bindValue(':token', $token);
                $conn->bindValue(':fc_id', $FournUser);
                $conn->bindValue(':cc_id', $clientUser);
                $conn->execute();
                $result = $conn->fetchAll();

                $data = $helper->array_utf8_encode($result);
                return new JsonResponse($data, 200);





                break;
        }
        return new JsonResponse('ok', 200);
    }


    /**
     * @Route("/ticket/answer/{id}", name="ticket_answer")
     * @Method("POST")
     */
    public function TicketsAnswer(Connection $connection,DbService $db, Request $request, HelperService $helper, $id, ApiKeyAuth $auth,LogHsitory $log)
    {


        header("Access-Control-Allow-Origin: *");

        $key = $request->headers->get('X-ac-key');
        $body = $request->request->get('body');
        $clientUser = $request->request->get('client_user');
        $FournUser = $request->request->get('fourn_user');
        $grant = $auth->grant($key);
        $id_ticket = $id;

        $data = [
            "client_user" => $clientUser,
            "fournisseur_user" => $FournUser,
            "body" => $body,
        ];


        switch ($grant){
            case $grant['profil'] == "FOURNISSEUR":



                $ticket = $db->getTicketsForFrs($id);


                if (intval($ticket['ticket']['CC_ID']) === intval($data['client_user']) && intval($ticket['ticket']['FC_ID']) === intval($data['fournisseur_user'])){


                    $sqlInsert = "INSERT INTO ".$ticket['centrale'].".dbo.MESSAGE_DETAIL (ME_ID, FC_ID, MD_DATE, MD_CORPS, INS_DATE, INS_USER)
    VALUES
      (:me_id, :fourn_user,GETDATE(), :body, GETDATE(), 'API' )";
                    $connInsert = $connection->prepare($sqlInsert);
                    $connInsert->bindValue('me_id', $id );
                    $connInsert->bindValue('fourn_user', $data['fournisseur_user'] );
                    $connInsert->bindValue('body', $data['body'] );
                    $connInsert->execute();
                    $resultInsert = $connInsert->fetchAll();

                    return new JsonResponse('ok', 200);
                }

                return new JsonResponse("CC_ID et FC_ID different de ceux du ticket", 500);
                break;

            case $grant['profil'] == "CENTRALE":


                $centrale = $helper->getCentraleFromId($grant['centrale']);


                $sql = "SELECT * FROM ".$centrale.".dbo.MESSAGE_ENTETE
                        WHERE ME_ID = :id";
                $conn = $connection->prepare($sql);
                $conn->bindValue('id', $id );
                $conn->execute();
                $result = $conn->fetchAll()[0];



                if (intval($result['CC_ID']) === intval($data['client_user']) || intval($result['FC_ID']) === intval($data['fournisseur_user'])){


                    $sqlInsert = "INSERT INTO ".$centrale.".dbo.MESSAGE_DETAIL (ME_ID, CC_ID, MD_DATE, MD_CORPS, INS_DATE, INS_USER)
    VALUES
      (:me_id, :client_user,GETDATE(), :body, GETDATE(), 'API' )";
                    $connInsert = $connection->prepare($sqlInsert);
                    $connInsert->bindValue('me_id', $id );
                    $connInsert->bindValue('client_user', $data['client_user'] );
                    $connInsert->bindValue('body', $data['body'] );
                    $connInsert->execute();
                    $resultInsert = $connInsert->fetchAll();


                    return new JsonResponse('ok', 200);


                }

                return new JsonResponse("CC_ID ou FC_ID different de ceux du ticket", 500);




                break;
        }




        return new JsonResponse('ok', 200);

    }


}
