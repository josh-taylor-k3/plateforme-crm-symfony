<?php

namespace App\Controller;

use App\Service\HelperService;
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
    public function base()
    {

        $tpl_response = [
            "status" => "ok"
        ];

        return new JsonResponse($tpl_response, 200);
    }


    /**
     * @Route("/user/login", name="user_login")
     * @Method("POST")
     */
    public function user_login(Request $request, Connection $connection, HelperService $helper)
    {

        $contentParam = $request->getContent();

        $requestFromJson = json_decode($contentParam);

        $email = $requestFromJson->email;
        $password = $requestFromJson->password;


        if ($email !== "" || $password !== "") {
            //Requete pour savoir si c'est un client
            $sqlIsClient = "SELECT SO_ID, CL_ID
                        FROM CENTRALE_ACHAT_V2.dbo.Vue_All_Clients
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


            switch ($email) {


                // c'est un client
                case !empty($resultClient):

                    $sqlCentrale = "SELECT SO_DATABASE FROM CENTRALE_ACHAT_V2.dbo.SOCIETES
                                    WHERE SO_ID = :so_id";
                    $conn = $connection->prepare($sqlCentrale);
                    $conn->bindValue('so_id', $resultClient[0]["SO_ID"]);
                    $conn->execute();
                    $resultCentrale = $conn->fetchAll();


                    $sqlClient = sprintf("SELECT * FROM %s.dbo.CLIENTS_USERS WHERE CC_MAIL = :mail", $resultCentrale[0]["SO_DATABASE"]);

                    $conn = $connection->prepare($sqlClient);
                    $conn->bindValue('mail', $email);
                    $conn->execute();
                    $client = $conn->fetchAll();

                    if (!empty($client)) {
                        if ($client[0]["CC_PASS"] === $password) {

                            //ajout token user en cour

                            $token = $helper->gen_uuid();

                            $helper->setTokenApp($resultCentrale[0]["SO_DATABASE"], $client[0]["CC_ID"], $token);


                            $array_answer = [
                                "status" => "ok",
                                "uuid" => $resultCentrale[0]["SO_DATABASE"] . "-" . $token,
                                "type" => "client",
                                "details" => [
                                    "SO_ID" => $resultClient[0]["SO_ID"],
                                    "CL_ID" => $resultClient[0]["CL_ID"],
                                    "CC_ID" => $client[0]["CC_ID"],
                                ]
                            ];

                            return new JsonResponse($array_answer, 200);

                        } else {
                            $array_answer = [
                                "status" => "ko",
                            ];

                            return new JsonResponse($array_answer, 200);

                        }
                    } else {
                        dump("Le client n'a pas été trouvé dans la base de donnée");
                    }


                    break;

                // C'est un fournisseur
                case !empty($resultFourn):
                    if ($resultFourn[0]["FC_PASS"] === $password) {


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

                    } else {
                        dump("mauvais mot de passe");
                    }


                    break;

                case empty($resultFourn) && empty($resultClient):
                    $array_answer = ["status" => "ko",];
                    return new JsonResponse($array_answer, 404);
                    break;
            }
        } else {
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
     * @Route("/client/details/{token}", name="client_details")
     * @Method("GET")
     */
    public function client_details(Request $request, Connection $connection, Environment $twig, HelperService $helper, $token)
    {


        $data_token = $helper->extractTokenDb($token);

        if (!$data_token) {
            $array_answer = [
                "status" => "ko",
            ];
            return new JsonResponse($array_answer, 404);
        }

        $cc_id = $helper->verifyTokenApp($data_token["token"], $data_token["database"]);


        if ($cc_id) {
            $sqlDetail = sprintf("SELECT * FROM %s.dbo.CLIENTS
                    INNER JOIN %s.dbo.CLIENTS_USERS on CLIENTS.CL_ID = CLIENTS_USERS.CL_ID
                    WHERE CC_ID = :user_id", $data_token["database"], $data_token["database"]);

            $connClient = $connection->prepare($sqlDetail);
            $connClient->bindValue('user_id', $cc_id);
            $connClient->execute();
            $resultClient = $connClient->fetchAll();

            $tpl_result = [
                "data" => $helper->array_utf8_encode($resultClient[0]),
                "logo" => $helper->getBaseUrl($data_token["database"]) . "/UploadFichiers/Uploads/CLIENT_" . $resultClient[0]["CL_ID"] . "/" . $resultClient[0]["CL_LOGO"],
            ];


            return new JsonResponse($tpl_result, 200);
        } else {
            $array_answer = [
                "status" => "ko",
            ];

            return new JsonResponse($array_answer, 404);

        }
    }

    /**
     * @Route("/client/message/open/{token}", name="client_message_open")
     * @Method("GET")
     */
    public function clientMessageOpen(Request $request, Connection $connection, Environment $twig, HelperService $helper, $token)
    {


        $data_token = $helper->extractTokenDb($token);

        if (!$data_token) {
            $array_answer = [
                "status" => "ko",
            ];
            return new JsonResponse($array_answer, 404);
        }

        $cc_id = $helper->verifyTokenApp($data_token["token"], $data_token["database"]);


        if ($cc_id) {
            $sqlNiveau = sprintf("SELECT CC_NIVEAU, CL_ID FROM %s.dbo.CLIENTS_USERS WHERE CC_ID = :cc_id", $data_token["database"]);

            $connClient = $connection->prepare($sqlNiveau);
            $connClient->bindValue('cc_id', $cc_id);
            $connClient->execute();
            $resultNiveau = $connClient->fetchAll();


            if ($resultNiveau[0]["CC_NIVEAU"] == 1) {
                //user no level

                $sqlMessagesList = sprintf("SELECT ME_ID, (SELECT CL_RAISONSOC FROM %s.dbo.CLIENTS WHERE CLIENTS.CL_ID = MESSAGE_ENTETE.CL_ID) as CL, ME_SUJET, (SELECT CC_PRENOM FROM %s.dbo.CLIENTS_USERS WHERE CLIENTS_USERS.CL_ID = MESSAGE_ENTETE.CL_ID AND CLIENTS_USERS.CC_ID = MESSAGE_ENTETE.CC_ID) as CC_PRENOM, (SELECT CC_NOM FROM %s.dbo.CLIENTS_USERS WHERE CLIENTS_USERS.CL_ID = MESSAGE_ENTETE.CL_ID AND CLIENTS_USERS.CC_ID = MESSAGE_ENTETE.CC_ID) as CC_NOM, MAJ_DATE, (SELECT CL_LOGO FROM %s.dbo.CLIENTS WHERE CLIENTS.CL_ID = MESSAGE_ENTETE.CL_ID) as LOGO, CL_ID,  (SELECT '') as logo_url, (SELECT CL_RAISONSOC FROM %s.dbo.CLIENTS WHERE CLIENTS.CL_ID = MESSAGE_ENTETE.CL_ID) as raison_soc

                                                    FROM %s.dbo.MESSAGE_ENTETE
                                                    WHERE CL_ID = :cl_id
                                                    AND CC_ID = :cc_id
                                                    AND ME_STATUS < 2
                                                    ORDER BY MAJ_DATE DESC ", $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"]);

                $connClient = $connection->prepare($sqlMessagesList);
                $connClient->bindValue('cl_id', $resultNiveau[0]["CL_ID"]);
                $connClient->bindValue('cc_id', $cc_id);
                $connClient->execute();
                $resultNiveau = $connClient->fetchAll();


                $res_final = [];

                foreach ($resultNiveau as $res) {

                    $tpl_temp = [
                        "messages_id" => $res["ME_ID"],
                        "message_topic" => $res["ME_SUJET"],
                        "client_firstname" => $res["CC_PRENOM"],
                        "client_lastname" => $res["CC_NOM"],
                        "last_time" => $res["MAJ_DATE"],
                        "raison_social" => $res["raison_soc"],
                        "logo_url" => $helper->getBaseUrl($data_token["database"]) . "/UploadFichiers/Uploads/CLIENT_" . $res["CL_ID"] . "/" . $res["LOGO"],
                    ];

                    array_push($res_final, $tpl_temp);
                }


                return new JsonResponse($res_final, 200);


            } else if ($resultNiveau[0]["CC_NIVEAU"] == 0) {
                // user is admin

                $sqlMessagesList = sprintf("SELECT ME_ID, (SELECT CL_RAISONSOC FROM %s.dbo.CLIENTS WHERE CLIENTS.CL_ID = MESSAGE_ENTETE.CL_ID) as CL, ME_SUJET, (SELECT CC_PRENOM FROM %s.dbo.CLIENTS_USERS WHERE CLIENTS_USERS.CL_ID = MESSAGE_ENTETE.CL_ID AND CLIENTS_USERS.CC_ID = MESSAGE_ENTETE.CC_ID) as CC_PRENOM, (SELECT CC_NOM FROM %s.dbo.CLIENTS_USERS WHERE CLIENTS_USERS.CL_ID = MESSAGE_ENTETE.CL_ID AND CLIENTS_USERS.CC_ID = MESSAGE_ENTETE.CC_ID) as CC_NOM, MAJ_DATE, (SELECT CL_LOGO FROM %s.dbo.CLIENTS WHERE CLIENTS.CL_ID = MESSAGE_ENTETE.CL_ID) as LOGO, CL_ID,  (SELECT '') as logo_url, (SELECT CL_RAISONSOC FROM %s.dbo.CLIENTS WHERE CLIENTS.CL_ID = MESSAGE_ENTETE.CL_ID) as raison_soc, FO_ID,
       (SELECT FO_LOGO FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS WHERE FOURNISSEURS.FO_ID = MESSAGE_ENTETE.FO_ID) as FC_LOGO
                                                    FROM %s.dbo.MESSAGE_ENTETE
                                                    WHERE CL_ID = :cl_id
                                                    AND ME_STATUS < 2
                                                    ORDER BY MAJ_DATE DESC ", $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"]);


                $connClient = $connection->prepare($sqlMessagesList);
                $connClient->bindValue('cl_id', $resultNiveau[0]["CL_ID"]);
                $connClient->execute();
                $resultNiveau = $connClient->fetchAll();

                $res_final = [];

                foreach ($resultNiveau as $res) {

                    $tpl_temp = [
                        "messages_id" => $res["ME_ID"],
                        "message_topic" => $res["ME_SUJET"],
                        "client_firstname" => $res["CC_PRENOM"],
                        "client_lastname" => $res["CC_NOM"],
                        "last_time" => $res["MAJ_DATE"],
                        "raison_social" => $res["raison_soc"],
                        "logo_url" => "http://secure.achatcentrale.fr/UploadFichiers/Uploads/FOURN_" . $res["FO_ID"] . "/" . $res["FC_LOGO"],
                    ];

                    array_push($res_final, $tpl_temp);
                }


                return new JsonResponse($res_final, 200);
            }


        } else {
            $array_answer = [
                "status" => "ko",
            ];

            return new JsonResponse($array_answer, 404);

        }
    }


    /**
     * @Route("/client/message/archived/{token}", name="client_message_archived")
     * @Method("GET")
     */
    public function clientMessageArchived(Request $request, Connection $connection, Environment $twig, HelperService $helper, $token)
    {


        $data_token = $helper->extractTokenDb($token);

        if (!$data_token) {
            $array_answer = [
                "status" => "ko",
            ];
            return new JsonResponse($array_answer, 404);
        }

        $cc_id = $helper->verifyTokenApp($data_token["token"], $data_token["database"]);


        if ($cc_id) {
            $sqlNiveau = sprintf("SELECT CC_NIVEAU, CL_ID FROM %s.dbo.CLIENTS_USERS WHERE CC_ID = :cc_id", $data_token["database"]);

            $connClient = $connection->prepare($sqlNiveau);
            $connClient->bindValue('cc_id', $cc_id);
            $connClient->execute();
            $resultNiveau = $connClient->fetchAll();


            if ($resultNiveau[0]["CC_NIVEAU"] == 1) {
                //user no level

                $sqlMessagesList = sprintf("SELECT ME_ID, (SELECT CL_RAISONSOC FROM %s.dbo.CLIENTS WHERE CLIENTS.CL_ID = MESSAGE_ENTETE.CL_ID) as CL, ME_SUJET, (SELECT CC_PRENOM FROM %s.dbo.CLIENTS_USERS WHERE CLIENTS_USERS.CL_ID = MESSAGE_ENTETE.CL_ID AND CLIENTS_USERS.CC_ID = MESSAGE_ENTETE.CC_ID) as CC_PRENOM, (SELECT CC_NOM FROM %s.dbo.CLIENTS_USERS WHERE CLIENTS_USERS.CL_ID = MESSAGE_ENTETE.CL_ID AND CLIENTS_USERS.CC_ID = MESSAGE_ENTETE.CC_ID) as CC_NOM, MAJ_DATE, (SELECT CL_LOGO FROM %s.dbo.CLIENTS WHERE CLIENTS.CL_ID = MESSAGE_ENTETE.CL_ID) as LOGO, CL_ID,  (SELECT '') as logo_url, (SELECT CL_RAISONSOC FROM %s.dbo.CLIENTS WHERE CLIENTS.CL_ID = MESSAGE_ENTETE.CL_ID) as raison_soc, FO_ID,
       (SELECT FO_LOGO FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS WHERE FOURNISSEURS.FO_ID = MESSAGE_ENTETE.FO_ID) as FC_LOGO
                                                    FROM %s.dbo.MESSAGE_ENTETE
                                                    WHERE CL_ID = :cl_id
                                                    AND CC_ID = :cc_id
                                                    AND ME_STATUS = 2
                                                    ORDER BY MAJ_DATE DESC ", $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"]);


                $connClient = $connection->prepare($sqlMessagesList);
                $connClient->bindValue('cl_id', $resultNiveau[0]["CL_ID"]);
                $connClient->bindValue('cc_id', $cc_id);
                $connClient->execute();
                $resultNiveau = $connClient->fetchAll();


                $res_final = [];

                foreach ($resultNiveau as $res) {

                    $tpl_temp = [
                        "messages_id" => $res["ME_ID"],
                        "message_topic" => $res["ME_SUJET"],
                        "client_firstname" => $res["CC_PRENOM"],
                        "client_lastname" => $res["CC_NOM"],
                        "last_time" => $res["MAJ_DATE"],
                        "raison_social" => $res["raison_soc"],
                        "logo_url" => "http://secure.achatcentrale.fr/UploadFichiers/Uploads/FOURN_" . $res["FO_ID"] . "/" . $res["FC_LOGO"],
                    ];

                    array_push($res_final, $tpl_temp);
                }


                return new JsonResponse($res_final, 200);

            } else if ($resultNiveau[0]["CC_NIVEAU"] == 0) {
                // user is admin

                $sqlMessagesList = sprintf("SELECT ME_ID, (SELECT CL_RAISONSOC FROM %s.dbo.CLIENTS WHERE CLIENTS.CL_ID = MESSAGE_ENTETE.CL_ID) as CL, ME_SUJET, (SELECT CC_PRENOM FROM %s.dbo.CLIENTS_USERS WHERE CLIENTS_USERS.CL_ID = MESSAGE_ENTETE.CL_ID AND CLIENTS_USERS.CC_ID = MESSAGE_ENTETE.CC_ID) as CC_PRENOM, (SELECT CC_NOM FROM %s.dbo.CLIENTS_USERS WHERE CLIENTS_USERS.CL_ID = MESSAGE_ENTETE.CL_ID AND CLIENTS_USERS.CC_ID = MESSAGE_ENTETE.CC_ID) as CC_NOM, MAJ_DATE, (SELECT CL_LOGO FROM %s.dbo.CLIENTS WHERE CLIENTS.CL_ID = MESSAGE_ENTETE.CL_ID) as LOGO, CL_ID,  (SELECT '') as logo_url, (SELECT CL_RAISONSOC FROM %s.dbo.CLIENTS WHERE CLIENTS.CL_ID = MESSAGE_ENTETE.CL_ID) as raison_soc, FO_ID,
       (SELECT FO_LOGO FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS WHERE FOURNISSEURS.FO_ID = MESSAGE_ENTETE.FO_ID) as FC_LOGO
                                                    FROM %s.dbo.MESSAGE_ENTETE
                                                    WHERE CL_ID = :cl_id
                                                    AND ME_STATUS = 2
                                                    ORDER BY INS_DATE DESC ", $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"]);


                $connClient = $connection->prepare($sqlMessagesList);
                $connClient->bindValue('cl_id', $resultNiveau[0]["CL_ID"]);
                $connClient->execute();
                $resultNiveau = $connClient->fetchAll();

                $res_final = [];

                foreach ($resultNiveau as $res) {


                    $tpl_temp = [
                        "messages_id" => $res["ME_ID"],
                        "message_topic" => $res["ME_SUJET"],
                        "client_firstname" => $res["CC_PRENOM"],
                        "client_lastname" => $res["CC_NOM"],
                        "last_time" => $res["MAJ_DATE"],
                        "raison_social" => $res["raison_soc"],
                        "logo_url" => "http://secure.achatcentrale.fr/UploadFichiers/Uploads/FOURN_" . $res["FO_ID"] . "/" . $res["FC_LOGO"],
                    ];

                    array_push($res_final, $tpl_temp);
                }


                return new JsonResponse($res_final, 200);
            }


        } else {
            $array_answer = [
                "status" => "ko",
            ];

            return new JsonResponse($array_answer, 404);

        }
    }

    /**
     * @Route("/client/messages/{token}/{me_id}", name="client_message_details")
     * @Method("GET")
     */
    public function clientMessageDetails(Request $request, Connection $connection, Environment $twig, HelperService $helper, $token, $me_id)
    {

        $data_token = $helper->extractTokenDb($token);


        // si il existe un token et un SO_DATABASE
        if (!$data_token) {
            $array_answer = [
                "status" => "ko",
            ];
            return new JsonResponse($array_answer, 404);
        }


        //on verifie le token et on extrait le user
        $cc_id = $helper->verifyTokenApp($data_token["token"], $data_token["database"]);


        if ($cc_id) {


            $sqlNiveau = sprintf("SELECT ME_ID,
                                                   MD_ID,
                                                   MD_CORPS,
                                                   (SELECT CC_PRENOM + ' ' + CC_NOM FROM CENTRALE_ACHAT_v2.dbo.CLIENTS_USERS WHERE CC_ID = MESSAGE_DETAIL.CC_ID) as fourn,
                                                   (SELECT FC_NOM + ' ' + FC_PRENOM FROM CENTRALE_PRODUITS.dbo.FOURN_USERS WHERE FC_ID = MESSAGE_DETAIL.FC_ID) as client,
                                                   INS_DATE,
                                                   (SELECT CL_ID
                                                    FROM %s.dbo.CLIENTS
                                                    WHERE CL_ID = (SELECT CL_ID
                                                                   FROM %s.dbo.CLIENTS_USERS
                                                                   WHERE CLIENTS_USERS.CC_ID = %s.dbo.MESSAGE_DETAIL.CC_ID)) AS client_id,
                                                   (SELECT FO_ID
                                                    FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS
                                                    WHERE FO_ID = (SELECT FO_ID
                                                                   FROM CENTRALE_PRODUITS.dbo.FOURN_USERS
                                                                   WHERE FOURN_USERS.FC_ID = %s.dbo.MESSAGE_DETAIL.FC_ID))   AS fournisseur_id,
                                                   (SELECT CL_RAISONSOC
                                                    FROM %s.dbo.CLIENTS
                                                    WHERE CL_ID = (SELECT CL_ID
                                                                   FROM %s.dbo.CLIENTS_USERS
                                                                   WHERE CLIENTS_USERS.CC_ID = %s.dbo.MESSAGE_DETAIL.CC_ID)) AS Raison_soc_client,
                                                   (SELECT CL_RAISONSOC
                                                    FROM %s.dbo.CLIENTS
                                                    WHERE CL_ID = (SELECT CL_ID
                                                                   FROM %s.dbo.CLIENTS_USERS
                                                                   WHERE CLIENTS_USERS.CC_ID = %s.dbo.MESSAGE_DETAIL.CC_ID)) AS Raison_soc_client,
                                                   (SELECT FO_RAISONSOC
                                                    FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS
                                                    WHERE FO_ID = (SELECT FO_ID
                                                                   FROM CENTRALE_PRODUITS.dbo.FOURN_USERS
                                                                   WHERE FOURN_USERS.FC_ID = %s.dbo.MESSAGE_DETAIL.FC_ID))   AS Raison_soc_fourn,
                                                   (SELECT FO_LOGO
                                                    FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS
                                                    WHERE FO_ID = (SELECT FO_ID
                                                                   FROM CENTRALE_PRODUITS.dbo.FOURN_USERS
                                                                   WHERE FOURN_USERS.FC_ID = %s.dbo.MESSAGE_DETAIL.FC_ID))   AS logo_fourn,
                                                   (SELECT CL_LOGO
                                                    FROM %s.dbo.CLIENTS
                                                    WHERE CL_ID = (SELECT CL_ID
                                                                   FROM %s.dbo.CLIENTS_USERS
                                                                   WHERE CLIENTS_USERS.CC_ID = %s.dbo.MESSAGE_DETAIL.CC_ID)) AS logo_client
                                            FROM %s.dbo.MESSAGE_DETAIL
                                            WHERE ME_ID = :me_id
                                            order by MD_DATE DESC", $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"]);

            $connClient = $connection->prepare($sqlNiveau);
            $connClient->bindValue('me_id', $me_id);
            $connClient->execute();
            $resultMessageDetails = $connClient->fetchAll();


            $res_final = [];

            foreach ($resultMessageDetails as $res) {

                $typeMessage = $helper->getTypeMessage($res["Raison_soc_client"], $res["Raison_soc_fourn"]);

                $logo = ($typeMessage == "client") ? "http://v2.achatcentrale.fr/UploadFichiers/Uploads/CLIENT_" . $res["client_id"] . "/" .  $res["logo_client"] : (($typeMessage == "fournisseur") ? "http://secure.achatcentrale.fr/UploadFichiers/Uploads/FOURN_" . $res["fournisseur_id"] . "/" .  $res["logo_fourn"] : '');



                $tpl_temp = [
                    "ID" => $res["MD_ID"],
                    "thread_id" => $res["ME_ID"],
                    "corps" => $res["MD_CORPS"],
                    "type" => $typeMessage,
                    "logo" => $logo,
                    "date" =>  $res["INS_DATE"],
                    "client" =>  $res["client"],
                    "fournisseur" =>  $res["fourn"],
                ];

                array_push($res_final, $tpl_temp);
            }


            return new JsonResponse($res_final, 200);

        } else {
            $array_answer = [
                "status" => "ko",
            ];

            return new JsonResponse($array_answer, 404);

        }


    }

}
