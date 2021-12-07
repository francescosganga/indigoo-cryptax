<?php

namespace CrypTax\Models;

use CrypTax\Models\PdfDocument;

class ModelloRedditi
{
    private $pdf = null;
    private $fiscalYear;

    public function __construct($info, $prefix) {
        $this->pdf = new PdfDocument();
        $source = dirname(__FILE__) . '/../../../resources/pdf/PF-' . static::FISCAL_YEAR . '.pdf';
        $dest = dirname(__FILE__) . '/../../../resources/pdf/' . $prefix . '-' . static::FISCAL_YEAR . '.pdf';
        file_put_contents($dest, file_get_contents($source));

        $this->fiscalYear = $info['fiscal_year'];

        if ($info['sections_required']['rl']) {
            ModelloRedditiRl::fill($this->pdf, $info, $this->fiscalYear, $prefix);
        }

        if ($info['sections_required']['rw']) {
            ModelloRedditiRw::fill($this->pdf, $info, $this->fiscalYear, $prefix);
        }

        if ($info['sections_required']['rt']) {
            ModelloRedditiRt::fill($this->pdf, $info, $this->fiscalYear, $prefix);
        }

        if ($info['sections_required']['rm']) {
            ModelloRedditiRm::fill($this->pdf, $info, $this->fiscalYear, $prefix);
        }
    }

    public function getPdf() {
        return $this->pdf->Output();
    }
}
