<?php

namespace App\Controller;

use App\Service\HelperService;
use Doctrine\DBAL\Driver\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class ConsommationController extends Controller
{

    /**
     * @Route("/conso/index", name="conso_index")
     */
    public function consoIndex(Connection $connection)
    {


        $sql = "SELECT FO_RAISONSOC, FO_ID FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS";
        $conn = $connection->prepare($sql);
        $conn->execute();
        $result = $conn->fetchAll();


        return $this->render('conso/index.html.twig', [
            "fournisseur" => $result
        ]);
    }


    /**
     * @Route("/consommation/{id}/{start}/{end}/{centrale}", name="consommation_client")
     */
    public function consoClient(Connection $connection, HelperService $helper, $id, $start, $end, $centrale)
    {


        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: true ");
        header("Access-Control-Allow-Headers: Content-Type, Depth, User-Agent, X-File-Size, X-Requested-With, If-Modified-Since, X-File-Name, Cache-Control, X-ac-key");


        $data = [
            "graph" => [
                "count" => 0,
                "Total" => [
                    "eco" => [],
                    "ca" => [],
                ],
                "labels" => []
            ],
            "table" => []
        ];

        $months = $helper->get_months($start, $end);
        foreach ($months as $mois) {
            array_push($data["graph"]['labels'], $mois);
        }

        $month = count($months);


        //On cherche a obtenir les mois pour avoir les noms de colones

        // variable pour contenir les mois
        $list_month = [];

        // tpl des colonnes
        $tplMoisTemp = "";

        foreach ($months as $mois) {
            //on ajoute chaque mois dans le tableau
            array_push($list_month, $mois);
            // on ajoute au tpl les th avec mois
            $tplMoisTemp .= "<th>" . $mois . "</th>";
        }

        //tpl final du tableau
        $tplDataFinal = "";



        switch ($centrale) {

            //achat centrale
            case 1:
                $sqlFourn = "SELECT DISTINCT FO_ID,
                      (SELECT FO_RAISONSOC FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS WHERE CENTRALE_PRODUITS.dbo.FOURNISSEURS.FO_ID = CENTRALE_ACHAT.dbo.CLIENTS_CONSO.FO_ID GROUP BY FO_RAISONSOC) as FO_RAISONSOC
                    FROM CENTRALE_ACHAT.dbo.CLIENTS_CONSO WHERE CL_ID = :id AND CLC_DATE BETWEEN :start AND :end";

                $conn = $connection->prepare($sqlFourn);
                $conn->bindValue(':id', $id);
                $conn->bindValue('start', $start);
                $conn->bindValue('end', $end);
                $conn->execute();
                $ListFourn = $conn->fetchAll();


                $data["graph"]["count"] = count($ListFourn);

                // chiffre d'affaires et eco total
                $ca_total = 0;
                $eco_total = 0;


                foreach ($ListFourn as $key => $fourn) {
                    $sqlConso = "SELECT
                          CLC_ID,
                          CL_ID,
                          CC_ID,
                          FO_ID,
                          CLC_DATE,
                          CLC_PRIX_PUBLIC,
                          CLC_PRIX_CENTRALE,
                          INS_DATE,
                          INS_USER ,
                          (case month(CLC_DATE)
                                WHEN 1 THEN 'Janvier'
                                WHEN 2 THEN 'Février'
                                WHEN 3 THEN 'Mars'
                                WHEN 4 THEN 'Avril'
                                WHEN 5 THEN 'Mai'
                                WHEN 6 THEN 'Juin'
                                WHEN 7 THEN 'Juillet'
                                WHEN 8 THEN 'Août'
                                WHEN 9 THEN 'Septembre'
                                WHEN 10 THEN 'Octobre'
                                WHEN 11 THEN 'Novembre'
                                ELSE 'Décembre'
                           end) 
                            as Month,
                            (month(CLC_DATE)) 
                            as Month_number
                        FROM CENTRALE_ACHAT.dbo.CLIENTS_CONSO
                        WHERE CLC_DATE BETWEEN :start AND :end
                              AND CL_ID = :id
                              AND FO_ID = :fournisseur";


                    $conn = $connection->prepare($sqlConso);
                    $conn->bindValue('id', $id);
                    $conn->bindValue('fournisseur', $fourn['FO_ID']);
                    $conn->bindValue('start', $start);
                    $conn->bindValue('end', $end);
                    $conn->execute();
                    $conso = $conn->fetchAll();


                    $cons_ca = [];
                    $cons_eco = [];

                    // on initialise a 0
                    for ($i = 0; $i < $month; $i++) {
                        array_push($cons_eco, 0);
                        array_push($cons_ca, 0);
                    }


                    //on remplace les 0 par les vraies valeur
                    for ($i = 0; $i < $month; $i++) {
                        foreach ($conso as $keyCons => $cons) {
                            if ($cons['Month'] == $months[$i]) {
                                $cons_eco[$i] = $cons['CLC_PRIX_PUBLIC'] - $cons["CLC_PRIX_CENTRALE"];
                                $cons_ca[$i] = $cons["CLC_PRIX_CENTRALE"];
                            }
                        }
                    }


                    $tpl = Array($helper->array_utf8_encode($fourn['FO_RAISONSOC']) => [
                        "id" => $fourn['FO_ID'],
                        "CA" => $cons_ca,
                        "ECO" => $cons_eco,
                        "total_ca" => array_sum($cons_ca),
                        "total_eco" => array_sum($cons_eco)
                    ]);

                    $ca_total += array_sum($cons_ca);
                    $eco_total += array_sum($cons_eco);
                    array_push($data["graph"], $tpl);


                }

                array_push($data["graph"]["Total"]["ca"], $ca_total);
                array_push($data["graph"]["Total"]["eco"], $eco_total);


                return new JsonResponse($data, 200);
            //GCCP
            case 2:

                $sqlFourn = "SELECT DISTINCT FO_ID,
                      (SELECT FO_RAISONSOC FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS WHERE CENTRALE_PRODUITS.dbo.FOURNISSEURS.FO_ID = CENTRALE_GCCP.dbo.CLIENTS_CONSO.FO_ID GROUP BY FO_RAISONSOC) as FO_RAISONSOC
                    FROM CENTRALE_GCCP.dbo.CLIENTS_CONSO WHERE CL_ID = :id AND CLC_DATE BETWEEN :start AND :end";

                $conn = $connection->prepare($sqlFourn);
                $conn->bindValue(':id', $id);
                $conn->bindValue('start', $start);
                $conn->bindValue('end', $end);
                $conn->execute();
                $ListFourn = $conn->fetchAll();


                $data["graph"]["count"] = count($ListFourn);

                // chiffre d'affaires et eco total
                $ca_total = 0;
                $eco_total = 0;


                foreach ($ListFourn as $key => $fourn) {
                    $sqlConso = "SELECT
                          CLC_ID,
                          CL_ID,
                          CC_ID,
                          FO_ID,
                          CLC_DATE,
                          CLC_PRIX_PUBLIC,
                          CLC_PRIX_CENTRALE,
                          INS_DATE,
                          INS_USER ,
                          (case month(CLC_DATE)
                                WHEN 1 THEN 'Janvier'
                                WHEN 2 THEN 'Février'
                                WHEN 3 THEN 'Mars'
                                WHEN 4 THEN 'Avril'
                                WHEN 5 THEN 'Mai'
                                WHEN 6 THEN 'Juin'
                                WHEN 7 THEN 'Juillet'
                                WHEN 8 THEN 'Août'
                                WHEN 9 THEN 'Septembre'
                                WHEN 10 THEN 'Octobre'
                                WHEN 11 THEN 'Novembre'
                                ELSE 'Décembre'
                           end) 
                            as Month,
                            (month(CLC_DATE)) 
                            as Month_number
                        FROM CENTRALE_GCCP.dbo.CLIENTS_CONSO
                        WHERE CLC_DATE BETWEEN :start AND :end
                              AND CL_ID = :id
                              AND FO_ID = :fournisseur";


                    $conn = $connection->prepare($sqlConso);
                    $conn->bindValue('id', $id);
                    $conn->bindValue('fournisseur', $fourn['FO_ID']);
                    $conn->bindValue('start', $start);
                    $conn->bindValue('end', $end);
                    $conn->execute();
                    $conso = $conn->fetchAll();


                    $cons_ca = [];
                    $cons_eco = [];

                    // on initialise a 0
                    for ($i = 0; $i < $month; $i++) {
                        array_push($cons_eco, 0);
                        array_push($cons_ca, 0);
                    }


                    //on remplace les 0 par les vraies valeur
                    for ($i = 0; $i < $month; $i++) {
                        foreach ($conso as $keyCons => $cons) {
                            if ($cons['Month'] == $months[$i]) {
                                $cons_eco[$i] = $cons['CLC_PRIX_PUBLIC'] - $cons["CLC_PRIX_CENTRALE"];
                                $cons_ca[$i] = $cons["CLC_PRIX_CENTRALE"];
                            }
                        }
                    }


                    $tpl = Array($helper->array_utf8_encode($fourn['FO_RAISONSOC']) => [
                        "id" => $fourn['FO_ID'],
                        "CA" => $cons_ca,
                        "ECO" => $cons_eco,
                        "total_ca" => array_sum($cons_ca),
                        "total_eco" => array_sum($cons_eco)
                    ]);

                    $ca_total += array_sum($cons_ca);
                    $eco_total += array_sum($cons_eco);
                    array_push($data["graph"], $tpl);


                }

                array_push($data["graph"]["Total"]["ca"], $ca_total);
                array_push($data["graph"]["Total"]["eco"], $eco_total);


                return new JsonResponse($data, 200);

                break;
            //naldeo
            case 3:

                $sqlFourn = "SELECT DISTINCT FO_ID,
                      (SELECT FO_RAISONSOC FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS WHERE CENTRALE_PRODUITS.dbo.FOURNISSEURS.FO_ID = CENTRALE_NALDEO.dbo.CLIENTS_CONSO.FO_ID GROUP BY FO_RAISONSOC) as FO_RAISONSOC
                    FROM CENTRALE_NALDEO.dbo.CLIENTS_CONSO WHERE CL_ID = :id AND CLC_DATE BETWEEN :start AND :end";

                $conn = $connection->prepare($sqlFourn);
                $conn->bindValue(':id', $id);
                $conn->bindValue('start', $start);
                $conn->bindValue('end', $end);
                $conn->execute();
                $ListFourn = $conn->fetchAll();


                $data["graph"]["count"] = count($ListFourn);

                // chiffre d'affaires et eco total
                $ca_total = 0;
                $eco_total = 0;


                foreach ($ListFourn as $key => $fourn) {
                    $sqlConso = "SELECT
                          CLC_ID,
                          CL_ID,
                          CC_ID,
                          FO_ID,
                          CLC_DATE,
                          CLC_PRIX_PUBLIC,
                          CLC_PRIX_CENTRALE,
                          INS_DATE,
                          INS_USER ,
                          (case month(CLC_DATE)
                                WHEN 1 THEN 'Janvier'
                                WHEN 2 THEN 'Février'
                                WHEN 3 THEN 'Mars'
                                WHEN 4 THEN 'Avril'
                                WHEN 5 THEN 'Mai'
                                WHEN 6 THEN 'Juin'
                                WHEN 7 THEN 'Juillet'
                                WHEN 8 THEN 'Août'
                                WHEN 9 THEN 'Septembre'
                                WHEN 10 THEN 'Octobre'
                                WHEN 11 THEN 'Novembre'
                                ELSE 'Décembre'
                           end) 
                            as Month,
                            (month(CLC_DATE)) 
                            as Month_number
                        FROM CENTRALE_NALDEO.dbo.CLIENTS_CONSO
                        WHERE CLC_DATE BETWEEN :start AND :end
                              AND CL_ID = :id
                              AND FO_ID = :fournisseur";


                    $conn = $connection->prepare($sqlConso);
                    $conn->bindValue('id', $id);
                    $conn->bindValue('fournisseur', $fourn['FO_ID']);
                    $conn->bindValue('start', $start);
                    $conn->bindValue('end', $end);
                    $conn->execute();
                    $conso = $conn->fetchAll();


                    $cons_ca = [];
                    $cons_eco = [];

                    // on initialise a 0
                    for ($i = 0; $i < $month; $i++) {
                        array_push($cons_eco, 0);
                        array_push($cons_ca, 0);
                    }


                    //on remplace les 0 par les vraies valeur
                    for ($i = 0; $i < $month; $i++) {
                        foreach ($conso as $keyCons => $cons) {
                            if ($cons['Month'] == $months[$i]) {
                                $cons_eco[$i] = $cons['CLC_PRIX_PUBLIC'] - $cons["CLC_PRIX_CENTRALE"];
                                $cons_ca[$i] = $cons["CLC_PRIX_CENTRALE"];
                            }
                        }
                    }


                    $tpl = Array($helper->array_utf8_encode($fourn['FO_RAISONSOC']) => [
                        "id" => $fourn['FO_ID'],
                        "CA" => $cons_ca,
                        "ECO" => $cons_eco,
                        "total_ca" => array_sum($cons_ca),
                        "total_eco" => array_sum($cons_eco)
                    ]);

                    $ca_total += array_sum($cons_ca);
                    $eco_total += array_sum($cons_eco);
                    array_push($data["graph"], $tpl);


                }

                array_push($data["graph"]["Total"]["ca"], $ca_total);
                array_push($data["graph"]["Total"]["eco"], $eco_total);


                return new JsonResponse($data, 200);

                break;
            //funecap
            case 4:

                $sqlFourn = "SELECT DISTINCT FO_ID,
                      (SELECT FO_RAISONSOC FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS WHERE CENTRALE_PRODUITS.dbo.FOURNISSEURS.FO_ID = CENTRALE_FUNECAP.dbo.CLIENTS_CONSO.FO_ID GROUP BY FO_RAISONSOC) as FO_RAISONSOC
                    FROM CENTRALE_FUNECAP.dbo.CLIENTS_CONSO WHERE CL_ID = :id AND CLC_DATE BETWEEN :start AND :end";

                $conn = $connection->prepare($sqlFourn);
                $conn->bindValue(':id', $id);
                $conn->bindValue('start', $start);
                $conn->bindValue('end', $end);
                $conn->execute();
                $ListFourn = $conn->fetchAll();


                $data["graph"]["count"] = count($ListFourn);

                // chiffre d'affaires et eco total
                $ca_total = 0;
                $eco_total = 0;


                foreach ($ListFourn as $key => $fourn) {

                    $sqlConso = "SELECT
                          CLC_ID,
                          CL_ID,
                          CC_ID,
                          FO_ID,
                          CLC_DATE,
                          CLC_PRIX_PUBLIC,
                          CLC_PRIX_CENTRALE,
                          INS_DATE,
                          INS_USER ,
                          (case month(CLC_DATE)
                                WHEN 1 THEN 'Janvier'
                                WHEN 2 THEN 'Février'
                                WHEN 3 THEN 'Mars'
                                WHEN 4 THEN 'Avril'
                                WHEN 5 THEN 'Mai'
                                WHEN 6 THEN 'Juin'
                                WHEN 7 THEN 'Juillet'
                                WHEN 8 THEN 'Août'
                                WHEN 9 THEN 'Septembre'
                                WHEN 10 THEN 'Octobre'
                                WHEN 11 THEN 'Novembre'
                                ELSE 'Décembre'
                           end) 
                            as Month,
                            (month(CLC_DATE)) 
                            as Month_number
                        FROM CENTRALE_FUNECAP.dbo.CLIENTS_CONSO
                        WHERE CLC_DATE BETWEEN :start AND :end
                              AND CL_ID = :id
                              AND FO_ID = :fournisseur";
                    $conn = $connection->prepare($sqlConso);
                    $conn->bindValue('id', $id);
                    $conn->bindValue('fournisseur', $fourn['FO_ID']);
                    $conn->bindValue('start', $start);
                    $conn->bindValue('end', $end);
                    $conn->execute();
                    $conso = $conn->fetchAll();


                    $cons_ca = [];
                    $cons_eco = [];

                    // variable temporaire tpl pour le chiffre d'affaire
                    $tplTempCa = "";
                    // variable temporaire tpl pour les économies
                    $tplTempEco = "";

                    // variable contenant le total d'économies
                    $total_eco = 0;
                    // variable contenant le total chiffre d'affaire
                    $total_ca = 0;


                    // on initialise a 0
                    for ($i = 0; $i < $month; $i++) {
                        array_push($cons_eco, 0);
                        array_push($cons_ca, 0);


                    }


                    //on remplace les 0 par les vraies valeur
                    for ($i = 0; $i < $month; $i++) {
                        foreach ($conso as $keyCons => $cons) {
                            if ($cons['Month'] == $months[$i]) {
                                $cons_eco[$i] = $cons['CLC_PRIX_PUBLIC'] - $cons["CLC_PRIX_CENTRALE"];
                                $cons_ca[$i] = $cons["CLC_PRIX_CENTRALE"];
                            }
                        }
                    }


                    $tpl = Array($helper->array_utf8_encode($fourn['FO_RAISONSOC']) => [
                        "id" => $fourn['FO_ID'],
                        "CA" => $cons_ca,
                        "ECO" => $cons_eco,
                        "total_ca" => array_sum($cons_ca),
                        "total_eco" => array_sum($cons_eco)
                    ]);

                    $ca_total += array_sum($cons_ca);
                    $eco_total += array_sum($cons_eco);
                    array_push($data["graph"], $tpl);

                    foreach ($conso as $keyCons => $cons) {

                        // on ajoute a la variable le contenu du tableau presentant le chiffre d'affaire
                        $tplTempCa .= "<td>" . $cons["CLC_PRIX_CENTRALE"] . " €</td>";

                        // On obtient le total d"économies
                        $eco = $cons["CLC_PRIX_PUBLIC"] - $cons["CLC_PRIX_CENTRALE"];

                        //on obtient pour un fournisseur la rangée du tableau correspondant a l'économies
                        $tplTempEco .= "<td>" . $eco . " € (<b>" . $helper->Pourcentage($eco, $cons["CLC_PRIX_PUBLIC"]) . "%</b>)</td>";

                        // on obtient le total de chiffre d'afffaire
                        $total_ca = $total_ca + intval($cons["CLC_PRIX_CENTRALE"]);

                        // on obtient le total d'économies
                        $total_eco = $total_eco + $eco;


                    }

                    // on ajoute a la derniere colonne le total CA
                    $tplTempCa .= "<td>" . $total_ca . " €</td>";
                    // on ajoute a la derniere colonne le total ECO
                    $tplTempEco .= "<td>" . $total_eco . " € (<b>" . $helper->Pourcentage($total_eco, $total_ca + $total_eco) . "%</b>)</td>";


                    // on génère le tableau

                    $tplMois = "<tr style='font-size: 13pt'>
            <th></th>
            <th></th>
            " . $tplMoisTemp . "
            <th style=\"background-color: #a8a8a8;\" >Total</th>
            </tr>";


                    $tplData = "<tr style='font-size: 9pt'>
            <td rowspan=\"2\">" . $helper->array_utf8_encode($fourn['FO_RAISONSOC']) . "</td>
            <td>Mes achats</td>" .
                        $tplTempCa
                        . "</tr>
        <tr style='font-size: 9pt'>
            <td>Mes gains</td>" .
                        $tplTempEco
                        . "
        </tr>";

                    // on ajoute au tpl final les rangées pour pour chaque fournisseurs
                    $tplDataFinal .= $tplData;


                }

                $tplMois = "<tr style='font-size: 13pt'><th></th><th></th>" . $tplMoisTemp . "<th style='background-color: #a8a8a8;' >Total</th></tr>";

                $tplFinal = " <table id=\"table_conso\" class=\"table compact table-striped table-bordered\" style=\"width: 95%;margin: 0 auto;\">
        <thead>
        " . $tplMois . "
        </thead>
        <tbody>
        " . $tplDataFinal . "
        </tbody>
    </table>";


                array_push($data["graph"]["Total"]["ca"], $ca_total);
                array_push($data["graph"]["Total"]["eco"], $eco_total);
                array_push($data["table"], trim($tplFinal));




                return new JsonResponse($data, 200);

                break;
            //PFPL
            case 5:

                $sqlFourn = "SELECT DISTINCT FO_ID,
                      (SELECT FO_RAISONSOC FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS WHERE CENTRALE_PRODUITS.dbo.FOURNISSEURS.FO_ID = CENTRALE_PFPL.dbo.CLIENTS_CONSO.FO_ID GROUP BY FO_RAISONSOC) as FO_RAISONSOC
                    FROM CENTRALE_PFPL.dbo.CLIENTS_CONSO WHERE CL_ID = :id AND CLC_DATE BETWEEN :start AND :end";

                $conn = $connection->prepare($sqlFourn);
                $conn->bindValue(':id', $id);
                $conn->bindValue('start', $start);
                $conn->bindValue('end', $end);
                $conn->execute();
                $ListFourn = $conn->fetchAll();


                $data["graph"]["count"] = count($ListFourn);

                // chiffre d'affaires et eco total
                $ca_total = 0;
                $eco_total = 0;


                foreach ($ListFourn as $key => $fourn) {
                    $sqlConso = "SELECT
                          CLC_ID,
                          CL_ID,
                          CC_ID,
                          FO_ID,
                          CLC_DATE,
                          CLC_PRIX_PUBLIC,
                          CLC_PRIX_CENTRALE,
                          INS_DATE,
                          INS_USER ,
                          (case month(CLC_DATE)
                                WHEN 1 THEN 'Janvier'
                                WHEN 2 THEN 'Février'
                                WHEN 3 THEN 'Mars'
                                WHEN 4 THEN 'Avril'
                                WHEN 5 THEN 'Mai'
                                WHEN 6 THEN 'Juin'
                                WHEN 7 THEN 'Juillet'
                                WHEN 8 THEN 'Août'
                                WHEN 9 THEN 'Septembre'
                                WHEN 10 THEN 'Octobre'
                                WHEN 11 THEN 'Novembre'
                                ELSE 'Décembre'
                           end) 
                            as Month,
                            (month(CLC_DATE)) 
                            as Month_number
                        FROM CENTRALE_PFPL.dbo.CLIENTS_CONSO
                        WHERE CLC_DATE BETWEEN :start AND :end
                              AND CL_ID = :id
                              AND FO_ID = :fournisseur";


                    $conn = $connection->prepare($sqlConso);
                    $conn->bindValue('id', $id);
                    $conn->bindValue('fournisseur', $fourn['FO_ID']);
                    $conn->bindValue('start', $start);
                    $conn->bindValue('end', $end);
                    $conn->execute();
                    $conso = $conn->fetchAll();


                    $cons_ca = [];
                    $cons_eco = [];

                    // on initialise a 0
                    for ($i = 0; $i < $month; $i++) {
                        array_push($cons_eco, 0);
                        array_push($cons_ca, 0);
                    }


                    //on remplace les 0 par les vraies valeur
                    for ($i = 0; $i < $month; $i++) {
                        foreach ($conso as $keyCons => $cons) {
                            if ($cons['Month'] == $months[$i]) {
                                $cons_eco[$i] = $cons['CLC_PRIX_PUBLIC'] - $cons["CLC_PRIX_CENTRALE"];
                                $cons_ca[$i] = $cons["CLC_PRIX_CENTRALE"];
                            }
                        }
                    }


                    $tpl = Array($helper->array_utf8_encode($fourn['FO_RAISONSOC']) => [
                        "id" => $fourn['FO_ID'],
                        "CA" => $cons_ca,
                        "ECO" => $cons_eco,
                        "total_ca" => array_sum($cons_ca),
                        "total_eco" => array_sum($cons_eco)
                    ]);

                    $ca_total += array_sum($cons_ca);
                    $eco_total += array_sum($cons_eco);
                    array_push($data["graph"], $tpl);


                }

                array_push($data["graph"]["Total"]["ca"], $ca_total);
                array_push($data["graph"]["Total"]["eco"], $eco_total);


                return new JsonResponse($data, 200);

                break;
            //ROC
            case 6:

                $sqlFourn = "SELECT DISTINCT FO_ID,
                      (SELECT FO_RAISONSOC FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS WHERE CENTRALE_PRODUITS.dbo.FOURNISSEURS.FO_ID = CENTRALE_ROC_ECLERC.dbo.CLIENTS_CONSO.FO_ID GROUP BY FO_RAISONSOC) as FO_RAISONSOC
                    FROM CENTRALE_ROC_ECLERC.dbo.CLIENTS_CONSO WHERE CL_ID = :id AND CLC_DATE BETWEEN :start AND :end";

                $conn = $connection->prepare($sqlFourn);
                $conn->bindValue(':id', $id);
                $conn->bindValue('start', $start);
                $conn->bindValue('end', $end);
                $conn->execute();
                $ListFourn = $conn->fetchAll();


                $data["graph"]["count"] = count($ListFourn);

                // chiffre d'affaires et eco total
                $ca_total = 0;
                $eco_total = 0;


                foreach ($ListFourn as $key => $fourn) {
                    $sqlConso = "SELECT
                          CLC_ID,
                          CL_ID,
                          CC_ID,
                          FO_ID,
                          CLC_DATE,
                          CLC_PRIX_PUBLIC,
                          CLC_PRIX_CENTRALE,
                          INS_DATE,
                          INS_USER ,
                          (case month(CLC_DATE)
                                WHEN 1 THEN 'Janvier'
                                WHEN 2 THEN 'Février'
                                WHEN 3 THEN 'Mars'
                                WHEN 4 THEN 'Avril'
                                WHEN 5 THEN 'Mai'
                                WHEN 6 THEN 'Juin'
                                WHEN 7 THEN 'Juillet'
                                WHEN 8 THEN 'Août'
                                WHEN 9 THEN 'Septembre'
                                WHEN 10 THEN 'Octobre'
                                WHEN 11 THEN 'Novembre'
                                ELSE 'Décembre'
                           end) 
                            as Month,
                            (month(CLC_DATE)) 
                            as Month_number
                        FROM CENTRALE_ROC_ECLERC.dbo.CLIENTS_CONSO
                        WHERE CLC_DATE BETWEEN :start AND :end
                              AND CL_ID = :id
                              AND FO_ID = :fournisseur";


                    $conn = $connection->prepare($sqlConso);
                    $conn->bindValue('id', $id);
                    $conn->bindValue('fournisseur', $fourn['FO_ID']);
                    $conn->bindValue('start', $start);
                    $conn->bindValue('end', $end);
                    $conn->execute();
                    $conso = $conn->fetchAll();


                    $cons_ca = [];
                    $cons_eco = [];

                    // on initialise a 0
                    for ($i = 0; $i < $month; $i++) {
                        array_push($cons_eco, 0);
                        array_push($cons_ca, 0);
                    }


                    //on remplace les 0 par les vraies valeur
                    for ($i = 0; $i < $month; $i++) {
                        foreach ($conso as $keyCons => $cons) {
                            if ($cons['Month'] == $months[$i]) {
                                $cons_eco[$i] = $cons['CLC_PRIX_PUBLIC'] - $cons["CLC_PRIX_CENTRALE"];
                                $cons_ca[$i] = $cons["CLC_PRIX_CENTRALE"];
                            }
                        }
                    }


                    $tpl = Array($helper->array_utf8_encode($fourn['FO_RAISONSOC']) => [
                        "id" => $fourn['FO_ID'],
                        "CA" => $cons_ca,
                        "ECO" => $cons_eco,
                        "total_ca" => array_sum($cons_ca),
                        "total_eco" => array_sum($cons_eco)
                    ]);

                    $ca_total += array_sum($cons_ca);
                    $eco_total += array_sum($cons_eco);
                    array_push($data["graph"], $tpl);


                }

                array_push($data["graph"]["Total"]["ca"], $ca_total);
                array_push($data["graph"]["Total"]["eco"], $eco_total);


                return new JsonResponse($data, 200);

                break;


        }


    }

    /**
     * @Route("/consommation/years", name="conso_years")
     */
    public function consoYears(Connection $connection, HelperService $helper)
    {


        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: true ");
        header("Access-Control-Allow-Methods: OPTIONS, GET, POST");
        header("Access-Control-Allow-Headers: Content-Type, Depth, User-Agent, Cache-Control");

        $sql = "SELECT DISTINCT year(CLC_DATE) as date
                FROM CENTRALE_ACHAT.dbo.CLIENTS_CONSO
                ORDER BY date desc";


        $conn = $connection->prepare($sql);
        $conn->execute();
        $result = $conn->fetchAll();

        $data = $result;


        return new JsonResponse($data, 200);

    }


    /**
     * @Route("/consommation/years/{id}/{year}", name="consommation_client_year")
     */
    public function consoForTheYear(Connection $connection, HelperService $helper, $id, $year)
    {

        //header pour résoudre problème CORS ?
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: true ");
        header("Access-Control-Allow-Methods: OPTIONS, GET, POST");
        header("Access-Control-Allow-Headers: Content-Type, Depth, User-Agent, Cache-Control");


        //requete sql pour obtenir tout les fournisseurs contenu dans la table conso
        $sqlFourn = "SELECT DISTINCT
                      FO_ID,
                      (SELECT FO_RAISONSOC FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS WHERE CENTRALE_PRODUITS.dbo.FOURNISSEURS.FO_ID = CENTRALE_ACHAT.dbo.CLIENTS_CONSO.FO_ID GROUP BY FO_RAISONSOC) as FO_RAISONSOC
                    FROM CENTRALE_ACHAT.dbo.CLIENTS_CONSO";

        $conn = $connection->prepare($sqlFourn);
        $conn->execute();
        $fournisseur = $conn->fetchAll();


        //requete sql pour obtenir tout les mois stocké pour l'année :date
        $sqlMonth = "SELECT
                      (case month(CLC_DATE)
                       WHEN 1 THEN 'Jan'
                       WHEN 2 THEN 'Fév'
                       WHEN 3 THEN 'Mar'
                       WHEN 4 THEN 'Avr'
                       WHEN 5 THEN 'Mai'
                       WHEN 6 THEN 'Jun'
                       WHEN 7 THEN 'Jul'
                       WHEN 8 THEN 'Aoû'
                       WHEN 9 THEN 'Sep'
                       WHEN 10 THEN 'Oct'
                       WHEN 11 THEN 'Nov'
                       ELSE 'Déc'
                       end
                      ) as Month FROM CENTRALE_ACHAT.dbo.CLIENTS_CONSO
                    WHERE CL_ID = :id
                          AND year(CLC_DATE) = :date
                    group by MONTH(CLC_DATE)";

        $conn = $connection->prepare($sqlMonth);
        $conn->bindValue('id', $id);
        $conn->bindValue('date', $year);
        $conn->execute();
        $month = $conn->fetchAll();


        //Si on a aucun mois de disponible on retourne "none"
        if (count($month) == 0) {
            return new JsonResponse("none", 200);
        }


        //On cherche a obtenir les mois pour avoir les noms de colones

        // variable pour contenir les mois
        $list_month = [];

        // tpl des colonnes
        $tplMoisTemp = "";

        foreach ($month as $mois) {
            //on ajoute chaque mois dans le tableau
            array_push($list_month, $mois["Month"]);
            // on ajoute au tpl les th avec mois
            $tplMoisTemp .= "<th>" . $mois["Month"] . "</th>";
        }


        //tpl final du tableau
        $tplDataFinal = "";

        // pour chaque fournisseur on va chercher les données
        foreach ($fournisseur as $fourn) {

            //requete sql pour avoir les conso pour chaque mois pour chaque fournisseur
            $sqlConso = "SELECT CLC_PRIX_CENTRALE, CLC_PRIX_PUBLIC FROM CENTRALE_ACHAT.dbo.CLIENTS_CONSO WHERE CL_ID = :id AND FO_ID = :fourn AND year(CLC_DATE) = :date";
            $conn = $connection->prepare($sqlConso);
            $conn->bindValue('id', $id);
            $conn->bindValue('fourn', $fourn["FO_ID"]);
            $conn->bindValue('date', $year);
            $conn->execute();
            $conso = $conn->fetchAll();


            // variable temporaire tpl pour le chiffre d'affaire
            $tplTempCa = "";
            // variable temporaire tpl pour les économies
            $tplTempEco = "";

            // variable contenant le total d'économies
            $total_eco = 0;
            // variable contenant le total chiffre d'affaire
            $total_ca = 0;

            foreach ($conso as $key => $cons) {

                // on ajoute a la variable le contenu du tableau presentant le chiffre d'affaire
                $tplTempCa .= "<td>" . $cons["CLC_PRIX_CENTRALE"] . " €</td>";

                // On obtient le total d"économies
                $eco = $cons["CLC_PRIX_PUBLIC"] - $cons["CLC_PRIX_CENTRALE"];

                //on obtient pour un fournisseur la rangée du tableau correspondant a l'économies
                $tplTempEco .= "<td>" . $eco . " € (<b>" . $helper->Pourcentage($eco, $cons["CLC_PRIX_PUBLIC"]) . "%</b>)</td>";

                // on obtient le total de chiffre d'afffaire
                $total_ca = $total_ca + intval($cons["CLC_PRIX_CENTRALE"]);

                // on obtient le total d'économies
                $total_eco = $total_eco + $eco;


            }


            // on ajoute a la derniere colonne le total CA
            $tplTempCa .= "<td>" . $total_ca . " €</td>";
            // on ajoute a la derniere colonne le total ECO
            $tplTempEco .= "<td>" . $total_eco . " € (<b>" . $helper->Pourcentage($total_eco, $total_ca + $total_eco) . "%</b>)</td>";


            // on génère le tableau

            $tplMois = "<tr style='font-size: 13pt'>
            <th></th>
            <th></th>
            " . $tplMoisTemp . "
            <th style=\"background-color: #a8a8a8;\" >Total</th>
            </tr>";


            $tplData = "<tr style='font-size: 9pt'>
            <td rowspan=\"2\">" . $fourn["FO_RAISONSOC"] . "</td>
            <td>Mes achats</td>" .
                $tplTempCa
                . "</tr>
        <tr style='font-size: 9pt'>
            <td>Mes gains</td>" .
                $tplTempEco
                . "
        </tr>";

            // on ajoute au tpl final les rangées pour pour chaque fournisseurs
            $tplDataFinal .= $tplData;
        }


        $tplFinal = " <table id=\"table_conso\" class=\"table compact table-striped table-bordered\" style=\"width: 95%;margin: 0 auto;\">
        <thead>
        " . $tplMois . "
        </thead>
        <tbody>
        " . $tplDataFinal . "
        </tbody>
    </table>";

        return new JsonResponse($tplFinal, 200);

    }


}
