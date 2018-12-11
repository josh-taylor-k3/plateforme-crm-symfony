<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dompdf\Dompdf;

/**
 * @Route("/pdf", name="pdf_")
 * @Method("GET")
 */
class PdfController extends Controller
{


    /**
     * @Route("/audit/{id}", name="audit")
     * @Method("GET")
     */
    public function index($id)
    {

        $dompdf = new Dompdf(array('enable_remote' => true));

        $url = sprintf('http://v2.achatcentrale.fr/extranet/Audits_PRN.asp?AE_ID=%d', $id);

        $html = file_get_contents($url);

        $dompdf->loadHtml($html);

        $dompdf->render();

        $dompdf->stream();

    }
}