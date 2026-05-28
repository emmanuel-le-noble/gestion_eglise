<?php
// eglise_db/tresorerie/rapport_caisse.php
require_once "../config/database.php";
require_once "../includes/session.php";

// Sécurité : Rôles autorisés à éditer les rapports
securiser_par_module($pdo, 'tresorerie');

// Récupération et sécurisation des dates de la période
$date_debut = $_GET['date_debut'] ?? date('Y-m-01'); // Par défaut : 1er du mois en cours
$date_fin = $_GET['date_fin'] ?? date('Y-m-d');      // Par défaut : Aujourd'hui

// 1. Calcul des totaux de la période (Recettes, Dépenses, Solde)
$stats_query = "SELECT 
    SUM(CASE WHEN type_mouvement = 'ENTREE' THEN montant ELSE 0 END) as total_entrees,
    SUM(CASE WHEN type_mouvement = 'SORTIE' THEN montant ELSE 0 END) as total_sorties
    FROM tresorerie WHERE date_operation BETWEEN ? AND ?";
    
$stmt_stats = $pdo->prepare($stats_query);
$stmt_stats->execute([$date_debut, $date_fin]);
$stats = $stmt_stats->fetch();

$total_recettes = (float)($stats['total_entrees'] ?? 0);
$total_depenses = (float)($stats['total_sorties'] ?? 0);
$solde_periode = $total_recettes - $total_depenses;

// 2. Récupération de l'historique complet des flux sur cette période
$sql = "SELECT t.*, m.nom, m.prenoms, l.libelle as budget_ligne_nom 
        FROM tresorerie t 
        LEFT JOIN membres m ON t.membre_id = m.id 
        LEFT JOIN lignes_budget l ON t.ligne_budget_id = l.id
        WHERE t.date_operation BETWEEN ? AND ?
        ORDER BY t.date_operation ASC, t.id ASC"; // Ordre chronologique pour le rapport

