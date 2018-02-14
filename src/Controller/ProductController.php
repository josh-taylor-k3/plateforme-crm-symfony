<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ProductController extends Controller
{
    /**
     * @Route("/produits", name="produits")
     */
    public function produits(Connection $connection, Request $request)
    {

        $limit = $request->query->get('limit');

        if (isset($limit)){
            $sql = "SELECT TOP ".$limit."
                      PR_ID,
                      SO_ID,
                      (
                        SELECT FO_RAISONSOC
                        FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS
                        WHERE FOURNISSEURS.FO_ID = PRODUITS.FO_ID
                      ) as Fournisseur ,
                      (
                        SELECT RA_NOM
                        FROM CENTRALE_PRODUITS.dbo.RAYONS
                        WHERE RAYONS.RA_ID = PRODUITS.RA_ID
                      ) as Rayon,
                      (
                        SELECT FA_NOM
                        FROM CENTRALE_PRODUITS.dbo.FAMILLES
                        WHERE FAMILLES.FA_ID = PRODUITS.FA_ID
                      ) as Famille,
                      PR_REF,
                      PR_REF_FRS,
                      PR_EAN,
                      PR_NOM,
                      PR_DESCR_COURTE,
                      PR_DESCR_LONGUE,
                      PR_TRIPTYQUE,
                      PR_QTE_CMDE,
                      PR_CONDT,
                      PR_PRIX_PUBLIC,
                      PR_PRIX_CA,
                      PR_REMISE,
                      PR_PRIX_VC,
                      PR_TYPE_LIEN,
                      PR_LIEN,
                      PR_PHARE,
                      PR_STATUS
                    FROM CENTRALE_PRODUITS.dbo.PRODUITS";

        }
        $em = $this->getDoctrine()->getManager('ac_produits');

        $conn = $connection->prepare($sql);
        $conn->execute();
        $result = $conn->fetchAll();


        return new JsonResponse($result, 200);

    }


    /**
     * @Route("/produit/{id}", name="produit")
     */
    public function produit(Connection $connection, Request $request, $id)
    {


            $sql = "SELECT 
                      PR_ID,
                      SO_ID,
                      (
                        SELECT FO_RAISONSOC
                        FROM CENTRALE_PRODUITS.dbo.FOURNISSEURS
                        WHERE FOURNISSEURS.FO_ID = PRODUITS.FO_ID
                      ) as Fournisseur ,
                      (
                        SELECT RA_NOM
                        FROM CENTRALE_PRODUITS.dbo.RAYONS
                        WHERE RAYONS.RA_ID = PRODUITS.RA_ID
                      ) as Rayon,
                      (
                        SELECT FA_NOM
                        FROM CENTRALE_PRODUITS.dbo.FAMILLES
                        WHERE FAMILLES.FA_ID = PRODUITS.FA_ID
                      ) as Famille,
                      PR_REF,
                      PR_REF_FRS,
                      PR_EAN,
                      PR_NOM,
                      PR_DESCR_COURTE,
                      PR_DESCR_LONGUE,
                      PR_TRIPTYQUE,
                      PR_QTE_CMDE,
                      PR_CONDT,
                      PR_PRIX_PUBLIC,
                      PR_PRIX_CA,
                      PR_REMISE,
                      PR_PRIX_VC,
                      PR_TYPE_LIEN,
                      PR_LIEN,
                      PR_PHARE,
                      PR_STATUS
                    FROM CENTRALE_PRODUITS.dbo.PRODUITS
                    WHERE PR_ID = :id";

        $em = $this->getDoctrine()->getManager('ac_produits');

        $conn = $connection->prepare($sql);
        $conn->bindValue('id', $id);


        $conn->execute();
        $result = $conn->fetchAll();


        return new JsonResponse($result[0], 200);


    }



}
