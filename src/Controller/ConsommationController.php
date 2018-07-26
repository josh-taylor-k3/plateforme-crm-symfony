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


                if (empty($ListFourn)) {
                    return new JsonResponse("none", 200);
                }


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
                                WHEN 1 THEN 'Janv'
                                WHEN 2 THEN 'Févr'
                                WHEN 3 THEN 'Mars'
                                WHEN 4 THEN 'Avr'
                                WHEN 5 THEN 'Mai'
                                WHEN 6 THEN 'Juin'
                                WHEN 7 THEN 'Juill'
                                WHEN 8 THEN 'Août'
                                WHEN 9 THEN 'Sept'
                                WHEN 10 THEN 'Oct'
                                WHEN 11 THEN 'Nov'
                                ELSE 'Déc'
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


                    foreach ($cons_ca as $conso_ca) {
                        // on ajoute a la variable le contenu du tableau presentant le chiffre d'affaire

                        if ($conso_ca === 0) {
                            $tplTempCa .= "<td> _ </td>";

                        } else {
                            $tplTempCa .= "<td>" . $conso_ca . " €</td>";

                        }


                    }

                    foreach ($cons_eco as $conso_eco) {
                        //on obtient pour un fournisseur la rangée du tableau correspondant a l'économies
                        if ($conso_eco === 0) {
                            $tplTempEco .= "<td> _ </td>";

                        } else {
                            $tplTempEco .= "<td>" . $conso_eco . " € (<b>" . $helper->Pourcentage($conso_eco, $cons["CLC_PRIX_PUBLIC"]) . "%</b>)</td>";

                        }

                    }


                    // on obtient le total de chiffre d'afffaire
                    $total_ca = array_sum($cons_ca);

                    // on obtient le total d'économies
                    $total_eco = array_sum($cons_eco);
                    // on ajoute a la derniere colonne le total CA
                    $tplTempCa .= "<td style='background-color: #d4d4d5'><b>" . $total_ca . " €</b></td>";
                    // on ajoute a la derniere colonne le total ECO
                    $tplTempEco .= "<td style='background-color: #d4d4d5'><b>" . $total_eco . " € (" . $helper->Pourcentage($total_eco, $total_ca + $total_eco) . "%)</b></td>";

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

                if (empty($ListFourn)) {
                    return new JsonResponse("none", 200);
                }

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
                                WHEN 1 THEN 'Janv'
                                WHEN 2 THEN 'Févr'
                                WHEN 3 THEN 'Mars'
                                WHEN 4 THEN 'Avr'
                                WHEN 5 THEN 'Mai'
                                WHEN 6 THEN 'Juin'
                                WHEN 7 THEN 'Juill'
                                WHEN 8 THEN 'Août'
                                WHEN 9 THEN 'Sept'
                                WHEN 10 THEN 'Oct'
                                WHEN 11 THEN 'Nov'
                                ELSE 'Déc'
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


                    foreach ($cons_ca as $conso_ca) {
                        // on ajoute a la variable le contenu du tableau presentant le chiffre d'affaire

                        if ($conso_ca === 0) {
                            $tplTempCa .= "<td> _ </td>";

                        } else {
                            $tplTempCa .= "<td>" . $conso_ca . " €</td>";

                        }


                    }

                    foreach ($cons_eco as $conso_eco) {
                        //on obtient pour un fournisseur la rangée du tableau correspondant a l'économies
                        if ($conso_eco === 0) {
                            $tplTempEco .= "<td> _ </td>";

                        } else {
                            $tplTempEco .= "<td>" . $conso_eco . " € (<b>" . $helper->Pourcentage($conso_eco, $cons["CLC_PRIX_PUBLIC"]) . "%</b>)</td>";

                        }

                    }


                    // on obtient le total de chiffre d'afffaire
                    $total_ca = array_sum($cons_ca);

                    // on obtient le total d'économies
                    $total_eco = array_sum($cons_eco);
                    // on ajoute a la derniere colonne le total CA
                    $tplTempCa .= "<td style='background-color: #d4d4d5'><b>" . $total_ca . " €</b></td>";
                    // on ajoute a la derniere colonne le total ECO
                    $tplTempEco .= "<td style='background-color: #d4d4d5'><b>" . $total_eco . " € (" . $helper->Pourcentage($total_eco, $total_ca + $total_eco) . "%)</b></td>";

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
                    //dump($tplData);

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

                if (empty($ListFourn)) {
                    return new JsonResponse("none", 200);
                }


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
                                WHEN 1 THEN 'Janv'
                                WHEN 2 THEN 'Févr'
                                WHEN 3 THEN 'Mars'
                                WHEN 4 THEN 'Avr'
                                WHEN 5 THEN 'Mai'
                                WHEN 6 THEN 'Juin'
                                WHEN 7 THEN 'Juill'
                                WHEN 8 THEN 'Août'
                                WHEN 9 THEN 'Sept'
                                WHEN 10 THEN 'Oct'
                                WHEN 11 THEN 'Nov'
                                ELSE 'Déc'
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


                    foreach ($cons_ca as $conso_ca) {
                        // on ajoute a la variable le contenu du tableau presentant le chiffre d'affaire

                        if ($conso_ca === 0) {
                            $tplTempCa .= "<td> _ </td>";

                        } else {
                            $tplTempCa .= "<td>" . $conso_ca . " €</td>";

                        }


                    }

                    foreach ($cons_eco as $conso_eco) {
                        //on obtient pour un fournisseur la rangée du tableau correspondant a l'économies
                        if ($conso_eco === 0) {
                            $tplTempEco .= "<td> _ </td>";

                        } else {
                            $tplTempEco .= "<td>" . $conso_eco . " € (<b>" . $helper->Pourcentage($conso_eco, $cons["CLC_PRIX_PUBLIC"]) . "%</b>)</td>";

                        }

                    }


                    // on obtient le total de chiffre d'afffaire
                    $total_ca = array_sum($cons_ca);

                    // on obtient le total d'économies
                    $total_eco = array_sum($cons_eco);
                    // on ajoute a la derniere colonne le total CA
                    $tplTempCa .= "<td style='background-color: #d4d4d5'><b>" . $total_ca . " €</b></td>";
                    // on ajoute a la derniere colonne le total ECO
                    $tplTempEco .= "<td style='background-color: #d4d4d5'><b>" . $total_eco . " € (" . $helper->Pourcentage($total_eco, $total_ca + $total_eco) . "%)</b></td>";

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

                if (empty($ListFourn)) {
                    return new JsonResponse("none", 200);
                }

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
                                WHEN 1 THEN 'Janv'
                                WHEN 2 THEN 'Févr'
                                WHEN 3 THEN 'Mars'
                                WHEN 4 THEN 'Avri'
                                WHEN 5 THEN 'Mai'
                                WHEN 6 THEN 'Juin'
                                WHEN 7 THEN 'Juil'
                                WHEN 8 THEN 'Août'
                                WHEN 9 THEN 'Sept'
                                WHEN 10 THEN 'Octo'
                                WHEN 11 THEN 'Nove'
                                ELSE 'Déce'
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


                    foreach ($cons_ca as $conso_ca) {
                        // on ajoute a la variable le contenu du tableau presentant le chiffre d'affaire

                        if ($conso_ca === 0) {
                            $tplTempCa .= "<td style='background-color: #ececec' > _ </td>";

                        } else {
                            $tplTempCa .= "<td style='background-color: #ececec' >" . $conso_ca . " €</td>";

                        }


                    }

                    foreach ($cons_eco as $conso_eco) {
                        //on obtient pour un fournisseur la rangée du tableau correspondant a l'économies
                        if ($conso_eco === 0) {
                            $tplTempEco .= "<td style='background-color: #d4d4d5' > _ </td>";

                        } else {
                            $tplTempEco .= "<td style='background-color: #d4d4d5' >" . $conso_eco . " € (<b>" . $helper->Pourcentage($conso_eco, $cons["CLC_PRIX_PUBLIC"]) . "%</b>)</td>";

                        }

                    }


                    // on obtient le total de chiffre d'afffaire
                    $total_ca = array_sum($cons_ca);

                    // on obtient le total d'économies
                    $total_eco = array_sum($cons_eco);
                    // on ajoute a la derniere colonne le total CA
                    $tplTempCa .= "<td style='background-color: #ececec'><b>" . $total_ca . " €</b></td>";
                    // on ajoute a la derniere colonne le total ECO
                    $tplTempEco .= "<td style='background-color: #d4d4d5'><b>" . $total_eco . " € (" . $helper->Pourcentage($total_eco, $total_ca + $total_eco) . "%)</b></td>";


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
                                <td>Mes gains</td>" . $tplTempEco . "</tr>";

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

                if (empty($ListFourn)) {
                    return new JsonResponse("none", 200);
                }


                $data["graph"]["count"] = count($ListFourn);

                $cons_ca = [];
                $cons_eco = [];


                // chiffre d'affaires et eco total
                $ca_total = 0;
                $eco_total = 0;

                // variable temporaire tpl pour le chiffre d'affaire
                $tplTempCa = "";
                // variable temporaire tpl pour les économies
                $tplTempEco = "";

                // variable contenant le total d'économies
                $total_eco = 0;
                // variable contenant le total chiffre d'affaire
                $total_ca = 0;

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

                    foreach ($cons_ca as $conso_ca) {
                        // on ajoute a la variable le contenu du tableau presentant le chiffre d'affaire

                        if ($conso_ca === 0) {
                            $tplTempCa .= "<td> _ </td>";

                        } else {
                            $tplTempCa .= "<td>" . $conso_ca . " €</td>";

                        }


                    }

                    foreach ($cons_eco as $conso_eco) {
                        //on obtient pour un fournisseur la rangée du tableau correspondant a l'économies
                        if ($conso_eco === 0) {
                            $tplTempEco .= "<td> _ </td>";

                        } else {
                            $tplTempEco .= "<td>" . $conso_eco . " € (<b>" . $helper->Pourcentage($conso_eco, $cons["CLC_PRIX_PUBLIC"]) . "%</b>)</td>";

                        }

                    }

                    // on obtient le total de chiffre d'afffaire
                    $total_ca = array_sum($cons_ca);

                    // on obtient le total d'économies
                    $total_eco = array_sum($cons_eco);
                    // on ajoute a la derniere colonne le total CA
                    $tplTempCa .= "<td style='background-color: #d4d4d5'><b>" . $total_ca . " €</b></td>";
                    // on ajoute a la derniere colonne le total ECO
                    $tplTempEco .= "<td style='background-color: #d4d4d5'><b>" . $total_eco . " € (" . $helper->Pourcentage($total_eco, $total_ca + $total_eco) . "%)</b></td>";

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
                    //dump($tplData);

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

                if (empty($ListFourn)) {
                    return new JsonResponse("none", 200);
                }


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


                    foreach ($cons_ca as $conso_ca) {
                        // on ajoute a la variable le contenu du tableau presentant le chiffre d'affaire

                        if ($conso_ca === 0) {
                            $tplTempCa .= "<td> _ </td>";

                        } else {
                            $tplTempCa .= "<td>" . $conso_ca . " €</td>";

                        }


                    }

                    foreach ($cons_eco as $conso_eco) {
                        //on obtient pour un fournisseur la rangée du tableau correspondant a l'économies
                        if ($conso_eco === 0) {
                            $tplTempEco .= "<td> _ </td>";

                        } else {
                            $tplTempEco .= "<td>" . $conso_eco . " € (<b>" . $helper->Pourcentage($conso_eco, $cons["CLC_PRIX_PUBLIC"]) . "%</b>)</td>";

                        }

                    }


                    // on obtient le total de chiffre d'afffaire
                    $total_ca = array_sum($cons_ca);

                    // on obtient le total d'économies
                    $total_eco = array_sum($cons_eco);
                    // on ajoute a la derniere colonne le total CA
                    $tplTempCa .= "<td style='background-color: #d4d4d5'><b>" . $total_ca . " €</b></td>";
                    // on ajoute a la derniere colonne le total ECO
                    $tplTempEco .= "<td style='background-color: #d4d4d5'><b>" . $total_eco . " € (" . $helper->Pourcentage($total_eco, $total_ca + $total_eco) . "%)</b></td>";


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
                    //dump($tplData);

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


        }

        return new JsonResponse("none", 200);
    }


    /**
     * @Route("/consommation/{id}/{start}/{end}/{centrale}/fournisseur", name="fourn_byClient")
     */
    public function fournisseurByClient(Connection $connection, HelperService $helper, $id, $start, $end, $centrale)
    {

        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: true ");
        header("Access-Control-Allow-Headers: Content-Type, Depth, User-Agent, X-File-Size, X-Requested-With, If-Modified-Since, X-File-Name, Cache-Control, X-ac-key");


        switch ($centrale) {

            //ACHAT CENTRALE
            case 1:
                $sql = "SELECT DISTINCT
                          FO_ID,
                          (SELECT FO_RAISONSOC
                           FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS
                           WHERE CENTRALE_PRODUITS.dbo.FOURNISSEURS.FO_ID = CENTRALE_ACHAT.dbo.CLIENTS_CONSO.FO_ID) as fourn,
                          SUM(CLC_PRIX_PUBLIC)                                                                      as achat,
                          SUM(CLC_PRIX_PUBLIC) - SUM(CLC_PRIX_CENTRALE)                                             as eco,
                          (SELECT FO_LOGO FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS WHERE CENTRALE_PRODUITS.dbo.FOURNISSEURS.FO_ID = CENTRALE_ACHAT.dbo.CLIENTS_CONSO.FO_ID) as logo
                        FROM CENTRALE_ACHAT.dbo.CLIENTS_CONSO
                        WHERE CL_ID = :id
                              AND CLC_DATE BETWEEN :start AND :end
                        GROUP BY FO_ID
                        ORDER BY eco DESC";

                $conn = $connection->prepare($sql);
                $conn->bindValue(':id', $id);
                $conn->bindValue('start', $start);
                $conn->bindValue('end', $end);
                $conn->execute();
                $ListFourn = $conn->fetchAll();

                $final_tpl = " <thead><tr><th>Position</th><th>Logo fournisseur</th><th>Raison sociale</th><th style=' white-space: nowrap;overflow: hidden;text-overflow: ellipsis;'>Montant économie</th></tr></thead><tbody>";

                foreach ($ListFourn as $key => $fourn){
                    switch ($key) {
                        case 0:
                            $tpl_temp = "<tr><th><img src='number_one.png' class='logo_position_img'/></th><th class='container_img_top_fourn' ><img class='img_logo_fourn' src='http://secure.achatcentrale.fr/UploadFichiers/Uploads/FOURN_" . $fourn["FO_ID"] . "/" . $fourn["logo"] . "' alt=''></th><th class='raison_soc_top_fourn' >" . $helper->array_utf8_encode($fourn["fourn"]) . "</th><th class='qty_eco_top' >" . $fourn["eco"] . " €</th></tr>";
                            $final_tpl .= $tpl_temp;

                            break;
                        case 1:
                            $tpl_temp = "<tr><th><img src='number_two.png' class='logo_position_img'/></th><th class='container_img_top_fourn' ><img class='img_logo_fourn' src='http://secure.achatcentrale.fr/UploadFichiers/Uploads/FOURN_" . $fourn["FO_ID"] . "/" . $fourn["logo"] . "' alt=''></th><th class='raison_soc_top_fourn' >" . $helper->array_utf8_encode($fourn["fourn"]) . "</th><th class='qty_eco_top' >" . $fourn["eco"] . " €</th></tr>";
                            $final_tpl .= $tpl_temp;

                            break;
                        case 2:
                            $tpl_temp = "<tr><th><img src='number_three.png' class='logo_position_img'/></th><th class='container_img_top_fourn' ><img class='img_logo_fourn' src='http://secure.achatcentrale.fr/UploadFichiers/Uploads/FOURN_" . $fourn["FO_ID"] . "/" . $fourn["logo"] . "' alt=''></th><th class='raison_soc_top_fourn' >" . $helper->array_utf8_encode($fourn["fourn"]) . "</th><th class='qty_eco_top' >" . $fourn["eco"] . " €</th></tr>";
                            $final_tpl .= $tpl_temp;
                            break;
                    }
                }
                $final_tpl .= "</tbody></table>";

                return new JsonResponse($final_tpl, 200);
                break;
            //GCCP
            case 2:

                $sql = "SELECT DISTINCT
                          FO_ID,
                          (SELECT FO_RAISONSOC
                           FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS
                           WHERE CENTRALE_PRODUITS.dbo.FOURNISSEURS.FO_ID = CENTRALE_GCCP.dbo.CLIENTS_CONSO.FO_ID) as fourn,
                          SUM(CLC_PRIX_PUBLIC)                                                                      as achat,
                          SUM(CLC_PRIX_PUBLIC) - SUM(CLC_PRIX_CENTRALE)                                             as eco,
                          (SELECT FO_LOGO FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS WHERE CENTRALE_PRODUITS.dbo.FOURNISSEURS.FO_ID = CENTRALE_GCCP.dbo.CLIENTS_CONSO.FO_ID) as logo
                        FROM CENTRALE_GCCP.dbo.CLIENTS_CONSO
                        WHERE CL_ID = :id
                              AND CLC_DATE BETWEEN :start AND :end
                        GROUP BY FO_ID
                        ORDER BY eco DESC";

                $conn = $connection->prepare($sql);
                $conn->bindValue(':id', $id);
                $conn->bindValue('start', $start);
                $conn->bindValue('end', $end);
                $conn->execute();
                $ListFourn = $conn->fetchAll();

                $final_tpl = "";
                foreach ($ListFourn as $key => $fourn){
                    switch ($key) {
                        case 0:
                            $tpl_temp = "<tr><th><img src='number_one.png' class='logo_position_img'/></th><th><img class='img_logo_fourn' src='http://secure.achatcentrale.fr/UploadFichiers/Uploads/FOURN_" . $fourn["FO_ID"] . "/" . $fourn["logo"] . "' alt=''></th><th class='raison_soc_top_fourn' >" . $helper->array_utf8_encode($fourn["fourn"]) . "</th><th class='qty_eco_top' >" . $fourn["eco"] . " €</th></tr>";
                            $final_tpl .= $tpl_temp;

                            break;
                        case 1:
                            $tpl_temp = "<tr><th><img src='number_two.png' class='logo_position_img'/></th><th><img class='img_logo_fourn' src='http://secure.achatcentrale.fr/UploadFichiers/Uploads/FOURN_" . $fourn["FO_ID"] . "/" . $fourn["logo"] . "' alt=''></th><th class='raison_soc_top_fourn' >" . $helper->array_utf8_encode($fourn["fourn"]) . "</th><th class='qty_eco_top' >" . $fourn["eco"] . " €</th></tr>";
                            $final_tpl .= $tpl_temp;

                            break;
                        case 2:
                            $tpl_temp = "<tr><th><img src='number_three.png' class='logo_position_img'/></th><th><img class='img_logo_fourn' src='http://secure.achatcentrale.fr/UploadFichiers/Uploads/FOURN_" . $fourn["FO_ID"] . "/" . $fourn["logo"] . "' alt=''></th><th class='raison_soc_top_fourn' >" . $helper->array_utf8_encode($fourn["fourn"]) . "</th><th class='qty_eco_top' >" . $fourn["eco"] . " €</th></tr>";
                            $final_tpl .= $tpl_temp;
                            break;
                    }
                }
                $final_tpl .= "</table>";

                return new JsonResponse($final_tpl, 200);
                break;
            //NALDEO
            case 3:
                $sql = "SELECT DISTINCT
                          FO_ID,
                          (SELECT FO_RAISONSOC
                           FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS
                           WHERE CENTRALE_PRODUITS.dbo.FOURNISSEURS.FO_ID = CENTRALE_NALDEO.dbo.CLIENTS_CONSO.FO_ID) as fourn,
                          SUM(CLC_PRIX_PUBLIC)                                                                      as achat,
                          SUM(CLC_PRIX_PUBLIC) - SUM(CLC_PRIX_CENTRALE)                                             as eco,
                          (SELECT FO_LOGO FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS WHERE CENTRALE_PRODUITS.dbo.FOURNISSEURS.FO_ID = CENTRALE_NALDEO.dbo.CLIENTS_CONSO.FO_ID) as logo
                        FROM CENTRALE_NALDEO.dbo.CLIENTS_CONSO
                        WHERE CL_ID = :id
                              AND CLC_DATE BETWEEN :start AND :end
                        GROUP BY FO_ID
                        ORDER BY eco DESC";

                $conn = $connection->prepare($sql);
                $conn->bindValue(':id', $id);
                $conn->bindValue('start', $start);
                $conn->bindValue('end', $end);
                $conn->execute();
                $ListFourn = $conn->fetchAll();

                $final_tpl = "";

                foreach ($ListFourn as $key => $fourn){
                    switch ($key) {
                        case 0:
                            $tpl_temp = "<tr><th><img src='number_one.png' class='logo_position_img'/></th><th><img src='http://secure.achatcentrale.fr/UploadFichiers/Uploads/FOURN_" . $fourn["FO_ID"] . "/" . $fourn["logo"] . "' alt=''></th><th>" . $helper->array_utf8_encode($fourn["fourn"]) . "</th><th>" . $fourn["eco"] . " €</th></tr>";
                            $final_tpl .= $tpl_temp;

                            break;
                        case 1:
                            $tpl_temp = "<tr><th><img src='number_two.png' class='logo_position_img'/></th><th><img src='http://secure.achatcentrale.fr/UploadFichiers/Uploads/FOURN_" . $fourn["FO_ID"] . "/" . $fourn["logo"] . "' alt=''></th><th>" . $helper->array_utf8_encode($fourn["fourn"]) . "</th><th>" . $fourn["eco"] . " €</th></tr>";
                            $final_tpl .= $tpl_temp;

                            break;
                        case 2:
                            $tpl_temp = "<tr><th><img src='number_three.png' class='logo_position_img'/></th><th><img src='http://secure.achatcentrale.fr/UploadFichiers/Uploads/FOURN_" . $fourn["FO_ID"] . "/" . $fourn["logo"] . "' alt=''></th><th>" . $helper->array_utf8_encode($fourn["fourn"]) . "</th><th>" . $fourn["eco"] . " €</th></tr>";
                            $final_tpl .= $tpl_temp;
                            break;
                    }
                }
                $final_tpl .= "</table>";

                return new JsonResponse($final_tpl, 200);
                break;
            //Funecap
            case 4:
                $sql = "SELECT DISTINCT
                          FO_ID,
                          (SELECT FO_RAISONSOC
                           FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS
                           WHERE CENTRALE_PRODUITS.dbo.FOURNISSEURS.FO_ID = CENTRALE_FUNECAP.dbo.CLIENTS_CONSO.FO_ID) as fourn,
                          SUM(CLC_PRIX_PUBLIC)                                                                      as achat,
                          SUM(CLC_PRIX_PUBLIC) - SUM(CLC_PRIX_CENTRALE)                                             as eco,
                          (SELECT FO_LOGO FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS WHERE CENTRALE_PRODUITS.dbo.FOURNISSEURS.FO_ID = CENTRALE_FUNECAP.dbo.CLIENTS_CONSO.FO_ID) as logo
                        FROM CENTRALE_FUNECAP.dbo.CLIENTS_CONSO
                        WHERE CL_ID = :id
                              AND CLC_DATE BETWEEN :start AND :end
                        GROUP BY FO_ID
                        ORDER BY eco DESC";

                $conn = $connection->prepare($sql);
                $conn->bindValue(':id', $id);
                $conn->bindValue('start', $start);
                $conn->bindValue('end', $end);
                $conn->execute();
                $ListFourn = $conn->fetchAll();

                $final_tpl = "";
                foreach ($ListFourn as $key => $fourn){
                    switch ($key) {
                        case 0:
                            $tpl_temp = "<tr><th><img src='number_one.png' class='logo_position_img'/></th><th><img class='img_logo_fourn' src='http://secure.achatcentrale.fr/UploadFichiers/Uploads/FOURN_" . $fourn["FO_ID"] . "/" . $fourn["logo"] . "' alt=''></th><th class='raison_soc_top_fourn' >" . $helper->array_utf8_encode($fourn["fourn"]) . "</th><th class='qty_eco_top' >" . $fourn["eco"] . " €</th></tr>";
                            $final_tpl .= $tpl_temp;

                            break;
                        case 1:
                            $tpl_temp = "<tr><th><img src='number_two.png' class='logo_position_img'/></th><th><img class='img_logo_fourn' src='http://secure.achatcentrale.fr/UploadFichiers/Uploads/FOURN_" . $fourn["FO_ID"] . "/" . $fourn["logo"] . "' alt=''></th><th class='raison_soc_top_fourn' >" . $helper->array_utf8_encode($fourn["fourn"]) . "</th><th class='qty_eco_top' >" . $fourn["eco"] . " €</th></tr>";
                            $final_tpl .= $tpl_temp;

                            break;
                        case 2:
                            $tpl_temp = "<tr><th><img src='number_three.png' class='logo_position_img'/></th><th><img class='img_logo_fourn' src='http://secure.achatcentrale.fr/UploadFichiers/Uploads/FOURN_" . $fourn["FO_ID"] . "/" . $fourn["logo"] . "' alt=''></th><th class='raison_soc_top_fourn' >" . $helper->array_utf8_encode($fourn["fourn"]) . "</th><th class='qty_eco_top' >" . $fourn["eco"] . " €</th></tr>";
                            $final_tpl .= $tpl_temp;
                            break;
                    }
                }
                $final_tpl .= "</table>";

                return new JsonResponse($final_tpl, 200);
                break;
            //PFPL
            case 5:
                $sql = "SELECT DISTINCT
                          FO_ID,
                          (SELECT FO_RAISONSOC
                           FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS
                           WHERE CENTRALE_PRODUITS.dbo.FOURNISSEURS.FO_ID = CENTRALE_PFPL.dbo.CLIENTS_CONSO.FO_ID) as fourn,
                          SUM(CLC_PRIX_PUBLIC)                                                                      as achat,
                          SUM(CLC_PRIX_PUBLIC) - SUM(CLC_PRIX_CENTRALE)                                             as eco,
                          (SELECT FO_LOGO FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS WHERE CENTRALE_PRODUITS.dbo.FOURNISSEURS.FO_ID = CENTRALE_PFPL.dbo.CLIENTS_CONSO.FO_ID) as logo
                        FROM CENTRALE_PFPL.dbo.CLIENTS_CONSO
                        WHERE CL_ID = :id
                              AND CLC_DATE BETWEEN :start AND :end
                        GROUP BY FO_ID
                        ORDER BY eco DESC";

                $conn = $connection->prepare($sql);
                $conn->bindValue(':id', $id);
                $conn->bindValue('start', $start);
                $conn->bindValue('end', $end);
                $conn->execute();
                $ListFourn = $conn->fetchAll();

                $final_tpl = "";
                foreach ($ListFourn as $key => $fourn){
                    switch ($key) {
                        case 0:
                            $tpl_temp = "<tr><th><img src='number_one.png' class='logo_position_img'/></th><th><img class='img_logo_fourn' src='http://secure.achatcentrale.fr/UploadFichiers/Uploads/FOURN_" . $fourn["FO_ID"] . "/" . $fourn["logo"] . "' alt=''></th><th class='raison_soc_top_fourn' >" . $helper->array_utf8_encode($fourn["fourn"]) . "</th><th class='qty_eco_top' >" . $fourn["eco"] . " €</th></tr>";
                            $final_tpl .= $tpl_temp;

                            break;
                        case 1:
                            $tpl_temp = "<tr><th><img src='number_two.png' class='logo_position_img'/></th><th><img class='img_logo_fourn' src='http://secure.achatcentrale.fr/UploadFichiers/Uploads/FOURN_" . $fourn["FO_ID"] . "/" . $fourn["logo"] . "' alt=''></th><th class='raison_soc_top_fourn' >" . $helper->array_utf8_encode($fourn["fourn"]) . "</th><th class='qty_eco_top' >" . $fourn["eco"] . " €</th></tr>";
                            $final_tpl .= $tpl_temp;

                            break;
                        case 2:
                            $tpl_temp = "<tr><th><img src='number_three.png' class='logo_position_img'/></th><th><img class='img_logo_fourn' src='http://secure.achatcentrale.fr/UploadFichiers/Uploads/FOURN_" . $fourn["FO_ID"] . "/" . $fourn["logo"] . "' alt=''></th><th class='raison_soc_top_fourn' >" . $helper->array_utf8_encode($fourn["fourn"]) . "</th><th class='qty_eco_top' >" . $fourn["eco"] . " €</th></tr>";
                            $final_tpl .= $tpl_temp;
                            break;
                    }
                }
                $final_tpl .= "</table>";

                return new JsonResponse($final_tpl, 200);
                break;
            //ROC ECLERC
            case 6:
                $sql = "SELECT DISTINCT
                          FO_ID,
                          (SELECT FO_RAISONSOC
                           FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS
                           WHERE CENTRALE_PRODUITS.dbo.FOURNISSEURS.FO_ID = CENTRALE_ROC_ECLERC.dbo.CLIENTS_CONSO.FO_ID) as fourn,
                          SUM(CLC_PRIX_PUBLIC)                                                                      as achat,
                          SUM(CLC_PRIX_PUBLIC) - SUM(CLC_PRIX_CENTRALE)                                             as eco,
                          (SELECT FO_LOGO FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS WHERE CENTRALE_PRODUITS.dbo.FOURNISSEURS.FO_ID = CENTRALE_ROC_ECLERC.dbo.CLIENTS_CONSO.FO_ID) as logo
                        FROM CENTRALE_ROC_ECLERC.dbo.CLIENTS_CONSO
                        WHERE CL_ID = :id
                              AND CLC_DATE BETWEEN :start AND :end
                        GROUP BY FO_ID
                        ORDER BY eco DESC";

                $conn = $connection->prepare($sql);
                $conn->bindValue(':id', $id);
                $conn->bindValue('start', $start);
                $conn->bindValue('end', $end);
                $conn->execute();
                $ListFourn = $conn->fetchAll();

                $final_tpl = "";
                foreach ($ListFourn as $key => $fourn){
                    switch ($key) {
                        case 0:
                            $tpl_temp = "<tr><th><img src='number_one.png' class='logo_position_img'/></th><th><img class='img_logo_fourn' src='http://secure.achatcentrale.fr/UploadFichiers/Uploads/FOURN_" . $fourn["FO_ID"] . "/" . $fourn["logo"] . "' alt=''></th><th class='raison_soc_top_fourn' >" . $helper->array_utf8_encode($fourn["fourn"]) . "</th><th class='qty_eco_top' >" . $fourn["eco"] . " €</th></tr>";
                            $final_tpl .= $tpl_temp;

                            break;
                        case 1:
                            $tpl_temp = "<tr><th><img src='number_two.png' class='logo_position_img'/></th><th><img class='img_logo_fourn' src='http://secure.achatcentrale.fr/UploadFichiers/Uploads/FOURN_" . $fourn["FO_ID"] . "/" . $fourn["logo"] . "' alt=''></th><th class='raison_soc_top_fourn' >" . $helper->array_utf8_encode($fourn["fourn"]) . "</th><th class='qty_eco_top' >" . $fourn["eco"] . " €</th></tr>";
                            $final_tpl .= $tpl_temp;

                            break;
                        case 2:
                            $tpl_temp = "<tr><th><img src='number_three.png' class='logo_position_img'/></th><th><img class='img_logo_fourn' src='http://secure.achatcentrale.fr/UploadFichiers/Uploads/FOURN_" . $fourn["FO_ID"] . "/" . $fourn["logo"] . "' alt=''></th><th class='raison_soc_top_fourn' >" . $helper->array_utf8_encode($fourn["fourn"]) . "</th><th class='qty_eco_top' >" . $fourn["eco"] . " €</th></tr>";
                            $final_tpl .= $tpl_temp;
                            break;
                    }
                }
                $final_tpl .= "</table>";

                return new JsonResponse($final_tpl, 200);
                break;

        }


        return new JsonResponse("none", 200);

    }

}