$stmt_list = $pdo->prepare($sql);
$stmt_list->execute([$date_debut, $date_fin]);
$transactions = $stmt_list->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport_Caisse_<?= date('d_m_Y', strtotime($date_debut)) ?>_au_<?= date('d_m_Y', strtotime($date_fin)) ?></title>
    <!-- Bootstrap 5.3.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

        body { 
            background: #f0f0f0; 
            font-family: 'poppins', sans-serif; 
            font-size: 14px; 
        }
        
        /* Conteneur Format A4 rigide (Identique à ta Fiche Membre) */
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
            border-bottom: 3px solid #333; 
            margin-bottom: 20px; 
            padding-bottom: 10px; 
        }

        .section-title { 
            background: #f8f9fa; 
            border-left: 5px solid #198754; /* Liseré vert pour la caisse */
            padding: 5px 12px; 
            font-weight: bold; 
            margin-top: 20px;
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
            background: #fafafa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
        }

        .info-label { font-weight: 550; color: #555; }
        .info-value { padding: 5px; font-weight: 600; }
        
        .table-compta th {
            text-transform: uppercase;
            font-size: 13px;
            background-color: #f8f9fa !important;
            letter-spacing: 0.5px;
        }

        @media print {
            body { background: white; margin: 0; }
            .fiche-a4 { margin: 0; box-shadow: none; width: 100%; padding: 10mm; }
            .no-print { display: none; }
            .section-title { -webkit-print-color-adjust: exact; background-color: #f8f9fa !important; }
            .table-compta th { -webkit-print-color-adjust: exact; background-color: #f8f9fa !important; }
        }
    </style>
</head>
<body>

<div class="container text-center mt-3 no-print">
    <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm">
        <div class="d-flex gap-5">
            <a href="index.php" class="btn btn-outline-secondary p-2 btn-sm"><i class="fa fa-arrow-left me-1"></i> Retour journal</a>
            <button onclick="window.print()" class="btn btn-dark p-2 btn-sm"><i class="fa fa-print me-1"></i> Imprimer le Rapport A4</button>
        </div>
        
        <form method="GET" class="d-flex align-items-center gap-2 m-0">
            <label class="small fw-bold text-muted mb-0">Du:</label>
            <input type="date" name="date_debut" class="form-control p-2 w-auto" value="<?= $date_debut ?>">
            <label class="small fw-bold text-muted mb-0">Au:</label>
            <input type="date" name="date_fin" class="form-control p-2 w-auto" value="<?= $date_fin ?>">
            <button type="submit" class="btn btn-outline-primary p-2 btn-sm"><i class="fa fa-filter"></i></button>
        </form>
    </div>
</div>

<div class="fiche-a4">
    
    <div class="header-fiche text-center">
        <h1 class="fs-1 mb-0" style="font-weight: bold;">RAPPORT DE CAISSE</h1>
        <p class="fs-5 mb-1 text-muted text-uppercase">Bilan des mouvements réels de trésorerie</p>
    </div>

    <div class="row mb-3">
        <div class="col-7">
            <span class="info-label">Période du rapport :</span> 
            <span class="info-value">du <?= date('d/m/Y', strtotime($date_debut)) ?> au <?= date('d/m/Y', strtotime($date_fin)) ?></span>
        </div>
        <div class="col-5 text-end">
            <span class="info-label">Généré par :</span> 
            <span class="info-value text-uppercase"><?= htmlspecialchars($_SESSION['user_nom'] ?? 'Trésorier') ?></span>
        </div>
    </div>

    <div class="section-title">1. Synthèse Financière de la Période</div>
    <div class="bloc-synthese mb-4">
        <div class="row text-center">
            <div class="col-4 border-end">
                <span class="info-label text-uppercase small text-muted">Total encaissé (Recettes)</span>
                <h4 class="fw-bold text-success mt-1"><?= number_format($total_recettes, 0, ',', ' ') ?> F</h4>
            </div>
            <div class="col-4 border-end">
                <span class="info-label text-uppercase small text-muted">Total décaissé (Dépenses)</span>
                <h4 class="fw-bold text-danger mt-1"><?= number_format($total_depenses, 0, ',', ' ') ?> F</h4>
            </div>
            <div class="col-4">
                <span class="info-label text-uppercase small text-muted">Flux net de la période</span>
                <h4 class="fw-bold <?= $solde_periode >= 0 ? 'text-success' : 'text-danger' ?> mt-1">
                    <?= $solde_periode >= 0 ? '+' : '' ?> <?= number_format($solde_periode, 0, ',', ' ') ?> F
                </h4>
            </div>
        </div>
    </div>

    <div class="section-title">2. Registre des recettes et dépenses</div>
    <table class="table table-bordered table-sm align-middle table-compta mb-0">
        <thead class="table-light">
            <tr class="text-center">
                <th style="width: 14%;">Date</th>
                <th style="width: 14%;">Mouvement</th>
                <th style="width: 54%;" class="text-start">Rubrique / Nature du flux & Justificatif</th>
                <th style="width: 18%;" class="text-end">Montant</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($transactions)): ?>
                <tr>
                    <td colspan="4" class="text-center text-muted py-4 font-italic">
                        Aucun mouvement de caisse enregistré entre ces deux dates.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach($transactions as $t): ?>
                <tr>
                    <td class="text-center font-monospace small"><?= date('d/m/Y', strtotime($t['date_operation'])) ?></td>
                    <td class="text-center fw-bold small" style="font-size: 11px;">
                        <?= $t['type_mouvement'] == 'ENTREE' ? '<span class="text-success">RECETTE</span>' : '<span class="text-danger">DÉPENSE</span>' ?>
                    </td>
                    <td class="ps-2">
                        <div class="fw-bold text-dark mb-0" style="font-size: 13px;">
                            <?= htmlspecialchars($t['budget_ligne_nom'] ?? $t['categorie']) ?>
                        </div>
                        <small class="text-muted">
                            <?php if(!empty($t['nom'])): ?>
                                Fidèle : <?= htmlspecialchars($t['nom'] . ' ' . $t['prenoms']) ?>
                            <?php else: ?>
                                <?= htmlspecialchars($t['libelle'] ?? '') ?>
                            <?php endif; ?>
                        </small>
                    </td>
                    <td class="text-end pe-2 fw-bold font-monospace <?= $t['type_mouvement'] == 'ENTREE' ? 'text-success' : 'text-danger' ?>">
                        <?= $t['type_mouvement'] == 'ENTREE' ? '+' : '-' ?> <?= number_format($t['montant'], 0, ',', ' ') ?> F
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="row mt-5 pt-3">
        <div class="col-6 text-center">
            <p class="mb-4 fw-bold text-decoration-underline">Établi par le trésorier</p>
            <div style="height: 40px;"></div>
            <small class="text-muted font-monospace">(Signature)</small>
        </div>
        <div class="col-6 text-center">
            <p class="mb-4 fw-bold text-decoration-underline">Certifié conforme (Pasteur / Conseil)</p>
            <div style="height: 40px;"></div>
            <small class="text-muted font-monospace">(Date, cachet et signature)</small>
        </div>
    </div>

</div>

</body>
</html>