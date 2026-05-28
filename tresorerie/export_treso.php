<?php
require_once "../config/database.php";

$date_debut = $_GET['date_debut'] ?? date('Y-m-01');
$date_fin = $_GET['date_fin'] ?? date('Y-m-d');

$filename = "Rapport_Tresorerie_".$date_debut."_au_".$date_fin.".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

echo "\xEF\xBB\xBF"; // UTF-8 BOM

$sql = "SELECT t.*, m.nom, m.prenoms  FROM tresorerie t  LEFT JOIN membres m ON t.membre_id = m.id  WHERE t.date_operation BETWEEN ? AND ? ORDER BY date_operation ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$date_debut, $date_fin]);
$data = $stmt->fetchAll();

echo '<table border="1">
    <tr style="background:#004085; color:white;">
        <th>Date</th><th>Categorie</th><th>Libellé / Membre</th><th>Entrée</th><th>Sortie</th>
    </tr>';

foreach($data as $row) {
    $entree = ($row['type_mouvement'] == 'ENTREE') ? $row['montant'] : 0;
    $sortie = ($row['type_mouvement'] == 'SORTIE') ? $row['montant'] : 0;
    $label = !empty($row['nom']) ? $row['nom'].' '.$row['prenoms'] : $row['libelle'];
    
    echo "<tr>
        <td>{$row['date_operation']}</td>
        <td>{$row['categorie']}</td>
        <td>".htmlspecialchars($label)."</td>
        <td>$entree</td>
        <td>$sortie</td>
    </tr>";
}
echo '</table>';