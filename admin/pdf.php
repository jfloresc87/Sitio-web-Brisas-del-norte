<?php
/* Genera el PDF horizontal del comprobante (nuevo o reimpresión) desde la BDD */
require __DIR__.'/../includes/bootstrap.php'; require_login();
require ROOT.'/includes/fpdf/fpdf.php';
$st = db(DB_REC)->prepare("SELECT * FROM receipts WHERE id=?"); $st->execute([(int)($_GET['id'] ?? 0)]);
$r = $st->fetch(); if (!$r) { http_response_code(404); exit('Comprobante no encontrado.'); }
function t($s) { return mb_convert_encoding((string)$s, 'ISO-8859-1', 'UTF-8'); }
function fdate($d) { return $d ? date('d/m/Y', strtotime($d)) : ''; }
$num = str_pad($r['id'], 6, '0', STR_PAD_LEFT);
$detail = $r['concept'];
if ($r['concept'] === 'Arriendo de Cancha') $detail .= sprintf(' - %d hr (%02d:00 a %02d:00)', $r['hour_qty'], $r['hour_start'], $r['hour_start'] + $r['hour_qty']);
if ($r['concept'] === 'Otros Pagos') $detail = 'Otros pagos: '.$r['other_concept'];

$pdf = new FPDF('L', 'mm', 'A5'); $pdf->SetAutoPageBreak(false); $pdf->AddPage(); $pdf->SetMargins(12, 10, 12);
$sea = [14,74,94];
if (is_file(ROOT.'/'.$JUNTA['logo'])) $pdf->Image(ROOT.'/'.$JUNTA['logo'], 12, 9, 26);          // logo arriba-izquierda
$pdf->SetTextColor(...$sea); $pdf->SetFont('Helvetica', 'B', 18); $pdf->SetXY(60, 12); $pdf->Cell(138, 9, t('COMPROBANTE DE PAGO'), 0, 1, 'R');
$pdf->SetFont('Helvetica', 'B', 14); $pdf->SetX(60); $pdf->Cell(138, 8, t('N° '.$num), 0, 1, 'R');
$pdf->SetFont('Helvetica', '', 10); $pdf->SetTextColor(80); $pdf->SetX(60); $pdf->Cell(138, 6, t('Fecha de emisión: '.date('d/m/Y H:i', strtotime($r['issued_at']))), 0, 1, 'R');
$pdf->SetDrawColor(...$sea); $pdf->SetLineWidth(.6); $pdf->Line(12, 40, 198, 40);

$rows = [['Recibido de', $r['name'].' '.$r['lastname']]];
if ($r['rut']) $rows[] = ['RUT', $r['rut']];
if ($r['phone']) $rows[] = ['Teléfono', $r['phone']];
$rows[] = ['Concepto de pago', $detail];
if ($r['event_date']) $rows[] = ['Fecha del evento', fdate($r['event_date'])];
$rows[] = ['Forma de pago', $r['method']];
$y = 47;
foreach ($rows as [$k, $v]) {
  $pdf->SetXY(12, $y); $pdf->SetFont('Helvetica', 'B', 11); $pdf->SetTextColor(...$sea); $pdf->Cell(48, 7, t($k), 0, 0);
  $pdf->SetFont('Helvetica', '', 11); $pdf->SetTextColor(30); $pdf->MultiCell(138, 7, t($v)); $y = $pdf->GetY() + 1.5;
}
$pdf->SetFillColor(238, 245, 246); $pdf->Rect(12, 100, 186, 16, 'F');
$pdf->SetXY(16, 102); $pdf->SetFont('Helvetica', 'B', 12); $pdf->SetTextColor(...$sea); $pdf->Cell(60, 12, t('VALOR PAGADO'), 0, 0);
$pdf->SetFont('Helvetica', 'B', 22); $pdf->Cell(118, 12, t(clp($r['amount'])), 0, 0, 'R');
// Pie de página con datos de la junta
$pdf->SetDrawColor(180); $pdf->SetLineWidth(.2); $pdf->Line(12, 126, 198, 126);
$pdf->SetFont('Helvetica', 'B', 9); $pdf->SetTextColor(60); $pdf->SetXY(12, 128); $pdf->Cell(186, 5, t($JUNTA['nombre']), 0, 1, 'C');
$pdf->SetFont('Helvetica', '', 9); $pdf->SetX(12); $pdf->Cell(186, 5, t($JUNTA['direccion'].'  |  Tel: '.$JUNTA['telefono']), 0, 1, 'C');
$pdf->SetTitle(t('Comprobante N° '.$num)); $pdf->Output('I', "comprobante-$num.pdf");
