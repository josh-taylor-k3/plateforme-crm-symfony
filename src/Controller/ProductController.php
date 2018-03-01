<?php

namespace App\Controller;

use App\Security\ApiKeyAuth;
use App\Service\HelperService;
use App\Service\LogHsitory;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class ProductController extends Controller
{


    /**
     * @Route("/produits", name="produits")
     * @Method("GET")
     */
    public function produits(Connection $connection, Request $request, HelperService $helper, ApiKeyAuth $auth, LogHsitory $log)
    {

        header("Access-Control-Allow-Origin: *");


        $key = $request->headers->get('X-ac-key');

        if (isset($key) && $auth->grant($key)) {
            $limit = $request->query->get('limit');

            if (isset($limit)) {
                $sql = "SELECT TOP " . $limit . "
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

            } else {

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
                    FROM CENTRALE_PRODUITS.dbo.PRODUITS";
            }

            $conn = $connection->prepare($sql);
            $conn->execute();
            $result = $conn->fetchAll();

            if (!isset($result)) {
                return new JsonResponse("Aucun produit trouvé", 200);

            }

            $data = $helper->array_utf8_encode($result);

            $id = $helper->getIdFromApiKey($key);

//            $log->logAction($id[0]['APP_ID'], "get:produits");
            return new JsonResponse($data, 200);
        } else {

            return new JsonResponse("Vous n'avez pas accès a ces ressources", 500);


        }

    }


    /**
     * @Route("/produit/{id}", name="produit")
     * @Method("GET")
     *
     */
    public function produit(Connection $connection, Request $request, $id, HelperService $helper, ApiKeyAuth $auth,LogHsitory $log)
    {


        header("Access-Control-Allow-Origin: *");


        $key = $request->headers->get('X-ac-key');

        if (isset($key) && $auth->grant($key)) {
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


            $conn = $connection->prepare($sql);
            $conn->bindValue('id', $id);


            $conn->execute();
            $result = $conn->fetchAll();


            if (!isset($result)) {
                return new JsonResponse("Aucun produit trouvé pour l'id " . $id, 200);

            }

            $data = $helper->array_utf8_encode($result[0]);


            $id = $helper->getIdFromApiKey($key);

            $log->logAction($id[0]['APP_ID'], "get:produit");

            return new JsonResponse($data, 200);

        } else {
            return new JsonResponse("Vous n'avez pas accès a ces ressources", 500);

        }


    }


    /**
     * @Route("/produits/{id}/update", name="produit_update")
     * @Method("PUT")
     */
    public function produitUpdate(Connection $connection, Request $request, $id,HelperService $helper, ApiKeyAuth $auth,LogHsitory $log)
    {

        $key = $request->headers->get('X-ac-key');

        if (isset($key) && $auth->grant($key)) {
            $ref = $request->query->get('reference');
            $refFrs = $request->query->get('reference_fournisseur');
            $ean = $request->query->get('EAN');
            $nom = $request->query->get('nom');
            $descr_courte = $request->query->get('description_courte');
            $descr_longue = $request->query->get('description_longue');
            $tryptique = $request->query->get('tryptique');
            $qte = $request->query->get('qte_commande');
            $condt = $request->query->get('conditionnement');
            $prixPub = $request->query->get('prix_public');
            $prixCa = $request->query->get('prix_ca');
            $prixRemise = $request->query->get('prix_remise');
            $prixVc = $request->query->get('prix_vente_conseille');
            $typeLien = $request->query->get('type_lien');
            $lien = $request->query->get('lien');
            $phare = $request->query->get('produit_phare');
            $status = $request->query->get('status');


            $sql = "
        UPDATE CENTRALE_PRODUITS.dbo.PRODUITS
        SET PR_REF = :ref, PR_REF_FRS = :refFrs, PR_EAN = :ean, PR_NOM = :nom, PR_DESCR_COURTE = :descrCourte, PR_DESCR_LONGUE = :descrLongue, PR_TRIPTYQUE = :tryptique, PR_QTE_CMDE = :qteCmde, PR_CONDT = :condt, PR_PRIX_PUBLIC = :prixPub, PR_PRIX_CA = :prixCa, 
        PR_REMISE = :remise, PR_PRIX_VC = :prixVc, PR_TYPE_LIEN = :typeLien, PR_LIEN = :lien, PR_PHARE = :phare, PR_STATUS = :status, MAJ_DATE = GETDATE(), INS_USER = 'API'
        WHERE PR_ID = :id";


            $conn = $connection->prepare($sql);
            $conn->bindParam('id', $id, \Doctrine\DBAL\Types\Type::INTEGER);
            $conn->bindParam('ref', $ref, \Doctrine\DBAL\Types\Type::STRING);
            $conn->bindParam('refFrs', $refFrs, \Doctrine\DBAL\Types\Type::STRING);
            $conn->bindParam('ean', $ean, \Doctrine\DBAL\Types\Type::STRING);
            $conn->bindParam('nom', $nom, \Doctrine\DBAL\Types\Type::STRING);
            $conn->bindParam('descrCourte', $descr_courte, \Doctrine\DBAL\Types\Type::STRING);
            $conn->bindParam('descrLongue', $descr_longue, \Doctrine\DBAL\Types\Type::STRING);
            $conn->bindParam('tryptique', $tryptique, \Doctrine\DBAL\Types\Type::STRING);
            $conn->bindParam('qteCmde', $qte, \Doctrine\DBAL\Types\Type::INTEGER);
            $conn->bindParam('condt', $condt, \Doctrine\DBAL\Types\Type::INTEGER);
            $conn->bindParam('prixPub', $prixPub, \Doctrine\DBAL\Types\Type::FLOAT);
            $conn->bindParam('prixCa', $prixCa, \Doctrine\DBAL\Types\Type::FLOAT);
            $conn->bindParam('remise', $prixRemise, \Doctrine\DBAL\Types\Type::FLOAT);
            $conn->bindParam('prixVc', $prixVc, \Doctrine\DBAL\Types\Type::FLOAT);
            $conn->bindParam('typeLien', $typeLien, \Doctrine\DBAL\Types\Type::STRING);
            $conn->bindParam('lien', $lien, \Doctrine\DBAL\Types\Type::STRING);
            $conn->bindParam('phare', $phare, \Doctrine\DBAL\Types\Type::INTEGER);
            $conn->bindParam('status', $status, \Doctrine\DBAL\Types\Type::INTEGER);

            $conn->execute();
            $result = $conn->fetchAll();

            $id = $helper->getIdFromApiKey($key);

            $log->logAction($id[0]['APP_ID'], "put:produit");


            return new JsonResponse($result, 200);


        } else {
            return new JsonResponse("Vous n'avez pas accès a ces ressources", 500);

        }


    }


    /**
     * @Route("/produit/new", name="produit_new")
     * @Method("POST")
     */
    public function produitNew(Connection $connection, Request $request, HelperService $helper, ApiKeyAuth $auth,LogHsitory $log)
    {

        $key = $request->headers->get('X-ac-key');

        if (isset($key) && $auth->grant($key)) {


            $ref = $request->query->get('reference');
            $refFrs = $request->query->get('reference_fournisseur');
            $ean = $request->query->get('EAN');
            $nom = $request->query->get('nom');
            $descr_courte = $request->query->get('description_courte');
            $descr_longue = $request->query->get('description_longue');
            $tryptique = $request->query->get('tryptique');
            $qte = $request->query->get('qte_commande');
            $condt = $request->query->get('conditionnement');
            $prixPub = $request->query->get('prix_public');
            $prixCa = $request->query->get('prix_ca');
            $prixRemise = $request->query->get('prix_remise');
            $prixVc = $request->query->get('prix_vente_conseille');
            $typeLien = $request->query->get('type_lien');
            $lien = $request->query->get('lien');
            $phare = $request->query->get('produit_phare');
            $status = $request->query->get('status');




            $id = $helper->getIdFromApiKey($key);

            $log->logAction($id[0]['APP_ID'], "post:produit");



        } else {
            return new JsonResponse("Vous n'avez pas accès a ces ressources", 500);

        }


        return new JsonResponse("ok", 200);
    }


}
