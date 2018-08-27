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
     * @Route("/", name="base_endpoint")
     * @Method("GET")
     */
    public function base(){

        $tpl_response = [
          "status" => "ok"
        ];

        return new JsonResponse($tpl_response, 200);
    }


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
     * @Route("/client/details/{centrale_id}/{client_id}/{user_id}", name="client_details")
     */
    public function client_details(Request $request, Connection $connection, Environment $twig, $centrale_id, $client_id, $user_id){




        $sqlCentrale = "SELECT SO_DATABASE FROM CENTRALE_ACHAT.dbo.SOCIETES
                                    WHERE SO_ID = :so_id";
        $conn = $connection->prepare($sqlCentrale);
        $conn->bindValue('so_id', $centrale_id);
        $conn->execute();
        $resultCentrale = $conn->fetchAll();


        $sqlDetail = sprintf("SELECT * FROM %s.dbo.CLIENTS
                    INNER JOIN %s.dbo.CLIENTS_USERS on CLIENTS.CL_ID = CLIENTS_USERS.CL_ID
                    WHERE CC_ID = :user_id",$resultCentrale[0]["SO_DATABASE"], $resultCentrale[0]["SO_DATABASE"] );

        $connClient = $connection->prepare($sqlDetail);
        $connClient->bindValue('user_id', $user_id);
        $connClient->execute();
        $resultClient = $connClient->fetchAll();

        return new JsonResponse($resultClient[0], 200);
    }

}
