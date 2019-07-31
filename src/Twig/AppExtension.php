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
            new TwigFilter('ean', [$this, 'formatEanNumber']),
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

    public function formatEanNumber($ean)
    {

        $result = sprintf("<div class=\"ean\">
                        <p style=\"font-size: 8pt;margin: 0;background-color: white;\" >%s</p>
                        <p style=\"font-size: 8pt;margin: 0;margin-left: 6px;background-color: white;padding-left: 1px;\" >%s</p>
                        <p style=\"font-size: 8pt;margin: 0;margin-left: 6px;background-color: white;padding-left: 2px;\" >%s</p> 
                    </div>", $ean[0], substr($ean, 1, 7 - 1 ), substr($ean, 7, strlen($ean)));

        return $result;
    }

}