<?php

namespace CrypTax\Controllers;

use CrypTax\Utils\DateUtils;
use CrypTax\Models\ReportViewer;
use CrypTax\Models\ReportWrapper;

use Exception;

use CrypTax\Utils\CryptoInfoUtils;

class MainController
{
    const ACTION_PDF_MODELLO_REDDITI = 'pdf_modello_redditi';
    const ACTION_PDF_MODELLO_F24 = 'pdf_modello_f24';

    public static function run($data) {
        $reportWrapper = new ReportWrapper($data);

        $year = isset($_GET['year']) ? intval($_GET['year']) : DateUtils::getCurrentYear();
        $action = $_GET['action'] ?? null;
        $compensateCapitalLosses = filter_var($_GET['compensate_losses'] ?? true, FILTER_VALIDATE_BOOLEAN);

        switch ($action) {
            case self::ACTION_PDF_MODELLO_REDDITI:
                echo $reportWrapper->getModelloRedditi($year, $compensateCapitalLosses);
            case self::ACTION_PDF_MODELLO_F24:
                echo $reportWrapper->getModelloF24($year, $compensateCapitalLosses);
            default:
                echo $reportWrapper->getReport($year);
        }
    }
}
