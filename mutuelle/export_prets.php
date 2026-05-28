<?php
// eglise_db/mutuelle/export_prets.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'mutuelle');


$filename = "Suivi_Recouvrement_Prets_" . date('d-m-Y') . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Extraction de la liste complète des prêts (Correction de la formule pour inclure la commission)
$sql = "SELECT p.*, m.nom, m.prenoms, 
        (p.montant_prete + p.commission) as total_du,
        ((p.montant_prete + p.commission) - p.montant_rembourse) as reste_a_payer 
        FROM mutuelle_prets p 
        JOIN mutuelle_comptes mc ON p.compte_id = mc.id 
        JOIN membres m ON mc.membre_id = m.id 
        ORDER BY CASE WHEN p.statut = 'RETARD' THEN 1 WHEN p.statut = 'EN_COURS' THEN 2 ELSE 3 END, p.date_echeance ASC";
$prets = $pdo->query($sql)->fetchAll();
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style>
    .title { font-family: 'poppins', sans-serif; font-size: 16px; font-weight: bold; background-color: #922b21; color: #ffffff; text-align: center; }
    .header { font-family: 'poppins', sans-serif; font-size: 11px; font-weight: bold; background-color: #c0392b; color: #ffffff; text-align: center; border: 0.5pt solid #bdc3c7; }
    .data { font-family: 'poppins', sans-serif; font-size: 11px; border: 0.5pt solid #bdc3c7; }
    .number { mso-number-format:"\#\,\#\#0"; text-align: right; }
    .center { text-align: center; }
    .retard { background-color: #fadbd8; color: #922b21; font-weight: bold; text-align: center; border: 0.5pt solid #bdc3c7; }
    .total { font-family: 'poppins', sans-serif; font-size: 11px; font-weight: bold; background-color: #eaeded; border-top: 1pt solid #000000; border-bottom: 2pt double #000000; }
</style>

<table>
    <tr>
        <th colspan="9" class="title" height="40">REGISTRE DE SUIVI ET RECOUVREMENT DES PRÊTS</th>
    </tr>
    <tr>
        <td colspan="5" style="font-family:'poppins', sans-serif; font-size: 10px;"><b>Audité le :</b> <?= date('d/m/Y à H:i') ?></td>
        <td colspan="4" style="font-family:'poppins', sans-serif; font-size: 10px; text-align: right;">Document interne de contrôle de caisse</td>
    </tr>
    <tr><td colspan="9"></td></tr>
    <thead>
        <tr>
            <th class="header" width="250">Bénéficiaire (Adhérent)</th>
            <th class="header" width="110">Date Prêt</th>
            <th class="header" width="110">Échéance</th>
            <th class="header" width="80">Taux (%)</th>
            <th class="header" width="120">Montant Prêté</th>
            <th class="header" width="100">Frais/Intérêts</th>
            <th class="header" width="140">Total Dû</th>
            <th class="header" width="140">Déjà Remboursé</th>
            <th class="header" width="140">Reste à Recouvrer</th>
            <th class="header" width="110">Statut</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $tot_prete = 0;
        $tot_commission = 0;
        $tot_du = 0;
        $tot_rembourse = 0;
        $tot_reste = 0;
        
        foreach ($prets as $p): 
            $tot_prete += $p['montant_prete'];
            $tot_commission += $p['commission'];
            $tot_du += $p['total_du'];
            $tot_rembourse += $p['montant_rembourse'];
            $tot_reste += $p['reste_a_payer'];
            $style_statut = ($p['statut'] == 'RETARD') ? 'retard' : 'data center';
        ?>
        <tr>
            <td class="data" style="text-transform: uppercase;"><?= htmlspecialchars($p['nom'] . ' ' . $p['prenoms']) ?></td>
            <td class="data center"><?= date('d/m/Y', strtotime($p['date_pret'])) ?></td>
            <td class="data center" style="<?= $p['statut'] == 'RETARD' ? 'color:#c0392b; font-weight:bold;' : '' ?>"><?= date('d/m/Y', strtotime($p['date_echeance'])) ?></td>
            <td class="data center" mso-number-format="0\.00"><?= (float)$p['taux'] ?></td>
            <td class="data number"><?= (int)$p['montant_prete'] ?></td>
            <td class="data number" style="color: #7f8c8d;"><?= (int)$p['commission'] ?></td>
            <td class="data number" style="font-weight:semibold;"><?= (int)$p['total_du'] ?></td>
            <td class="data number" style="color:#27ae60;"><?= (int)$p['montant_rembourse'] ?></td>
            <td class="data number" style="color:#2980b9; font-weight:bold;"><?= (int)$p['reste_a_payer'] ?></td>
            <td class="<?= $style_statut ?>"><?= htmlspecialchars($p['statut']) ?></td>
        </tr>
        <?php endforeach; ?>
        
        <tr>
            <td colspan="4" class="total" style="text-align: right;"><b>PORTEFEUILLE TOTAL :</b></td>
            <td class="total number"><?= $tot_prete ?></td>
            <td class="total number" style="color: #7f8c8d;"><?= $tot_commission ?></td>
            <td class="total number"><?= $tot_du ?></td>
            <td class="total number" style="color:#27ae60;"><?= $tot_rembourse ?></td>
            <td class="total number" style="color:#2980b9;"><?= $tot_reste ?></td>
            <td class="total"></td>
        </tr>
    </tbody>
</table>