<?php
require('../fpdf.php');

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

// Instanciation of inherited class
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

$pdf->SetFont('Arial','B',24);
$pdf->Cell(95,12,'A 10 11407',0,2);
$pdf->SetFont('Times','',13);
$pdf->Cell(95, 5, "David Smith, et al. v. Administrator BWC, et al.",0,2);
$pdf->Cell(95,7,'Judge Nadine Allen ----- Room: 495',0,2);
$pdf->Cell(95,7,'2011-09-08  @ 9:00AM:',0,2);
$pdf->Cell(95,7,'   CMC Initial Case Management Conference',0,2);
$pdf->Cell(95,7,'Scott: 6-6106 // Bailiff: 6-5112',0,2);
$pdf->Cell(95,5,'',0,2);

$pdf->SetFont('Arial','B',24);
$pdf->Cell(95,12,'A 10 11407',0,2);
$pdf->SetFont('Times','',13);
$pdf->Cell(95, 5, "David Smith, et al. v. Administrator BWC, et al.",0,2);
$pdf->Cell(95,7,'Judge Nadine Allen ----- Room: 495',0,2);
$pdf->Cell(95,7,'2011-09-08  @ 9:00AM:',0,2);
$pdf->Cell(95,7,'   CMC Initial Case Management Conference',0,2);
$pdf->Cell(95,7,'Scott: 6-6106 // Bailiff: 6-5112',0,2);
$pdf->Cell(95,5,'',0,2);

$pdf->SetFont('Arial','B',24);
$pdf->Cell(95,12,'A 10 11407',0,2);
$pdf->SetFont('Times','',13);
$pdf->Cell(95, 5, "David Smith, et al. v. Administrator BWC, et al.",0,2);
$pdf->Cell(95,7,'Judge Nadine Allen ----- Room: 495',0,2);
$pdf->Cell(95,7,'2011-09-08  @ 9:00AM:',0,2);
$pdf->Cell(95,7,'   CMC Initial Case Management Conference',0,2);
$pdf->Cell(95,7,'Scott: 6-6106 // Bailiff: 6-5112',0,2);
$pdf->Cell(95,5,'',0,2);

$pdf->SetFont('Arial','B',24);
$pdf->Cell(95,12,'A 10 11407',0,2);
$pdf->SetFont('Times','',13);
$pdf->Cell(95, 5, "David Smith, et al. v. Administrator BWC, et al.",0,2);
$pdf->Cell(95,7,'Judge Nadine Allen ----- Room: 495',0,2);
$pdf->Cell(95,7,'2011-09-08  @ 9:00AM:',0,2);
$pdf->Cell(95,7,'   CMC Initial Case Management Conference',0,2);
$pdf->Cell(95,7,'Scott: 6-6106 // Bailiff: 6-5112',0,2);
$pdf->Cell(95,5,'',0,2);

$pdf->SetFont('Arial','B',24);
$pdf->Cell(95,12,'A 10 11407',0,2);
$pdf->SetFont('Times','',13);
$pdf->Cell(95, 5, "David Smith, et al. v. Administrator BWC, et al.",0,2);
$pdf->Cell(95,7,'Judge Nadine Allen ----- Room: 495',0,2);
$pdf->Cell(95,7,'2011-09-08  @ 9:00AM:',0,2);
$pdf->Cell(95,7,'   CMC Initial Case Management Conference',0,2);
$pdf->Cell(95,7,'Scott: 6-6106 // Bailiff: 6-5112',0,2);
$pdf->Cell(95,5,'',0,2);

// move cell to top of second column
$pdf->SetXY(115,10);

$pdf->SetFont('Arial','B',24);
$pdf->Cell(95,12,'A 10 11407',0,2);
$pdf->SetFont('Times','',13);
$pdf->Cell(95, 5, "Davsdfid Smith, et al. v. Administrator BWC, et al.",0,2);
$pdf->Cell(95,7,'Judge Nadiadne Allen ----- Room: 495',0,2);
$pdf->Cell(95,7,'2011-09-08  @ 9:00AM:',0,2);
$pdf->Cell(95,7,'   CMC Initial Case Management Conference',0,2);
$pdf->Cell(95,7,'Scott: 6-6106 // Bailiff: 6-5112',0,2);
$pdf->Cell(95,5,'',0,2);

$pdf->SetFont('Arial','B',24);
$pdf->Cell(95,12,'A 10 11407',0,2);
$pdf->SetFont('Times','',13);
$pdf->Cell(95, 5, "David Smith, et al. v. Administrator BWC, et al.",0,2);
$pdf->Cell(95,7,'Judge Nadine Allen ----- Room: 495',0,2);
$pdf->Cell(95,7,'2011-09-08  @ 9:00AM:',0,2);
$pdf->Cell(95,7,'   CMC Initial Case Management Conference',0,2);
$pdf->Cell(95,7,'Scott: 6-6106 // Bailiff: 6-5112',0,2);
$pdf->Cell(95,5,'',0,2);

$pdf->SetFont('Arial','B',24);
$pdf->Cell(95,12,'A 10 11407',0,2);
$pdf->SetFont('Times','',13);
$pdf->Cell(95, 5, "David Smith, et al. v. Administrator BWC, et al.",0,2);
$pdf->Cell(95,7,'Judge Nadine Allen ----- Room: 495',0,2);
$pdf->Cell(95,7,'2011-09-08  @ 9:00AM:',0,2);
$pdf->Cell(95,7,'   CMC Initial Case Management Conference',0,2);
$pdf->Cell(95,7,'Scott: 6-6106 // Bailiff: 6-5112',0,2);
$pdf->Cell(95,5,'',0,2);

$pdf->SetFont('Arial','B',24);
$pdf->Cell(95,12,'A 10 11407',0,2);
$pdf->SetFont('Times','',13);
$pdf->Cell(95, 5, "David Smith, et al. v. Administrator BWC, et al.",0,2);
$pdf->Cell(95,7,'Judge Nadine Allen ----- Room: 495',0,2);
$pdf->Cell(95,7,'2011-09-08  @ 9:00AM:',0,2);
$pdf->Cell(95,7,'   CMC Initial Case Management Conference',0,2);
$pdf->Cell(95,7,'Scott: 6-6106 // Bailiff: 6-5112',0,2);
$pdf->Cell(95,5,'',0,2);

$pdf->SetFont('Arial','B',24);
$pdf->Cell(95,12,'A 10 11407',0,2);
$pdf->SetFont('Times','',13);
$pdf->Cell(95, 5, "David Smith, et al. v. Administrator BWC, et al.",0,2);
$pdf->Cell(95,7,'Judge Nadine Allen ----- Room: 495',0,2);
$pdf->Cell(95,7,'2011-09-08  @ 9:00AM:',0,2);
$pdf->Cell(95,7,'   CMC Initial Case Management Conference',0,2);
$pdf->Cell(95,7,'Scott: 6-6106 // Bailiff: 6-5112',0,2);
$pdf->Cell(95,5,'',0,2);


$pdf->Output();
?>
