<?php
/**
 * File: download_busta_pdf.php
 * Description: Main functionality for this module.
 * Features: Data processing, Database interaction, User interface.
 * Usage: Accessed via web browser or API endpoint.
 */

// ===== SEZIONE 1: LOGICA DI PROCESSO =====
declare(strict_types=1);

// auth.php include database.php e verifica il JWT; se non loggato risponde 401
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../../../vendor/autoload.php';

$userId = $currentUser['user_id'];
$idBusta = (int)($_GET['id_busta'] ?? 0);

if ($idBusta <= 0) {
    http_response_code(422);

// ===== SEZIONE 2: LOGICA DI PROCESSO =====
    echo 'id_busta non valido';
    exit;
}

try {
    $stmt = $pdo->prepare(
        'SELECT bp.*, u.Email, ic.Livello_dipendente
         FROM Busta_paga bp
         LEFT JOIN Utenti u ON u.ID_utente = bp.ID_utente
         LEFT JOIN Impostazioni_contratto ic ON ic.ID_utente = bp.ID_utente
         WHERE bp.ID_busta = ? AND (bp.ID_utente = ? OR bp.ID_utente IS NULL)
         LIMIT 1'
    );
    $stmt->execute([$idBusta, $userId]);
    $busta = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    http_response_code(500);
    echo 'Errore database';
    exit;
}

// ===== SEZIONE 3: LOGICA DI PROCESSO =====

if (!$busta) {
    http_response_code(404);
    echo 'Busta paga non trovata';
    exit;
}


/**
 * Function: toPdfText
 * Parameters: string $text
 * Return: mixed
 * Description: Executes business logic for toPdfText.
 */
function toPdfText(string $text): string
{
    $converted = @iconv('UTF-8', 'windows-1252//TRANSLIT', $text);
    return $converted !== false ? $converted : $text;
}


/**
 * Function: eur
 * Parameters: float $v
 * Return: mixed
 * Description: Executes business logic for eur.
 */
function eur(float $v): string
{
    return number_format($v, 2, ',', '.') . ' EUR';
}

$lordo = (float)($busta['Stipendio_lordo'] ?? 0);
$netto = (float)($busta['Stipendio_netto'] ?? 0);

// ===== SEZIONE 4: LOGICA DI PROCESSO =====

$inps = round($lordo * 0.0919, 2);
$irpef = round($lordo * 0.2090, 2);
$addRegionale = round($lordo * 0.0160, 2);
$addComunale = round($lordo * 0.0070, 2);
$totTrattenute = round($inps + $irpef + $addRegionale + $addComunale, 2);

$pagaOraria = (float)($busta['Paga_oraria'] ?? 0);
$oreLavorate = (float)($busta['Ore_lavorate'] ?? 0);
$oreFerie = (float)($busta['Ore_ferie'] ?? 0);
$oreMalattia = (float)($busta['Ore_malattia'] ?? 0);
$oreStra = (float)($busta['Ore_straordinari'] ?? 0);
$oreFestivi = (float)($busta['Ore_festivi'] ?? 0);
$orePrefestivi = (float)($busta['Ore_prefestivi'] ?? 0);
$oreNotturne = (float)($busta['Ore_notturne'] ?? 0);
$oreReperibilita = (float)($busta['Ore_reperibilita'] ?? 0);
$oreTrasferta = (float)($busta['Ore_trasferta'] ?? 0);

$oreTotali = $oreLavorate + $oreFerie + $oreMalattia + $oreStra + $oreFestivi + $orePrefestivi + $oreNotturne + $oreReperibilita + $oreTrasferta;
$ferieMaturate = round($oreTotali * 0.083, 2);

// ===== SEZIONE 5: LOGICA DI PROCESSO =====

$stipendioBase = round(($oreLavorate + $oreFerie + $oreMalattia) * $pagaOraria, 2);
$periodo = (string)($busta['Mese_riferimento'] ?? '');
$livello = trim((string)($busta['Livello_dipendente'] ?? ''));
$nome = (string)($busta['Email'] ?? ($_SESSION['email'] ?? ('Utente #' . $userId)));
$dataDocumento = date('d/m/Y');

$caratteristiche = [
    'Paga oraria' => $pagaOraria > 0 ? eur($pagaOraria) : null,
    'Ore lavorate' => $oreLavorate > 0 ? number_format($oreLavorate, 2, ',', '.') . ' h' : null,
    'Ore ferie' => $oreFerie > 0 ? number_format($oreFerie, 2, ',', '.') . ' h' : null,
    'Ore malattia' => $oreMalattia > 0 ? number_format($oreMalattia, 2, ',', '.') . ' h' : null,
    'Ore straordinari' => $oreStra > 0 ? number_format($oreStra, 2, ',', '.') . ' h' : null,
    'Ore festivi' => $oreFestivi > 0 ? number_format($oreFestivi, 2, ',', '.') . ' h' : null,
    'Ore prefestivi' => $orePrefestivi > 0 ? number_format($orePrefestivi, 2, ',', '.') . ' h' : null,
    'Ore notturne' => $oreNotturne > 0 ? number_format($oreNotturne, 2, ',', '.') . ' h' : null,
    'Ore reperibilita' => $oreReperibilita > 0 ? number_format($oreReperibilita, 2, ',', '.') . ' h' : null,
    'Ore trasferta' => $oreTrasferta > 0 ? number_format($oreTrasferta, 2, ',', '.') . ' h' : null,
];


