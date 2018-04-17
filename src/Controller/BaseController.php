<?php

namespace App\Controller;

use App\Service\DbService;
use App\Service\HelperService;
use Doctrine\DBAL\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class BaseController extends Controller
{





    /**
     * @Route("/list", name="list_api_user")
     * @throws \Doctrine\DBAL\DBALException
     */
    public function ListApiUser(Request $request, Connection $connection, Environment $twig)
    {


        $sql = "SELECT * FROM CENTRALE_ACHAT_v2.dbo.API_USER";
        $conn = $connection->prepare($sql);
        $conn->execute();
        $result = $conn->fetchAll();

        $sqlFournisseur = "SELECT * FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS";
        $connFournisseur = $connection->prepare($sqlFournisseur);
        $connFournisseur->execute();
        $fournisseur = $connFournisseur->fetchAll();



        try {
            return new Response($twig->render("Api/ListToken.html.twig", [
                "list" => $result,
                "fournisseur" => $fournisseur
            ]));
        } catch (\Twig_Error_Loader $e) {
        } catch (\Twig_Error_Runtime $e) {
        } catch (\Twig_Error_Syntax $e) {
        }
    }


    /**
     * @Route("/list/{id}", name="detail_api_user")
     * @Method("GET")
     */
    public function detailApuUser(Request $request, Connection $connection, Environment $twig, $id, DbService $db)
    {

        $detail = $db->getDetailById($id);

        $history = $db->getHistoryById($id);



        return new Response($twig->render("Api/detail_user.html.twig", [
            "list" => $detail[0],
            "history" => $history
        ]));

    }

    /**
     * @Route("/new", name="new_api_user")
     * @Method("POST")
     * @throws \Exception
     */
    public function newApiUser(Request $request, Connection $connection, Environment $twig, HelperService $helper)
    {

        $app_name = $request->request->get('app_name');
        $app_profil = $request->request->get('app_profil');
        $selection_centrale = $request->request->get('selection_centrale');
        $selection_fournisseur = $request->request->get('selection_fournisseur');

        $tokenApi = $helper->getToken(40);



        switch ($app_profil){
            case "FOURNISSEUR":

                $sql = "INSERT INTO CENTRALE_ACHAT_v2.dbo.API_USER (FO_ID, AU_SECRET, AU_NAME, AU_PROFIL, INS_DATE, INS_USER) VALUES (:fournisseur_id,:token, :name,:profil, GETDATE(), :ins_user)";

                $conn = $connection->prepare($sql);
                $conn->bindValue('token', $tokenApi);
                $conn->bindValue('name', $app_name);
                $conn->bindValue('profil', $app_profil);
                $conn->bindValue('fournisseur_id', $selection_fournisseur);
                $conn->bindValue('ins_user', "API_SITE");


                $conn->execute();
                $result = $conn->fetchAll();

                break;
            case "CENTRALE":
                $sql = "INSERT INTO CENTRALE_ACHAT_v2.dbo.API_USER ( AU_SECRET, AU_NAME, AU_PROFIL, AU_DATABASE, INS_DATE, INS_USER) VALUES (:token, :name,:profil,:centrale,  GETDATE(), :ins_user)";

                $conn = $connection->prepare($sql);
                $conn->bindValue('token', $tokenApi);
                $conn->bindValue('name', $app_name);
                $conn->bindValue('profil', $app_profil);
                $conn->bindValue('centrale', $selection_centrale);
                $conn->bindValue('ins_user', "API_SITE");


                $conn->execute();
                $result = $conn->fetchAll();

                break;
        }









        return $this->redirectToRoute('list_api_user', array(), 301);

//        return new Response('ok', 200);
    }

    /**
     * @Route("/user/setDroits",name="set_droits_user" )
     * @Method("POST")
     */
    public function setDroitsApiUser( DbService $db, Request $request, Connection $conn){


        $clients = $request->request->get('clients');
        $tickets = $request->request->get('tickets');
        $fourn = $request->request->get('fourn');
        $produits = $request->request->get('produits');
        $id = $request->request->get('id');


        $sql = "UPDATE CENTRALE_ACHAT_v2.dbo.API_DROITS
                SET AD_TICKETS = :ticket, AD_PRODUITS = :produits,  AD_FOURN = :fourn, AD_CLIENTS = :client
                WHERE APP_ID = :id";


        $conn = $conn->prepare($sql);
        $conn->bindValue('ticket', $tickets);
        $conn->bindValue('produits', $produits);
        $conn->bindValue('fourn', $fourn);
        $conn->bindValue('client', $clients);
        $conn->bindValue('id', $id);


        $conn->execute();
        $result = $conn->fetchAll();



        $data = [

            "clients" => $clients,
            "tickets" => $tickets,
            "fourn" => $fourn,
            "produits" => $produits,
            "id" => $id,

        ];


        return new JsonResponse($data, 200);

    }


    /**
     * @Route("/testAjax",name="testAjax" )
     */
    public function testAjax( Request $request, Connection $connection, Environment $twig )
    {

        return new Response( $twig->render('testAjax.html.twig'), 200 );
    }

}
