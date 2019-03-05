<?php

namespace App\Controller;

use App\Service\HelperService;
use Doctrine\DBAL\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * @Route("/pdf", name="pdf_")
 * @Method("GET")
 */
class PdfController extends Controller
{


    /**
     * @Route("/audit/{id}/{centrale}", name="audit")
     * @Method("GET")
     */
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
     * @Route("/test", name="audit_test")
     * @Method("GET")
     */
    public function testPDF()
    {



        return $this->render('pdf/Audit.html.twig');
    }

}