// ===== SEZIONE 6: LOGICA DI PROCESSO =====
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 20);

$pdf->SetFont('Arial', 'B', 18);
$pdf->Cell(0, 10, toPdfText('BPIC Cedolino Paga'), 0, 1, 'L');
$pdf->Ln(2);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, toPdfText('Informazioni Dipendente'), 0, 1, 'L');
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 7, toPdfText('Nome: ' . $nome), 0, 1, 'L');
$pdf->Cell(0, 7, toPdfText('Periodo: ' . $periodo), 0, 1, 'L');
$pdf->Cell(0, 7, toPdfText('Livello: ' . ($livello !== '' ? $livello : 'N/D')), 0, 1, 'L');
$pdf->Ln(2);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, toPdfText('Voci Retributive'), 0, 1, 'L');
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(130, 7, toPdfText('Stipendio Base'), 0, 0, 'L');

// ===== SEZIONE 7: LOGICA DI PROCESSO =====
$pdf->Cell(0, 7, toPdfText(eur($stipendioBase)), 0, 1, 'R');
$pdf->Cell(130, 7, toPdfText('TOTALE LORDO'), 0, 0, 'L');
$pdf->Cell(0, 7, toPdfText(eur($lordo)), 0, 1, 'R');
$pdf->Ln(2);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, toPdfText('Trattenute'), 0, 1, 'L');
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(130, 7, toPdfText('INPS (Contributi Previdenziali)'), 0, 0, 'L');
$pdf->Cell(0, 7, toPdfText('-' . eur($inps)), 0, 1, 'R');
$pdf->Cell(130, 7, toPdfText('IRPEF (Imposta sul Reddito)'), 0, 0, 'L');
$pdf->Cell(0, 7, toPdfText('-' . eur($irpef)), 0, 1, 'R');
$pdf->Cell(130, 7, toPdfText('Addizionale Regionale'), 0, 0, 'L');
$pdf->Cell(0, 7, toPdfText('-' . eur($addRegionale)), 0, 1, 'R');
$pdf->Cell(130, 7, toPdfText('Addizionale Comunale'), 0, 0, 'L');
$pdf->Cell(0, 7, toPdfText('-' . eur($addComunale)), 0, 1, 'R');
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(130, 7, toPdfText('TOTALE TRATTENUTE'), 0, 0, 'L');
$pdf->Cell(0, 7, toPdfText('-' . eur($totTrattenute)), 0, 1, 'R');
$pdf->Cell(130, 8, toPdfText('STIPENDIO NETTO'), 0, 0, 'L');

// ===== SEZIONE 8: LOGICA DI PROCESSO =====
$pdf->Cell(0, 8, toPdfText(eur($netto)), 0, 1, 'R');
$pdf->Ln(2);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, toPdfText('Dettaglio voci valorizzate'), 0, 1, 'L');
$pdf->SetFont('Arial', '', 11);
foreach ($caratteristiche as $label => $val) {
    if ($val === null) {
        continue;
    }
    $pdf->Cell(95, 7, toPdfText($label), 0, 0, 'L');
    $pdf->Cell(0, 7, toPdfText($val), 0, 1, 'R');
}

$pdf->Ln(1);
$pdf->Cell(0, 7, toPdfText('Ferie maturate nel mese: ' . number_format($ferieMaturate, 2, ',', '.') . ' ore'), 0, 1, 'L');
$pdf->Ln(2);
$pdf->SetFont('Arial', 'I', 9);
$pdf->MultiCell(0, 6, toPdfText('Documento generato il ' . $dataDocumento . ' da BPIC.'));


// ===== SEZIONE 9: LOGICA DI PROCESSO =====
$fileName = 'cedolino_bpic_' . $idBusta . '_' . preg_replace('/[^0-9\-]/', '', $periodo) . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: private, max-age=0, must-revalidate');

$pdf->Output('I', $fileName);

/* ===== TODO: CASO D'USO NON IMPLEMENTATO =====
 * Use Case: "Invio PDF via email"
 * Description: Invio della busta paga in formato PDF al dipendente tramite email
 * Implementation Note: Aggiungere integrazione con libreria PHPMailer o mail() nativa
 * Required: Email template, SMTP configuration, retry logic, bounce handling
 * Expected Flow: 
 *   1. Dopo generazione PDF, salvare file temporaneamente
 *   2. Preparare messaggio email con allegato PDF
 *   3. Inviare tramite SMTP a $userEmail
 *   4. Registrare invio in database (Storico_PDF_inviati o tabella equivalente)
 * Status: PENDING
 */
exit;