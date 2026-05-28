<?php
require_once "../config/database.php";
securiser_par_module($pdo, 'communication');

$mois = $_GET['mois'] ?? date('m');
$annee = $_GET['annee'] ?? date('Y');

$filename = "Rapport_Activites_".$mois."_".$annee.".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

echo "\xEF\xBB\xBF"; // UTF-8 BOM pour les accents

$sql = "SELECT * FROM evenements WHERE MONTH(date_evenement) = ? AND YEAR(date_evenement) = ? ORDER BY date_evenement ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$mois, $annee]);
$data = $stmt->fetchAll();

echo '<table border="1">
    <tr>
        <th colspan="4" style="background:#0d6efd; color:white; font-size:16px;">RAPPORT D\'ACTIVITÉS - ' . $mois . '/' . $annee . '</th>
    </tr>
    <tr style="background:#f8f9fa;">
        <th>Date</th>
        <th>Titre</th>
        <th>Type</th>
        <th>Lieu</th>
    </tr>';

foreach($data as $row) {
    echo "<tr>
        <td>" . date('d/m/Y', strtotime($row['date_evenement'])) . "</td>
        <td>" . htmlspecialchars($row['titre']) . "</td>
        <td>" . $row['type_evenement'] . "</td>
        <td>" . htmlspecialchars($row['lieu']) . "</td>
    </tr>";
}
echo '</table>';