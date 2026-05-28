<?php
// eglise_db/mutuelle/export_membres.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'mutuelle');


// Nom du fichier avec horodatage
$filename = "Registre_Membres_Mutuelle_" . date('d-m-Y') . ".xls";

// Déclaration des headers pour forcer le téléchargement Excel en UTF-8
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Extraction des données de la mutuelle
$sql = "SELECT mc.*, m.nom, m.prenoms, m.matricule 
        FROM mutuelle_comptes mc 
        JOIN membres m ON mc.membre_id = m.id 
        ORDER BY m.nom ASC";
$adherents = $pdo->query($sql)->fetchAll();

// Structure du tableau avec styles Excel intégrés
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style>
    .title { font-family: 'Segoe UI', sans-serif; font-size: 16px; font-weight: bold; background-color: #2c3e50; color: #ffffff; text-align: center; }
    .header { font-family: 'Segoe UI', sans-serif; font-size: 11px; font-weight: bold; background-color: #2980b9; color: #ffffff; text-align: center; border: 0.5pt solid #bdc3c7; }
    .data { font-family: 'Segoe UI', sans-serif; font-size: 11px; border: 0.5pt solid #bdc3c7; }
    .number { mso-number-format:"\#\,\#\#0"; text-align: right; }
    .center { text-align: center; }
    .total { font-family: 'Segoe UI', sans-serif; font-size: 11px; font-weight: bold; background-color: #eaeded; border-top: 1pt solid #000000; border-bottom: 2pt double #000000; }
</style>

<table>
    <tr>
        <th colspan="6" class="title" height="40">REGISTRE DES ADHÉRENTS DE LA MUTUELLE</th>
    </tr>
    <tr>
        <td colspan="3" style="font-family: sans-serif; font-size: 10px;"><b>Généré le :</b> <?= date('d/m/Y à H:i') ?></td>
        <td colspan="3" style="font-family: sans-serif; font-size: 10px; text-align: right;"><b>Statut :</b> Officiel / Certifié</td>
    </tr>
    <tr><td colspan="6"></td></tr>
    <thead>
        <tr>
            <th class="header" width="120">Matricule</th>
            <th class="header" width="300">Nom & Prénoms</th>
            <th class="header" width="120">Date Adhésion</th>
            <th class="header" width="150">Épargne Tontine</th>
            <th class="header" width="150">Fonds Social</th>
            <th class="header" width="100">Statut</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $total_tontine = 0;
        $total_social = 0;
        foreach($adherents as $a): 
            $total_tontine += $a['solde_tontine'];
            $total_social += $a['solde_social'];
        ?>
        <tr>
            <td class="data center"><?= htmlspecialchars($a['matricule']) ?></td>
            <td class="data" style="text-transform: uppercase;"><?= htmlspecialchars($a['nom'] . ' ' . $a['prenoms']) ?></td>
            <td class="data center"><?= date('d/m/Y', strtotime($a['date_adhesion'])) ?></td>
            <td class="data number"><?= (int)$a['solde_tontine'] ?></td>
            <td class="data number"><?= (int)$a['solde_social'] ?></td>
            <td class="data center"><?= htmlspecialchars($a['statut']) ?></td>
        </tr>
        <?php endforeach; ?>
        
        <tr>
            <td colspan="3" class="total" style="text-align: right;"><b>TOTAL GÉNÉRAL :</b></td>
            <td class="total number"><?= $total_tontine ?></td>
            <td class="total number"><?= $total_social ?></td>
            <td class="total"></td>
        </tr>
    </tbody>
</table>