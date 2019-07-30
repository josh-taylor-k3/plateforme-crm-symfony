<?php

namespace App\Twig;

use Doctrine\DBAL\Connection;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{

    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('photo', [$this, 'getPhoto']),
            new TwigFilter('ean', [$this, 'formatEAN']),
            new TwigFilter('encodingFrom', [$this, 'encodingFromDatabase']),

        ];
    }

    public function getPhoto($pr_id)
    {

        $sql = "SELECT * FROM CENTRALE_PRODUITS.dbo.PRODUITS_PHOTOS WHERE PR_ID = :id AND PP_TYPE = 'PRINCIPALE' ";


        $conn = $this->connection->prepare($sql);
        $conn->bindValue('id', $pr_id);
        $conn->execute();
        $photo = $conn->fetchAll();

        $string = sprintf("https://secure.achatcentrale.fr/UploadFichiers/Uploads/PRODUIT_%s/%s", $pr_id, $photo[0]["PP_FICHIER"]);

        return $string;
    }


    public function encodingFromDatabase($value){
        return utf8_encode($value);
    }

}