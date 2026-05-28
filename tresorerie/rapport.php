<?php
// eglise_db/tresorerie/rapport.php
require_once "../config/database.php";
require_once "../includes/session.php";

// Sécurité : Rôles autorisés
securiser_par_module($pdo, 'tresorerie');

$annee_selectionnee = $_GET['annee'] ?? date('Y');

// 1. Récupération du budget global de l'année sélectionnée
$stmt_b = $pdo->prepare("SELECT * FROM budgets WHERE annee = ?");
$stmt_b->execute([$annee_selectionnee]);
$budget_global = $stmt_b->fetch();

$lignes_rapport = [];
$total_prevu_recettes = 0;
$total_reel_recettes = 0;
$total_prevu_depenses = 0;
$total_reel_depenses = 0;

if ($budget_global) {
    // 2. Requête puissante : On récupère les lignes budgétaires ET on somme les transactions réelles associées
    $sql = "SELECT 
                l.id, 
                l.libelle, 
                l.type_ligne, 
                l.montant_prevu,
                IFNULL(SUM(t.montant), 0) AS montant_reel
            FROM lignes_budget l
            LEFT JOIN tresorerie t ON l.id = t.ligne_budget_id
            WHERE l.budget_id = ?
            GROUP BY l.id
            ORDER BY l.type_ligne ASC, l.libelle ASC";
            
    $stmt_r = $pdo->prepare($sql);
    $stmt_r->execute([$budget_global['id']]);
    $lignes_rapport = $stmt_r->fetchAll();

    // Calcul des totaux généraux pour la synthèse
    foreach ($lignes_rapport as $l) {
        if ($l['type_ligne'] === 'ENTREE') {
            $total_prevu_recettes += (float)$l['montant_prevu'];
            $total_reel_recettes += (float)$l['montant_reel'];
        } else {
            $total_prevu_depenses += (float)$l['montant_prevu'];
            $total_reel_depenses += (float)$l['montant_reel'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport_Comparatif_Budget_<?= $annee_selectionnee ?></title>
    <!-- Bootstrap 5.3.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

        body { 
            background: #f0f0f0; 
            font-family: 'poppins', sans-serif; 
            font-size: 13px; 
        }
        
        /* Structure de ta Fiche A4 officielle */
        .fiche-a4 {
            width: 210mm;
            min-height: 297mm;
            padding: 15mm 20mm;
            margin: 10mm auto;
            background: white;
            box-shadow: 0 0 15px rgba(0,0,0,0.2);
            position: relative;
            box-sizing: border-box;
        }

        .header-fiche { 
            border-bottom: 2px solid #333; 
            margin-bottom: 10px; 
            padding-bottom: 5px; 
        }

        .section-title { 
            background: #f8f9fa; 
            border-left: 5px solid #0d6efd;
            padding: 5px 12px; 
            font-weight: bold; 
            margin-top: 25px;
            margin-bottom: 15px;
            text-transform: uppercase;
            font-size: 13px;
        }
        table{
            border-collapse: collapse;
            width: 100%;
        }

        tr, td {
            border: solid 1px black;
        }

        .bloc-synthese {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
        }

        .info-label { font-weight: 550; color: #555; }
        .info-value { padding: 5px; font-weight: 600; }
        
        .table-rapport th {
            text-transform: uppercase;
            font-size: 12px;
            background-color: #f8f9fa !important;
            vertical-align: middle;
        }

        @media print {
            body { background: white; margin: 0; }
            .fiche-a4 { margin: 0; box-shadow: none; width: 100%; padding: 10mm; }
            .no-print { display: none; }
            .section-title { -webkit-print-color-adjust: exact; background-color: #f8f9fa !important; }
            .table-rapport th { -webkit-print-color-adjust: exact; background-color: #f8f9fa !important; }
        }
    </style>
</head>
<body>

<div class="container text-center w-100 no-print">
    <div class="d-flex justify-content-center align-items-center gap-5">
        <a href="index.php" class="btn btn-primary"><i class="fa-solid fa-book me-2"></i>Retour à la trésorerie</a>
        <a href="budget.php" class="btn btn-light border"><i class="fa-solid fa-chart-pie me-2"></i>Retour au plan budgétaire</a>
        <button onclick="window.print()" class="btn btn-dark shadow"><i class="fa fa-print me-2"></i>Imprimer le rapport A4</button>
        
        <form method="GET" class="d-flex align-items-center bg-white p-2 rounded border m-0 shadow-sm">
            <label class="me-2 small fw-bold text-muted mb-0">Année :</label>
            <select name="annee" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <?php for($y = date('Y')-2; $y <= date('Y')+1; $y++): ?>
                    <option value="<?= $y ?>" <?= $annee_selectionnee == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </form>
    </div>
</div>

<div class="fiche-a4">
    
    <div class="header-fiche text-center">
        <h1 class="fs-2 mb-0" style="font-weight:bold;">RAPPORT D'ACTIVITÉS BUDGETAIRE</h1>
        <p class="fs-5 mb-1">SUIVI COMPARATIF : PRÉVISIONS VS RÉALISATIONS</p>
    </div>

    <div class="row mb-3">
        <div class="col-6">
            <span class="info-label">Exercice Budgétaire :</span> 
            <span class="info-value text-primary"><?= $annee_selectionnee ?></span>
        </div>
        <div class="col-6 text-end">
            <span class="info-label">Date d'édition :</span> 
            <span class="info-value"><?= date('d/m/Y') ?></span>
        </div>
    </div>

    <?php if (!$budget_global): ?>
        <div class="alert alert-warning text-center my-5">
            <i class="fa-solid fa-circle-exclamation me-2"></i> Aucun budget prévisionnel n'a été initialisé pour l'année <?= $annee_selectionnee ?>.
        </div>
    <?php else: ?>

        <div class="section-title">1. Synthèse globale de l'exercice</div>
        <div class="bloc-synthese mb-4">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0 text-center">
                    <thead>
                        <tr class="font-weight-bold text-muted small">
                            <th>NATURE</th>
                            <th>TOTAL PRÉVU</th>
                            <th>TOTAL RÉALISÉ</th>
                            <th>ÉCART GLOBAL</th>
                            <th>TAUX (TA)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-start fw-bold text-success">RECETTES</td>
                            <td class="fw-bold"><?= number_format($total_prevu_recettes, 0, ',', ' ') ?> F</td>
                            <td class="fw-bold text-success"><?= number_format($total_reel_recettes, 0, ',', ' ') ?> F</td>
                            <?php $ecart_recettes = $total_reel_recettes - $total_prevu_recettes; ?>
                            <td class="font-monospace fw-bold <?= $ecart_recettes >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= $ecart_recettes >= 0 ? '+' : '' ?> <?= number_format($ecart_recettes, 0, ',', ' ') ?> F
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= $total_prevu_recettes > 0 ? round(($total_reel_recettes / $total_prevu_recettes) * 100, 1) : 0 ?> %
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-start fw-bold text-danger">DÉPENSES</td>
                            <td class="fw-bold"><?= number_format($total_prevu_depenses, 0, ',', ' ') ?> F</td>
                            <td class="fw-bold text-danger"><?= number_format($total_reel_depenses, 0, ',', ' ') ?> F</td>
                            <?php $ecart_depenses = $total_prevu_depenses - $total_reel_depenses; // Écart positif = économie ?>
                            <td class="font-monospace fw-bold <?= $ecart_depenses >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= $ecart_depenses >= 0 ? 'Éco: +' : 'Dépassement: ' ?> <?= number_format($ecart_depenses, 0, ',', ' ') ?> F
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= $total_prevu_depenses > 0 ? round(($total_reel_depenses / $total_prevu_depenses) * 100, 1) : 0 ?> %
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section-title">2. Analyse détaillée par ligne budgétaire</div>
        <table class="table table-bordered table-sm align-middle table-rapport">
            <thead class="table-light">
                <tr class="text-center">
                    <th style="width: 12%;">Type</th>
                    <th style="width: 38%;" class="text-start">Intitulé de la rubrique</th>
                    <th style="width: 16%;" class="text-end">Objectif prévu</th>
                    <th style="width: 16%;" class="text-end">Réalisé caisse</th>
                    <th style="width: 18%;" class="text-end">Écart / Reste</th>
                    <th style="width: 8%;" class="text-center">%</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lignes_rapport)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">Aucune ligne budgétaire définie pour cette année.</td></tr>
                <?php else: ?>
                    <?php foreach($lignes_rapport as $row): 
                        $prevu = (float)$row['montant_prevu'];
                        $reel = (float)$row['montant_reel'];
                        
                        if ($row['type_ligne'] === 'ENTREE') {
                            $ecart = $reel - $prevu; // Pour les entrées, positif = bonne nouvelle
                        } else {
                            $ecart = $prevu - $reel; // Pour les sorties, positif = sous le budget (économie)
                        }
                        
                        $taux = $prevu > 0 ? round(($reel / $prevu) * 100, 0) : 0;
                    ?>
                        <tr>
                            <td class="text-center small fw-bold">
                                <?= $row['type_ligne'] === 'ENTREE' ? '<span class="text-success">Recette</span>' : '<span class="text-danger">Dépense</span>' ?>
                            </td>
                            <td class="fw-semibold text-dark"><?= htmlspecialchars($row['libelle']) ?></td>
                            <td class="text-end font-monospace"><?= number_format($prevu, 0, ',', ' ') ?> F</td>
                            <td class="text-end font-monospace fw-bold <?= $row['type_ligne'] === 'ENTREE' ? 'text-success' : 'text-danger' ?>">
                                <?= number_format($reel, 0, ',', ' ') ?> F
                            </td>
                            <td class="text-end font-monospace small fw-bold <?= $ecart >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= $ecart >= 0 ? '+' : '' ?><?= number_format($ecart, 0, ',', ' ') ?> F
                            </td>
                            <td class="text-center font-monospace small bg-light fw-bold"><?= $taux ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="row mt-2 pt-3">
            <div class="col-6 text-center">
                <p class="mb-4 fw-bold text-decoration-underline">Le Conseil Financier</p>
                <div style="height: 40px;"></div>
                <small class="text-muted font-monospace">(Visas)</small>
            </div>
            <div class="col-6 text-center">
                <p class="mb-4 fw-bold text-decoration-underline">Le Secrétaire Général</p>
                <div style="height: 40px;"></div>
                <small class="text-muted font-monospace">(Date, Cachet et Signature)</small>
            </div>
        </div>

    <?php endif; ?>
</div>

</body>
</html>