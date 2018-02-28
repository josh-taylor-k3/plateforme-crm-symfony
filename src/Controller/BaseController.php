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
     * @Route("/", name="base")
     */
    public function index(Environment $twig)
    {
        // replace this line with your own code!
        return $this->render('@Maker/demoPage.html.twig', [ 'path' => str_replace($this->getParameter('kernel.project_dir').'/', '', __FILE__) ]);
    }

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


        try {
            return new Response($twig->render("Api/ListToken.html.twig", [
                "list" => $result
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
     */
    public function newApiUser(Request $request, Connection $connection, Environment $twig, HelperService $helper)
    {

        $app_name = $request->request->get('app_name');

        $tokenApi = $helper->getToken(40);




        $sql = "INSERT INTO CENTRALE_ACHAT_v2.dbo.API_USER (AU_SECRET, AU_NAME, INS_DATE, INS_USER) VALUES (:token, :name, GETDATE(), :ins_user)";


        $conn = $connection->prepare($sql);
        $conn->bindValue('token', $tokenApi);
        $conn->bindValue('name', $app_name);
        $conn->bindValue('ins_user', "API_SITE");


        $conn->execute();
        $result = $conn->fetchAll();





        return $this->redirectToRoute('list_api_user', array(), 301);
    }

    /**
     * @Route("/user/setDroits",name="set_droits_user" )
     * @Method("POST")
     */
    public function setDroitsApiUser( DbService $db, Request $request){


        $client = $request->request->get('clients');

        dump($client);


        return new JsonResponse('ok', 200);

    }

}
