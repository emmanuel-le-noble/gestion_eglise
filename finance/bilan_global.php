<?php
// eglise_db/rapports/bilan_global.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'finances');

// Filtres de période
$annee = $_GET['annee'] ?? date('Y');

// --- 1. STATISTIQUES TRÉSORERIE ÉGLISE ---
// Utilisation de UPPER() pour parer aux variations de saisie (Entree, ENTREE, etc.)
$sqlEglise = "SELECT 
    COALESCE(SUM(CASE WHEN UPPER(type_mouvement) = 'ENTREE' THEN montant ELSE 0 END), 0) as total_entrees,
    COALESCE(SUM(CASE WHEN UPPER(type_mouvement) = 'SORTIE' THEN montant ELSE 0 END), 0) as total_sorties
    FROM tresorerie WHERE YEAR(date_operation) = ?";
$stmtE = $pdo->prepare($sqlEglise);
$stmtE->execute([$annee]);
$eglise = $stmtE->fetch();

// --- 2. STATISTIQUES MUTUELLE ---
$sqlMutuelle = "SELECT 
    COALESCE(SUM(CASE WHEN type_operation = 'REMBOURSEMENT' THEN montant ELSE 0 END), 0) as total_rembourse,
    (SELECT COALESCE(SUM(commission), 0) FROM mutuelle_prets WHERE YEAR(date_pret) = ?) as total_commissions,
    (SELECT COALESCE(SUM(solde_tontine), 0) FROM mutuelle_comptes) as epargne_membres,
    (SELECT COALESCE(SUM(montant_prete - montant_rembourse), 0) FROM mutuelle_prets WHERE statut != 'SOLDE') as prets_dehors
    FROM mutuelle_operations WHERE YEAR(date_op) = ?";

$stmtM = $pdo->prepare($sqlMutuelle);
$stmtM->execute([$annee, $annee]);
$mutuelle = $stmtM->fetch();

