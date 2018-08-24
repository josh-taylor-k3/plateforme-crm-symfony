<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

/**
 * @Route("/v1")
 */
class AppTicketsController extends Controller
{


    /**
     * @Route("/user/login", name="user_login")
     * @Method("POST")
     */
    public function user_login(Request $request, Connection $connection, Environment $twig)
    {

        $contentParam = $request->getContent();

        $requestFromJson = json_decode($contentParam);

        $email = $requestFromJson->email;
        $password = $requestFromJson->password;



        if ($email !== "" || $password !== ""){
            //Requete pour savoir si c'est un client
            $sqlIsClient = "SELECT SO_ID, CL_ID
                        FROM CENTRALE_ACHAT.dbo.Vue_All_Clients
                        WHERE CC_MAIL = :mail";

            //Requete pour savoir si c'est un fournisseur
            $sqlIsFourn = "SELECT * 
                          FROM CENTRALE_PRODUITS.dbo.FOURN_USERS
                          WHERE FC_MAIL = :mail";


            $conn = $connection->prepare($sqlIsClient);
            $conn->bindValue('mail', $email);
            $conn->execute();
            $resultClient = $conn->fetchAll();

            $conn = $connection->prepare($sqlIsFourn);
            $conn->bindValue('mail', $email);
            $conn->execute();
            $resultFourn = $conn->fetchAll();


            switch ($email){

                case !empty($resultClient):
                    $sqlCentrale = "SELECT SO_DATABASE FROM CENTRALE_ACHAT.dbo.SOCIETES
                                    WHERE SO_ID = :so_id";
                    $conn = $connection->prepare($sqlCentrale);
                    $conn->bindValue('so_id', $resultClient[0]["SO_ID"]);
                    $conn->execute();
                    $resultCentrale = $conn->fetchAll();

                    $sqlClient = sprintf("SELECT * FROM %s.dbo.CLIENTS_USERS WHERE CC_MAIL = :mail",$resultCentrale[0]["SO_DATABASE"] );

                    $conn = $connection->prepare($sqlClient);
                    $conn->bindValue('mail', $email);
                    $conn->execute();
                    $client = $conn->fetchAll();

                    if (!empty($client)){
                        if ($client[0]["CC_PASS"] === $password){



                            $array_answer = [
                                "status" => "ok",
                                "uuid" => uniqid(),
                                "type" => "client",
                                "details" => [
                                    "SO_ID" => $resultClient[0]["SO_ID"],
                                    "CL_ID" => $resultClient[0]["CL_ID"],
                                    "CC_ID" => $client[0]["CC_ID"],

                                ]
                            ];

                            return new JsonResponse($array_answer, 200);



                        }else{
                            dump("Mot de passe erroné");
                        }
                    }else{
                        dump("Le client n'a pas été trouvé dans la base de donnée");
                    }


                    break;

                case !empty($resultFourn):
                    if ( $resultFourn[0]["FC_PASS"] === $password ){


                        $array_answer = [
                            "status" => "ok",
                            "uuid" => uniqid(),
                            "type" => "Fournisseur",
                            "details" => [
                                "FO_ID" => $resultFourn[0]["FO_ID"],
                                "FC_ID" => $resultFourn[0]["FC_ID"],

                            ]
                        ];

                        return new JsonResponse($array_answer, 200);

                    }else {
                        dump("mauvais mot de passe");
                    }


                    break;

                case empty($resultFourn) && empty($resultClient):
                    $array_answer = [
                        "status" => "ko",
                    ];
                    return new JsonResponse($array_answer, 404);
                    break;
            }
        }else {
            $array_answer = [
                "status" => "ko",
                "detail" => "il y a pas d'email et de password"
            ];

            return new JsonResponse($array_answer, 404);
        }

        $array_answer = [
            "status" => "ko",
        ];

        return new JsonResponse($array_answer, 404);

    }


    /**
     * @Route("/client/details", name="client_details")
     */
    public function client_details(Request $request, Connection $connection, Environment $twig){

        $contentParam = $request->getContent();


        $sqlDetail = "SELECT * FROM CENTRALE_ACHAT.dbo.CLIENTS
                    INNER JOIN CENTRALE_ACHAT.dbo.CLIENTS_USERS on CLIENTS.CL_ID = CLIENTS_USERS.CL_ID
                    WHERE CC_ID = 207
                    ";


        return new JsonResponse("client_details", 200);
    }

}
