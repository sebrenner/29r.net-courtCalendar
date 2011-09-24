<?php
require('../fpdf.php');

// letter size is 216 × 279
// 8 mm margins leaves
// 200 x 263,  center is at 95, middle is at 140

class PDF extends FPDF
{
// Page footer
function Footer()
{
    // Position at 1.5 cm from bottom
    $this->SetY(-15);
    // Arial italic 8
    $this->SetFont('Arial','I',8);
    // Page number
    $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
}
}

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);

$pdf->Cell(95,52.75,'Hello World !',1);
$pdf->Cell(95,52.75,'Hello World !',1,1);

$pdf->Cell(95,52.75,'Hello World !',1);
$pdf->Cell(95,52.75,'Hello World !',1,1);

$pdf->Cell(95,52.75,'Hello World !',1);
$pdf->Cell(95,52.75,'Hello World !',1,1);

$pdf->Cell(95,52.75,'Hello World !',1);
$pdf->Cell(95,52.75,'Hello World !',1,1);

$pdf->Cell(95,52.75,'Hello World !',1);
$pdf->Cell(95,52.75,'Hello World !',1,1);

// Page footer
function Footer()
{
    // Position at 1.5 cm from bottom
    $this->SetY(-15);
    // Arial italic 8
    $this->SetFont('Arial','I',8);
    // Page number
    $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
}


$pdf->Output();
?>
