<?php

namespace App\Controller;

use App\Service\HelperService;
use App\Service\LoginHelperService;
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
    public function user_login(Request $request, Connection $connection, HelperService $helper, LoginHelperService $loginHelper)
    {

        $contentParam = $request->getContent();

        $requestFromJson = json_decode($contentParam);

        $email = $requestFromJson->email;
        $password = $requestFromJson->password;


        if ($email !== "" || $password !== "") {


            //on determine si c'est un client
            $resultClient = $loginHelper->isClient($email, $password);

            // Si il s'agit d'un client on lui ajoute un token de connexion
            if (!empty($resultClient)) {

                // On génere un token
                $token = $helper->gen_uuid();
                $database = $helper->getCentrale($resultClient["centrale"]);
                $helper->setTokenApp("CENTRALE_ACHAT_V2", $resultClient["data"]["CC_ID"], $token);


                $valideur = $loginHelper->isValideur($email, $database["SO_DATABASE"]);


                // on envois les données pour l'application
                $array_answer = [
                    "status" => "ok",
                    "uuid" => "CENTRALE_ACHAT_V2-" . $token,
                    "type" => "client",
                    "details" => [
                        "SO_ID" => $resultClient["centrale"],
                        "CL_ID" => $resultClient["data"]["CL_ID"],
                        "CC_ID" => $resultClient["data"]["CC_ID"],
                        "valideur" => $valideur,
                    ]
                ];

                return new JsonResponse($array_answer, 200);
            }

            $resultFourn = $loginHelper->isFourn($email, $password);

            if (!empty($resultFourn)) {
                $token = $helper->gen_uuid();

                $helper->setTokenApp("CENTRALE_PRODUITS", $resultFourn["FC_ID"], $token);

                $array_answer = [
                    "status" => "ok",
                    "uuid" => $token,
                    "type" => "fournisseur",
                    "details" => [
                        "FO_ID" => $resultFourn["FO_ID"],
                        "FC_ID" => $resultFourn["FC_ID"],
                    ]
                ];

                return new JsonResponse($array_answer, 200);
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
    public function client_details(Request $request, Connection $connection, HelperService $helper, $token)
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
     * @Route("/fourn/details/{token}", name="fourn_details")
     * @Method("GET")
     */
    public function fourn_details(Request $request, Connection $connection, HelperService $helper, $token)
    {

        $data_token = $helper->extractTokenDb($token);

        if (!$data_token) {
            $array_answer = [
                "status" => "ko",
            ];
            return new JsonResponse($array_answer, 404);
        }

        $fc_id = $helper->verifyTokenApp($data_token["token"], $data_token["database"]);

        if ($fc_id) {
            $sqlDetail = "SELECT * FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS
                                            INNER JOIN CENTRALE_PRODUITS.dbo.FOURN_USERS on FOURNISSEURS.FO_ID = FOURN_USERS.FO_ID
                                            WHERE FC_ID = :fc_id";

            $connFourn = $connection->prepare($sqlDetail);
            $connFourn->bindValue('fc_id', $fc_id);
            $connFourn->execute();
            $resultClient = $connFourn->fetchAll();


            $tpl_result = [
                "data" => $helper->array_utf8_encode($resultClient[0]),
                "logo" => "http://secure.achatcentrale.fr/UploadFichiers/Uploads/FOURN_" . $resultClient[0]["FO_ID"] . "/" . $resultClient[0]["FO_LOGO"],
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
    public function clientMessageOpen(Request $request, Connection $connection, HelperService $helper, $token)
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
                //user is admin

                $sqlMessagesList = sprintf("SELECT ME_ID,
                        (SELECT CL_RAISONSOC FROM %s.dbo.CLIENTS WHERE CLIENTS.CL_ID = MESSAGE_ENTETE.CL_ID) as CL,
                        ME_SUJET,
                        (SELECT FC_PRENOM FROM CENTRALE_PRODUITS.dbo.FOURN_USERS WHERE FOURN_USERS.FC_ID = MESSAGE_ENTETE.FC_ID AND FOURN_USERS.FO_ID = MESSAGE_ENTETE.FO_ID) as FC_PRENOM,
                        (SELECT FC_NOM FROM CENTRALE_PRODUITS.dbo.FOURN_USERS WHERE FOURN_USERS.FC_ID = MESSAGE_ENTETE.FC_ID AND FOURN_USERS.FO_ID = MESSAGE_ENTETE.FO_ID) as FC_NOM,
                        MAJ_DATE,
                        CL_ID,
                        (SELECT FO_RAISONSOC FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS WHERE FOURNISSEURS.FO_ID = MESSAGE_ENTETE.FO_ID) as raison_soc,
                        FO_ID,
                        (SELECT FO_LOGO FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS WHERE MESSAGE_ENTETE.FO_ID = FO_ID) as fourn_logo,
                        ME_LU_C
                FROM %s.dbo.MESSAGE_ENTETE
                WHERE CL_ID = :cl_id
                  AND CC_ID = :cc_id
                  AND ME_STATUS < 2
                ORDER BY ME_LU_C ASC, ME_DATE DESC",
                    $data_token["database"], $data_token["database"]);

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
                        "fourn_firstname" => $helper->array_utf8_encode($res["FC_PRENOM"]),
                        "fourn_lastname" => $helper->array_utf8_encode($res["FC_NOM"]),
                        "last_time" => $res["MAJ_DATE"],
                        "raison_social" => $helper->array_utf8_encode($res["raison_soc"]),
                        "logo_url" => "http://secure.achatcentrale.fr/UploadFichiers/Uploads/FOURN_" . $res["FO_ID"] . "/" . $res["fourn_logo"],
                        "Unread" => $res["ME_LU_C"] === 1 ? false : true,
                    ];

                    array_push($res_final, $tpl_temp);
                }


                return new JsonResponse($res_final, 200);


                //TODO: Refaire si user sans niveau
            } else if ($resultNiveau[0]["CC_NIVEAU"] == 0) {
                // user no level

                $sqlMessagesList = sprintf("SELECT ME_ID, (SELECT CL_RAISONSOC FROM %s.dbo.CLIENTS WHERE CLIENTS.CL_ID = MESSAGE_ENTETE.CL_ID) as CL, ME_SUJET, (SELECT CC_PRENOM FROM %s.dbo.CLIENTS_USERS WHERE CLIENTS_USERS.CL_ID = MESSAGE_ENTETE.CL_ID AND CLIENTS_USERS.CC_ID = MESSAGE_ENTETE.CC_ID) as CC_PRENOM, (SELECT CC_NOM FROM %s.dbo.CLIENTS_USERS WHERE CLIENTS_USERS.CL_ID = MESSAGE_ENTETE.CL_ID AND CLIENTS_USERS.CC_ID = MESSAGE_ENTETE.CC_ID) as CC_NOM, MAJ_DATE, (SELECT CL_LOGO FROM %s.dbo.CLIENTS WHERE CLIENTS.CL_ID = MESSAGE_ENTETE.CL_ID) as LOGO, CL_ID,  (SELECT '') as logo_url, (SELECT CL_RAISONSOC FROM %s.dbo.CLIENTS WHERE CLIENTS.CL_ID = MESSAGE_ENTETE.CL_ID) as raison_soc, FO_ID, (SELECT FO_LOGO FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS WHERE FOURNISSEURS.FO_ID = MESSAGE_ENTETE.FO_ID) as FC_LOGO

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
                        "fourn_firstname" => $res["FC_PRENOM"],
                        "fourn_lastname" => $res["FC_NOM"],
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


            if ($resultNiveau[0]["CC_NIVEAU"] == 0) {
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

            } else if ($resultNiveau[0]["CC_NIVEAU"] == 1) {
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
    public function clientMessageDetails(Request $request, Connection $connection, HelperService $helper, $token, $me_id)
    {
        $data_token = $helper->extractTokenDb($token);

        // si il existe un token et un SO_DATABASE
        if (!$data_token) {
            $array_answer = [
                "status" => "ko",
            ];
            return new JsonResponse($array_answer, 404);
        }


        // PARTIE CLIENT
        //on verifie le token et on extrait le user
        $cc_id = $helper->verifyTokenApp($data_token["token"], $data_token["database"]);


        if ($cc_id) {

            $sqlNiveau = sprintf("SELECT ME_ID,
                                                   MD_ID,
                                                   CAST(MD_CORPS AS TEXT) AS MD_CORPS,
                                                   (SELECT CC_PRENOM + ' ' + CC_NOM FROM CENTRALE_ACHAT_v2.dbo.CLIENTS_USERS WHERE CC_ID = MESSAGE_DETAIL.CC_ID) as client,
                                                   (SELECT FC_NOM + ' ' + FC_PRENOM FROM CENTRALE_PRODUITS.dbo.FOURN_USERS WHERE FC_ID = MESSAGE_DETAIL.FC_ID) as fourn,
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
                                            order by MD_DATE ASC", $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"]);

            $connClient = $connection->prepare($sqlNiveau);
            $connClient->bindValue('me_id', $me_id);
            $connClient->execute();
            $resultMessageDetails = $connClient->fetchAll();


            $res_final = [];

            foreach ($resultMessageDetails as $res) {

                $typeMessage = $helper->getTypeMessage($res["Raison_soc_client"], $res["Raison_soc_fourn"]);
                $logo = ($typeMessage == "client") ? "http://v2.achatcentrale.fr/UploadFichiers/Uploads/CLIENT_" . $res["client_id"] . "/" . $res["logo_client"] : (($typeMessage == "fournisseur") ? "http://secure.achatcentrale.fr/UploadFichiers/Uploads/FOURN_" . $res["fournisseur_id"] . "/" . $res["logo_fourn"] : '');


                $tpl_temp = [
                    "ID" => $res["MD_ID"],
                    "thread_id" => $res["ME_ID"],
                    "corps" => $helper->array_utf8_encode($res["MD_CORPS"]),
                    "isIncoming" => $typeMessage,
                    "logo" => $logo,
                    "date" => $res["INS_DATE"],
                    "client" => $res["client"],
                    "fournisseur" => $res["fourn"],
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


    /**
     * @Route("/client/info/{token}/{me_id}", name="client_message_info")
     * @Method("GET")
     */
    public function senderMessageDetails(Request $request, Connection $connection, HelperService $helper, $token, $me_id)
    {
        $data_token = $helper->extractTokenDb($token);

        // si il existe un token et un SO_DATABASE
        if (!$data_token) {
            $array_answer = [
                "status" => "ko",
            ];
            return new JsonResponse($array_answer, 404);
        }


        $sql = "SELECT FO_RAISONSOC, FOURNISSEURS.FO_LOGO, FOURNISSEURS.FO_ID  FROM CENTRALE_ACHAT_v2.dbo.MESSAGE_ENTETE LEFT OUTER JOIN CENTRALE_PRODUITS.dbo.FOURNISSEURS ON MESSAGE_ENTETE.FO_ID = FOURNISSEURS.FO_ID  WHERE MESSAGE_ENTETE.ME_ID = :id";

        $connClient = $connection->prepare($sql);
        $connClient->bindValue('id', $me_id);
        $connClient->execute();
        $resultMessageInfo = $connClient->fetchAll();


        $array_final = [
            "raisonsoc" => $resultMessageInfo[0]["FO_RAISONSOC"],
            "logo" => "http://secure.achatcentrale.fr/UploadFichiers/Uploads/FOURN_" . urlencode($resultMessageInfo[0]["FO_ID"]) . '/' . urlencode($resultMessageInfo[0]["FO_LOGO"]),
        ];

        return $this->json($array_final, 200);

    }


    /**
     * @Route("/fourn/message/open/{token}", name="fourn_message_open")
     * @Method("GET")
     */
    public function fournMessageOpen(Request $request, Connection $connection, HelperService $helper, $token)
    {

        $data_token = $helper->extractTokenDb($token);

        if (!$data_token) {
            $array_answer = [
                "status" => "ko",
            ];
            return new JsonResponse($array_answer, 404);
        }

        $fc_id = $helper->verifyTokenApp($data_token["token"], $data_token["database"]);

        $sqlMessagesList = sprintf("SELECT ME_ID,
                                           (SELECT CL_RAISONSOC FROM %s.dbo. CLIENTS WHERE CLIENTS.CL_ID = MESSAGE_ENTETE.CL_ID) as CL,
                                           ME_SUJET,
                                           (SELECT CC_PRENOM
                                            FROM %s.dbo.CLIENTS_USERS
                                            WHERE CLIENTS_USERS.CC_ID = MESSAGE_ENTETE.CC_ID
                                              AND CLIENTS_USERS.CL_ID = MESSAGE_ENTETE.CL_ID)                                                     as CC_PRENOM,
                                           (SELECT CC_NOM
                                            FROM %s.dbo.CLIENTS_USERS
                                            WHERE CLIENTS_USERS.CC_ID = MESSAGE_ENTETE.CC_ID
                                              AND CLIENTS_USERS.CL_ID = MESSAGE_ENTETE.CL_ID)                                                     as CC_NOM,
                                           MAJ_DATE,
                                           CL_ID,
                                           FO_ID,
                                           (SELECT CL_LOGO
                                            FROM %s.dbo.CLIENTS
                                            WHERE MESSAGE_ENTETE.CL_ID = CL_ID)                                                                 as client_logo
                                        FROM %s.dbo. MESSAGE_ENTETE
                                        WHERE FC_ID = :fc_id
                                          AND FO_ID = :fo_id
                                          AND ME_STATUS < 2
                                        ORDER BY MAJ_DATE DESC", $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"], $data_token["database"]);


        $fo_id = $helper->getFournFromUser($fc_id);

        $connClient = $connection->prepare($sqlMessagesList);
        $connClient->bindValue('fo_id', $fo_id["FO_ID"]);
        $connClient->bindValue('fc_id', $fc_id);
        $connClient->execute();
        $resultMessageOpen = $connClient->fetchAll();


        $res_final = [];

        foreach ($resultMessageOpen as $res) {
            $tpl_temp = [
                "messages_id" => $res["ME_ID"],
                "message_topic" => $res["ME_SUJET"],
                "fourn_firstname" => $res["CC_PRENOM"],
                "fourn_lastname" => $res["CC_NOM"],
                "last_time" => $res["MAJ_DATE"],
                "raison_social" => $helper->array_utf8_encode($res["CL"]),
                "logo_url" => "http://secure.achatcentrale.fr/UploadFichiers/Uploads/CLIENT_" . $res["CL_ID"] . "/" . $res["client_logo"],
            ];

            array_push($res_final, $tpl_temp);
        }


        return $this->json($res_final, 200);


    }


    /**
     * @Route("/client/message/new", name="client_message_new")
     * @Method("POST")
     */
    public function newClientMessage(Request $request, Connection $connection, HelperService $helper)
    {

        $contentParam = $request->getContent();

        $requestFromJson = json_decode($contentParam);

        $thread_id = $requestFromJson->thread_id;
        $token = $requestFromJson->token;
        $corps = $requestFromJson->corps;

        $data_token = $helper->extractTokenDb($token);

        $cc_id = $helper->verifyTokenApp($data_token["token"], $data_token["database"]);

        $cc_mail = $helper->getMailFromCCID($cc_id);

        if (!$data_token) {
            $array_answer = [
                "status" => "ko",
            ];
            return new JsonResponse($array_answer, 404);
        }

        if (!$cc_id) {
            $array_answer = [
                "status" => "ko",
            ];
            return new JsonResponse($array_answer, 404);
        }

        if (!$corps) {
            $array_answer = [
                "status" => "ko",
            ];
            return new JsonResponse($array_answer, 404);
        }

        if (!$token) {
            $array_answer = [
                "status" => "ko",
            ];
            return new JsonResponse($array_answer, 404);
        }

        if (!$thread_id) {
            $array_answer = [
                "status" => "ko",
            ];
            return new JsonResponse($array_answer, 404);
        }


        $sql = "INSERT INTO CENTRALE_ACHAT_v2.dbo.MESSAGE_DETAIL (ME_ID,CC_ID, US_ID, MD_DATE, MD_CORPS, INS_DATE, INS_USER) VALUES ( :thread_id, :cc_id, 0, GETDATE(), :corps, GETDATE(), :cc_mail)";

        $connClient = $connection->prepare($sql);
        $connClient->bindValue('thread_id', $thread_id);
        $connClient->bindValue('cc_id', $cc_id);
        $connClient->bindValue('corps', $corps);
        $connClient->bindValue('cc_mail', $cc_mail["CC_MAIL"]);
        $connClient->execute();
        $result = $connClient->fetchAll();


        $result_tpl = [
            "status" => "ok"
        ];

        return $this->json($result_tpl, 200);
    }


    /**
     * @Route("/client/contact/{token}", name="contact_client")
     * @Method("GET")
     */
    public function contactClient(Request $request, Connection $connection, HelperService $helper, $token)
    {

        $data_token = $helper->extractTokenDb($token);

        if (!$data_token) {
            $array_answer = [
                "status" => "ko",
            ];
            return new JsonResponse($array_answer, 404);
        }

        $cc_id = $helper->verifyTokenApp($data_token["token"], $data_token["database"]);

        $sqlRegions = "SELECT
                      CL_ID,
                      (SELECT RE_ID FROM CENTRALE_ACHAT_v2.dbo.CLIENTS WHERE CENTRALE_ACHAT_v2.dbo.CLIENTS_USERS.CL_ID = CENTRALE_ACHAT_v2.dbo.CLIENTS.CL_ID) as re_id
                FROM
                      CENTRALE_ACHAT_v2.dbo.CLIENTS_USERS
                WHERE
                      CC_ID = :id";

        $connClient = $connection->prepare($sqlRegions);
        $connClient->bindValue('id', $cc_id);
        $connClient->execute();
        $result = $connClient->fetchAll();

        $sqlFourn = "SELECT
                        CENTRALE_PRODUITS.dbo.FOURNISSEURS.FO_ID,
                        CENTRALE_PRODUITS.dbo.FOURNISSEURS.FO_RAISONSOC
                    FROM
                         CENTRALE_ACHAT_v2.dbo.REGIONS_FOURNISSEURS
                    INNER JOIN
                           CENTRALE_PRODUITS.dbo.FOURNISSEURS on CENTRALE_ACHAT_v2.dbo.REGIONS_FOURNISSEURS.FO_ID = CENTRALE_PRODUITS.dbo.FOURNISSEURS.FO_ID
                    WHERE
                          RE_ID = :re_id
                    ORDER BY FO_RAISONSOC";

        $connClient = $connection->prepare($sqlFourn);
        $connClient->bindValue('re_id', $result[0]["re_id"]);
        $connClient->execute();
        $resultListFourn = $connClient->fetchAll();

        $arrayFinal = [
            "Fournisseurs" => [],
            "Sections" => [],
        ];

        $arraySort = [];
        $arrayLetter = [];
        $previous = null;

        foreach($resultListFourn as $value) {
            $firstLetter = substr($value["FO_RAISONSOC"], 0, 1);
            if($previous !== $firstLetter) {

                array_push($arrayLetter, $firstLetter);

                $previous = $firstLetter;
                $arraySort[$firstLetter] = [];
            }
            array_push($arraySort[$firstLetter], $helper->array_utf8_encode($value));
        }

        foreach ($arraySort as $ar){
            array_push($arrayFinal["Fournisseurs"], $ar);
        }

        array_push($arrayFinal["Sections"], $arrayLetter);

        return $this->json($arrayFinal, 200);
    }

    /**
     * @Route("/client/message/seen/{me_id}/{token}", name="client_message_seen")
     * @Method("GET")
     */
    public function didMessageSeenClient(Request $request, Connection $connection, HelperService $helper, $me_id, $token)
    {

        $data_token = $helper->extractTokenDb($token);

        // si il existe un token et un SO_DATABASE
        if (!$data_token) {
            $array_answer = [
                "status" => "ko",
            ];
            return new JsonResponse($array_answer, 404);
        }


        $cc_id = $helper->verifyTokenApp($data_token["token"], $data_token["database"]);


        $sql = "UPDATE CENTRALE_ACHAT_v2.dbo.MESSAGE_ENTETE
                SET ME_LU_C = 1
                WHERE ME_ID = :id";

        $connClient = $connection->prepare($sql);
        $connClient->bindValue('id', $me_id);
        $connClient->execute();
        $result = $connClient->fetchAll();

        return $this->json($result, 200);
    }

}
