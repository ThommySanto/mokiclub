<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/auth_check.php";

$type = $_GET['type'] ?? '';

if ($type !== 'iscritti' && $type !== 'rimessaggi') {
    die("Tipo di esportazione non valido.");
}

$filename = "export_" . $type . "_" . date('Y-m-d') . ".csv";

// Impostazioni header per il download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Apertura output stream
$output = fopen('php://output', 'w');

// BOM per UTF-8 (per far leggere bene gli accenti a Excel)
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Query dinamica
$table = ($type === 'iscritti') ? 'iscritti' : 'rimessaggi';
$query = "SELECT * FROM $table ORDER BY id_" . ($type === 'iscritti' ? 'modulo' : '') . " DESC";

if ($type === 'rimessaggi') {
    $query = "SELECT * FROM rimessaggi ORDER BY id DESC";
}

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    // Intestazioni colonne
    $firstRow = $result->fetch_assoc();
    fputcsv($output, array_keys($firstRow));

    // Reset puntatore e inserimento dati
    $result->data_seek(0);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
}

fclose($output);
exit;
?>