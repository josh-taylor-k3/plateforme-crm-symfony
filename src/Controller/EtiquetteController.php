<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Safe\Exceptions\FilesystemException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

use TheCodingMachine\Gotenberg\Client;
use TheCodingMachine\Gotenberg\DocumentFactory;
use TheCodingMachine\Gotenberg\HTMLRequest;
use TheCodingMachine\Gotenberg\Request;

/**
 * @Route("/etiquette", name="etiquettes_")
 */
class EtiquetteController extends AbstractController
{

    /**
     * @Route("/{me_id}", name="base_etiquettes")
     */
    public function index(KernelInterface $kernel, $me_id, Connection $conn)
    {
        //tableau vide contenant les infos pour chaque case d'étiquettes
        $result = [];

        //nombre de produit == etiquettes a rendre
        $countQty = 0;


        // On récuoère la commande
        $sqlCommande = "SELECT PR_ID, CD_QTE FROM CENTRALE_ACHAT_v2.dbo.COMMANDE_DETAIL INNER JOIN CENTRALE_ACHAT_v2.dbo.COMMANDE_ENTETE ON CENTRALE_ACHAT_v2.dbo.COMMANDE_DETAIL.CE_ID = CENTRALE_ACHAT_v2.dbo.COMMANDE_ENTETE.CE_ID where ME_ID = :id";

        $stmt = $conn->prepare($sqlCommande);
        $stmt->bindValue("id", $me_id);
        $stmt->execute();
        $commande = $stmt->fetchAll();
        $stmt->closeCursor();

        foreach ($commande as $key => $value){
            // On recupére le code barre
            $sqlBarcode = "SELECT PR_EAN, PR_REF_FRS, PR_NOM, PR_PRIX_CA FROM CENTRALE_PRODUITS.dbo.PRODUITS where PR_ID = :id";

            $stmtBarcode = $conn->prepare($sqlBarcode);
            $stmtBarcode->bindValue("id", $value["PR_ID"]);
            $stmtBarcode->execute();
            $etiquettes = $stmtBarcode->fetchAll()[0];

            $barcode = "";

            if ($etiquettes["PR_EAN"])
            {
                $generator = new BarcodeGeneratorPNG();
                $barcode =  '<img style="height: 45px; padding: 0px 4px;" src="data:image/png;base64,' . base64_encode($generator->getBarcode($etiquettes["PR_EAN"], $generator::TYPE_EAN_13, 1, 60)) . '">';
            }

            $countQty += $value["CD_QTE"];

            $tempResult = [
                "barcode" => $barcode,
                "EAN" => $etiquettes["PR_EAN"],
                "ref" => $etiquettes["PR_REF_FRS"],
                "nom" => $etiquettes["PR_NOM"],
                "prix" => $etiquettes["PR_PRIX_CA"],
                "qty" => $value["CD_QTE"],
            ];
            array_push($result, $tempResult);
        }



//        return $this->render('Etiquette/index.html.twig', [
//           "etiquette" => $result,
//            "qty" => $countQty,
//        ]);




        $html =  $this->renderView('Etiquette/index.html.twig', [
            "etiquette" => $result,
            "qty" => $countQty,
        ]);


        $client = new Client('http://localhost:3000', new \Http\Adapter\Guzzle6\Client());

        try {
            $index = DocumentFactory::makeFromString('index.html', $html);
        } catch (FilesystemException $e) {
        }

        $request = new HTMLRequest($index);
        $request->setPaperSize(Request::A4);
        $request->setMargins(Request::NO_MARGINS);

        $dirPath = $kernel->getProjectDir()."/public/pdf/".uniqid("etiquettes-").".pdf";
        $filename = $client->store($request, $dirPath);


        $file = new File($dirPath);

        return $this->file($dirPath,uniqid("etiquettes-").".pdf", ResponseHeaderBag::DISPOSITION_INLINE);



    }


}
