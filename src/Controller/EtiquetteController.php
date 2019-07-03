<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Picqer\Barcode\BarcodeGeneratorHTML;

/**
 * @Route("/etiquette", name="etiquettes_")
 */
class EtiquetteController extends AbstractController
{

    /**
     * @Route("/", name="base_etiquettes")
     */
    public function index()
    {




        $generator = new BarcodeGeneratorHTML();
        $barcode =  $generator->getBarcode('081231723897', $generator::TYPE_EAN_13, 1, 60);


        return $this->render('Etiquette/index.html.twig', [
            "barcode" => $barcode
        ]);
    }


}
