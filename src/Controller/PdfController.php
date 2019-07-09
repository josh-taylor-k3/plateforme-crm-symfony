<?php

namespace App\Controller;

use App\Service\HelperService;
use Doctrine\DBAL\Connection;
use Safe\Exceptions\FilesystemException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

use TheCodingMachine\Gotenberg\Client;
use TheCodingMachine\Gotenberg\DocumentFactory;
use TheCodingMachine\Gotenberg\HTMLRequest;
use TheCodingMachine\Gotenberg\Request;


/**
 * @Route("/pdf", name="pdf_")
 * @Method("GET")
 */
class PdfController extends AbstractController
{


    public function index($id, $centrale, HelperService $helper, Connection $connection)
    {



        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'helvetica');
        $pdfOptions->setIsHtml5ParserEnabled(true);
        $pdfOptions->setIsRemoteEnabled(true);


        $sql = "SELECT * FROM CENTRALE_ACHAT.dbo.AUDITS_ENTETE";
        $conn = $connection->prepare($sql);
        $conn->execute();
        $result = $conn->fetchAll();


        $dompdf = new Dompdf($pdfOptions);
        $html = $this->renderView('pdf/Audit.html.twig', []);
        $dompdf->loadHtml($html);

        $dompdf->render();

        $dompdf->stream("audit".$helper->gen_uuid().".pdf", [
            "Attachment" => true,
        ]);
    }

    /**
     * @Route("/audit/{id}/{centrale}", name="audit")
     * @Method("GET")
     */
    public function getAudit($id, $centrale, HelperService $helper, Connection $connection,KernelInterface $kernel)
    {

        // Recupération de l'entete de l'audit

        $sqlAuditEntete = "SELECT * FROM CENTRALE_ACHAT.dbo.AUDITS_ENTETE WHERE AE_ID = :id";
        $conn = $connection->prepare($sqlAuditEntete);
        $conn->bindValue('id', $id);
        $conn->execute();
        $auditEntete = $conn->fetchAll();

        // Recupération du client

        $sqlClient = "SELECT * FROM CENTRALE_ACHAT.dbo.CLIENTS WHERE CL_ID = :client_id";
        $conn = $connection->prepare($sqlClient);
        $conn->bindValue('client_id', $auditEntete[0]["CL_ID"]);
        $conn->execute();
        $client = $conn->fetchAll()[0];


        // Récupération de l'utilisateur

        $sqlClient = "SELECT * FROM CENTRALE_ACHAT.dbo.CLIENTS_USERS WHERE CC_ID = :cc_id";
        $conn = $connection->prepare($sqlClient);
        $conn->bindValue('cc_id', $auditEntete[0]["CC_ID"]);
        $conn->execute();
        $users = $conn->fetchAll()[0];

        //Récupération liste catégories

        $sqlCate = "SELECT CENTRALE_ACHAT.dbo.Categories.CatID, CatTitre, SUM(AD_PRIX_ACTU*AD_QTE) AS AD_PRIX_ACTU, SUM(AD_PRIX_CA*AD_QTE) AS AD_PRIX_CA, ROUND((1-(SUM(AD_PRIX_CA*AD_QTE)/SUM(AD_PRIX_ACTU*AD_QTE)))*100,2) AS AD_ECO_PCT  FROM CENTRALE_ACHAT.dbo.AUDITS_DETAIL  INNER JOIN CENTRALE_PRODUITS.dbo.PRODUITS ON AUDITS_DETAIL.PR_ID = PRODUITS.PR_ID  INNER JOIN CENTRALE_ACHAT.dbo.CATEG_RAYONS ON PRODUITS.RA_ID = CATEG_RAYONS.RA_ID  INNER JOIN CENTRALE_ACHAT.dbo.Categories ON CATEG_RAYONS.CatID = Categories.CatID  WHERE AE_ID = :id GROUP BY Categories.CatID, CatTitre  ORDER BY CatTitre";

        $conn = $connection->prepare($sqlCate);
        $conn->bindValue('id', $id);
        $conn->execute();
        $catLst = $conn->fetchAll();


        //Récupération detail audit
        $sqlDetail = "SELECT SUM(AD_PRIX_ACTU*AD_QTE) AS AD_PRIX_ACTU FROM CENTRALE_ACHAT.dbo.AUDITS_DETAIL WHERE AE_ID = :id ";

        $conn = $connection->prepare($sqlDetail);
        $conn->bindValue('id', $id);
        $conn->execute();
        $auditDetail = $conn->fetchAll()[0];

        $logo = $helper->getAvatarFromClient($auditEntete[0]["CL_ID"], $centrale);



        dump($catLst);





        return $this->render('pdf/Audit.html.twig', [
           "auditEntete" => $auditEntete,
           "client" => $client,
           "users" => $users,
           "catLst" => $catLst,
           "auditDetail" => $auditDetail,
           "logo" => $logo,
        ]);



        $html = $this->renderView('pdf/Audit.html.twig', [
            "auditEntete" => $auditEntete,
            "client" => $client,
            "users" => $users,
            "catLst" => $catLst,
            "auditDetail" => $auditDetail,
            "logo" => $logo,
        ]);




        $client = new Client('http://localhost:3000', new \Http\Adapter\Guzzle6\Client());

        try {
            $index = DocumentFactory::makeFromString('index.html', $html);
        } catch (FilesystemException $e) {
        }

        $request = new HTMLRequest($index);
        $request->setPaperSize(Request::A4);
        $request->setMargins(Request::NO_MARGINS);

        $dirPath = $kernel->getProjectDir()."/public/pdf/audit".$helper->gen_uuid().".pdf";
        $filename = $client->store($request, $dirPath);


        $file = new File($dirPath);

        return $this->file($dirPath,"audit".$helper->gen_uuid().".pdf", ResponseHeaderBag::DISPOSITION_INLINE);
    }



    /**
     * @Route("/test", name="audit_test")
     * @Method("GET")
     */
    public function testPDF()
    {
        return $this->render('pdf/Audit.html.twig');
    }

}