// --- 3. CALCULS FINAUX ---
$solde_eglise = (float)$eglise['total_entrees'] - (float)$eglise['total_sorties'];
$total_commissions = (float)$mutuelle['total_commissions'];
$gain_total = $solde_eglise + $total_commissions;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilan_Global_<?= $annee ?></title>
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
        
        /* Structure de la Fiche A4 consolidée */
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
            border-left: 5px solid #212529; /* Noir neutre pour l'équilibre global */
            padding: 5px 12px; 
            font-weight: bold; 
            margin-top: 25px;
            margin-bottom: 15px;
            text-transform: uppercase;
            font-size: 13px;
        }

        .bloc-synthese {
            background: #fafafa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
        }

        .info-label { font-weight: 550; color: #555; }
        .info-value { padding: 5px; font-weight: 600; }

        @media print {
            body { background: white; margin: 0; }
            .fiche-a4 { margin: 0; box-shadow: none; width: 100%; padding: 10mm; }
            .no-print { display: none; }
            .section-title { -webkit-print-color-adjust: exact; background-color: #f8f9fa !important; }
            .bg-light { -webkit-print-color-adjust: exact; background-color: #f8f9fa !important; }
        }
    </style>
</head>
<body>

<div class="container text-center mt-3 no-print" style="max-width: 210mm;">
    <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm">
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-dark btn-sm"><i class="fa fa-print me-2"></i>Imprimer la synthèse annuelle</button>
            <a href="rapport_global.php" class="btn btn-outline-secondary btn-sm">Retour au bilan financier</a>
        </div>
        
        <form method="GET" class="d-flex align-items-center gap-2 m-0">
            <label class="small fw-bold text-muted mb-0">Exercice :</label>
            <select name="annee" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <?php for($y = date('Y'); $y >= 2024; $y--): ?>
                    <option value="<?= $y ?>" <?= $annee == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </form>
    </div>
</div>

<div class="fiche-a4">
    
    <div class="header-fiche text-center">
        <h1 class="fs-2 mb-0" style="font-weight: bold;">BILAN FINANCIER GLOBAL</h1>
        <p class="fs-5 mb-1 text-muted text-uppercase">Rapport consolidé de l'exercice <?= $annee ?></p>
    </div>

    <div class="row mb-3">
        <div class="col-7">
            <span class="info-label">Période comptable :</span> 
            <span class="info-value">Du 01/01/<?= $annee ?> au 31/12/<?= $annee ?></span>
        </div>
        <div class="col-5 text-end">
            <span class="info-label">Date d'extraction :</span> 
            <span class="info-value"><?= date('d/m/Y') ?></span>
        </div>
    </div>

    <div class="section-title">1. Situation des avoirs et liquidités réelles</div>
    <div class="row g-3">
        <div class="col-4">
            <div class="border p-3 text-center rounded bg-light">
                <span class="info-label small text-uppercase d-block text-muted mb-1">Caisse principale (Église)</span>
                <h4 class="fw-bold text-primary mb-0 font-monospace"><?= number_format($solde_eglise, 0, ',', ' ') ?> F</h4>
            </div>
        </div>
        <div class="col-4">
            <div class="border p-3 text-center rounded bg-light">
                <span class="info-label small text-uppercase d-block text-muted mb-1">Gains mutuelle (Commissions)</span>
                <h4 class="fw-bold text-success mb-0 font-monospace"><?= number_format($total_commissions, 0, ',', ' ') ?> F</h4>
            </div>
        </div>
        <div class="col-4">
            <div class="border p-3 text-center rounded bg-dark text-white">
                <span class="small text-uppercase d-block text-white-50 mb-1 fw-semibold">Avoirs réels église</span>
                <h4 class="fw-bold text-warning mb-0 font-monospace"><?= number_format($gain_total, 0, ',', ' ') ?> F</h4>
            </div>
        </div>
    </div>

    <div class="section-title">2. Flux de trésorerie courante (Église)</div>
    <div class="bloc-synthese">
        <table class="table table-sm table-borderless mb-0">
            <tbody>
                <tr class="border-bottom">
                    <td class="py-2 text-muted">Total des encaissements enregistrés (Recettes de l'année) :</td>
                    <td class="text-end fw-bold text-success font-monospace">+ <?= number_format($eglise['total_entrees'], 0, ',', ' ') ?> F</td>
                </tr>
                <tr class="border-bottom">
                    <td class="py-2 text-muted">Total des décaissements effectués (Dépenses et Charges) :</td>
                    <td class="text-end fw-bold text-danger font-monospace">- <?= number_format($eglise['total_sorties'], 0, ',', ' ') ?> F</td>
                </tr>
                <tr>
                    <td class="py-2 fw-bold text-dark">Solde net disponible en caisse de l'église :</td>
                    <td class="text-end fw-bold text-primary font-monospace fs-6"><?= number_format($solde_eglise, 0, ',', ' ') ?> F</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section-title">3. Fonds et capitaux sous gestion (Mutuelle)</div>
    <div class="bloc-synthese">
        <table class="table table-sm table-borderless mb-0">
            <tbody>
                <tr class="border-bottom">
                    <td class="py-2 text-muted">Épargne cumulée déposée par les membres (Dette envers les fidèles) :</td>
                    <td class="text-end fw-bold text-dark font-monospace"><?= number_format($mutuelle['epargne_membres'], 0, ',', ' ') ?> F</td>
                </tr>
                <tr class="border-bottom">
                    <td class="py-2 text-muted">Prêts en cours d'amortissement (Argent extérieur en circulation) :</td>
                    <td class="text-end fw-bold text-danger font-monospace"><?= number_format($mutuelle['prets_dehors'], 0, ',', ' ') ?> F</td>
                </tr>
                <tr>
                    <td class="py-2 text-muted">Intérêts et commissions retenus (Acquis définitivement à l'Église) :</td>
                    <td class="text-end fw-bold text-success font-monospace">+ <?= number_format($total_commissions, 0, ',', ' ') ?> F</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="alert alert-light border small mt-4 bg-light" style="line-height: 1.4;">
        <i class="fa-solid fa-scale-balanced me-2 text-info"></i>
        <strong>Principe d'étanchéité des fonds :</strong> Ce rapport consolidé certifie la séparation comptable stricte entre les liquidités de fonctionnement opérationnel de l'Église et l'épargne collective des adhérents de la mutuelle. Les fonds propres d'épargne des membres ne doivent en aucun cas être mobilisés pour subvenir aux charges de fonctionnement de la communauté.
    </div>

    <div class="row mt-3 pt-3">
        <div class="col-4 text-center">
            <p class="mb-4 fw-bold text-decoration-underline">Le Trésorier général</p>
            <div style="height: 40px;"></div>
            <small class="text-muted font-monospace">(Signature)</small>
        </div>
        <div class="col-4 text-center">
            <p class="mb-4 fw-bold text-decoration-underline">Le secrétariat</p>
            <div style="height: 40px;"></div>
            <small class="text-muted font-monospace">(Visa)</small>
        </div>
        <div class="col-4 text-center">
            <p class="mb-4 fw-bold text-decoration-underline">Le Conseil d'Administration</p>
            <div style="height: 40px;"></div>
            <small class="text-muted font-monospace">(Date, Cachet & Signatures)</small>
        </div>
    </div>

</div>

</body>
</html>