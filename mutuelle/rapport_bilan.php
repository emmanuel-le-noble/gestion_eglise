<?php
// eglise_db/mutuelle/bilan.php ou rapport_bilan.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'mutuelle');

try {
    // 1. Calcul de l'épargne tontine globale et du nombre de membres actifs
    $sql_base = "SELECT 
        COALESCE(SUM(solde_tontine), 0) as total_tontine,
        (SELECT COUNT(*) FROM mutuelle_comptes WHERE statut = 'ACTIF') as nb_membres
        FROM mutuelle_comptes";
    $res_base = $pdo->query($sql_base)->fetch();

    $total_tontine = floatval($res_base['total_tontine']);
    $nb_membres = intval($res_base['nb_membres']);

    // 2. Calcul du Reste Dehors (Capital + Intérêts restants dus par les membres)
    $sql_dettes = "SELECT COALESCE(SUM((montant_prete + commission) - montant_rembourse), 0) as total_dettes
                   FROM mutuelle_prets 
                   WHERE statut != 'SOLDE'";
    $total_dettes = floatval($pdo->query($sql_dettes)->fetchColumn());

    // 3. Calcul des Gains Générés par la Mutuelle
    // Gain A : Les intérêts/commissions cumulés sur les prêts
    $sql_gains_interets = "SELECT COALESCE(SUM(commission), 0) FROM mutuelle_prets";
    $gains_interets = floatval($pdo->query($sql_gains_interets)->fetchColumn());

    // Gain B : Les frais de tenue de compte prélevés
    $sql_gains_frais = "SELECT COALESCE(SUM(montant), 0) FROM mutuelle_operations WHERE type_operation = 'FRAIS_TENUE'";
    $gains_frais = floatval($pdo->query($sql_gains_frais)->fetchColumn());

    // Total des gains accumulés par la mutuelle
    $total_gains = $gains_interets + $gains_frais;

    // La liquidité totale en caisse correspond à l'épargne disponible augmentée des gains propres de la mutuelle
    $total_caisse = $total_tontine + $total_gains;

    // Calcul du ratio d'endettement (Prêts / Épargne totale tontine)
    $ratio = ($total_tontine > 0) ? ($total_dettes / $total_tontine) * 100 : 0;

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilan_Mutuelle_<?= date('d_m_Y') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

        body { 
            background: #f0f0f0; 
            font-family: 'poppins', sans-serif; 
            font-size: 13px; 
        }
        
        /* Structure stricte de la Fiche A4 officielle */
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
            border-left: 5px solid #0d6efd; /* Bleu pour la mutuelle */
            padding: 5px 12px; 
            font-weight: bold; 
            margin-top: 25px;
            margin-bottom: 15px;
            text-transform: uppercase;
            font-size: 13px;
        }

        /* Blocs financiers épurés */
        .bloc-synthese {
            background: #fafafa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
        }

        .info-label { font-weight: 550; color: #555; }
        .info-value { padding: 5px; font-weight: 600; }
        .text-xs { font-size: 0.75rem; }

        @media print {
            body { background: white; margin: 0; }
            .fiche-a4 { margin: 0; box-shadow: none; width: 100%; padding: 10mm; }
            .no-print { display: none; }
            .section-title { -webkit-print-color-adjust: exact; background-color: #f8f9fa !important; }
            .progress, .progress-bar { -webkit-print-color-adjust: exact; background-color: #e9ecef !important; }
            .progress-bar { background-color: #ffc107 !important; } /* Garde la jauge warning visible */
        }
    </style>
</head>
<body>

<div class="container text-center mt-3 no-print" style="max-width: 210mm;">
    <div class="d-flex justify-content-center gap-2 bg-white p-3 rounded shadow-sm">
        <button onclick="window.print()" class="btn btn-dark shadow"><i class="fa fa-print me-2"></i>Imprimer le bilan A4</button>
        <a href="index.php" class="btn btn-outline-secondary">Retour à la mutuelle</a>
    </div>
</div>

<div class="fiche-a4">
    
    <div class="header-fiche text-center">
        <h1 class="fs-2 mb-0" style="font-weight: bold;">SITUATION GLOBALE DE LA MUTUELLE</h1>
        <p class="fs-5 mb-1 text-muted text-uppercase">Bilan financier et analyse des gains</p>
    </div>

    <div class="row mb-3">
        <div class="col-7">
            <span class="info-label">Date du point de situation :</span> 
            <span class="info-value">Au <?= date('d/m/Y') ?> à <?= date('H:i') ?></span>
        </div>
        <div class="col-5 text-end">
            <span class="info-label">Édité par :</span> 
            <span class="info-value text-uppercase"><?= htmlspecialchars($_SESSION['user_nom'] ?? 'Admin') ?></span>
        </div>
    </div>

    <div class="section-title">1. Répartition des capitaux de la Mutuelle</div>
    <div class="row g-3">
        <div class="col-3">
            <div class="border p-2 text-center rounded bg-light">
                <span class="info-label small text-uppercase d-block text-muted">Épargne Tontine</span>
                <h4 class="fw-bold text-primary mt-1 mb-0" style="font-size:15px;"><?= number_format($total_tontine, 0, ',', ' ') ?> F</h4>
            </div>
        </div>
        <div class="col-3">
            <div class="border p-2 text-center rounded bg-light">
                <span class="info-label small text-uppercase d-block text-muted">Gains Mutuelle</span>
                <h4 class="fw-bold text-success mt-1 mb-0" style="font-size:15px;"><?= number_format($total_gains, 0, ',', ' ') ?> F</h4>
            </div>
        </div>
        <div class="col-3">
            <div class="border p-2 text-center rounded bg-light">
                <span class="info-label small text-uppercase d-block text-muted">Prêts Dehors</span>
                <h4 class="fw-bold text-warning mt-1 mb-0" style="font-size:15px;"><?= number_format($total_dettes, 0, ',', ' ') ?> F</h4>
            </div>
        </div>
        <div class="col-3">
            <div class="border p-2 text-center rounded bg-light">
                <span class="info-label small text-uppercase d-block text-muted">Membres Actifs</span>
                <h4 class="fw-bold text-dark mt-1 mb-0" style="font-size:15px;"><?= $nb_membres ?> Adhérents</h4>
            </div>
        </div>
    </div>

    <div class="section-title">2. Résumé de la Trésorerie</div>
    <div class="bloc-synthese mb-3">
        <div class="p-3 bg-white border rounded text-center mb-3">
            <span class="d-block text-muted small text-uppercase mb-1">Liquidités totales théoriques en Caisse / Banque</span>
            <h2 class="fw-bold text-dark mb-0 font-monospace"><?= number_format($total_caisse, 0, ',', ' ') ?> FCFA</h2>
        </div>
        
        <div class="row px-3">
            <div class="col-6 py-2 border-bottom">
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Part Tontine (Épargne brute) :</span>
                    <span class="fw-bold font-monospace"><?= number_format($total_tontine, 0, ',', ' ') ?> F</span>
                </div>
            </div>
            <div class="col-6 py-2 border-bottom ps-4">
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Gains propres structure :</span>
                    <span class="fw-bold font-monospace text-success">+ <?= number_format($total_gains, 0, ',', ' ') ?> F</span>
                </div>
                <div class="text-muted text-xs mt-1">
                    • Intérêts sur prêts : <?= number_format($gains_interets, 0, ',', ' ') ?> F <br>
                    • Frais de tenue de compte : <?= number_format($gains_frais, 0, ',', ' ') ?> F
                </div>
            </div>
        </div>
    </div>

    <div class="section-title">3. Analyse de l'exposition et des risques</div>
    <div class="border rounded p-3 bg-light">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="small fw-bold text-dark text-uppercase">Taux d'occupation des fonds mutuels</span>
            <span class="fs-5 fw-bold font-monospace text-dark"><?= round($ratio, 1) ?>%</span>
        </div>
        
        <div class="progress mb-2" style="height: 12px; background-color: #e9ecef; border-radius: 3px;">
            <div class="progress-bar bg-warning" role="progressbar" style="width: <?= min($ratio, 100) ?>%"></div>
        </div>
        
        <p class="small text-muted mb-0" style="line-height:1.4; font-style: italic;">
            Note technique : Ce ratio exprime la part de l'épargne tontine des membres actuellement injectée dans les prêts en cours d'amortissement. Un taux inférieur à 75% garantit une excellente liquidité pour faire face aux demandes de retraits des adhérents.
        </p>
    </div>

    <div class="alert alert-light border small mt-4" style="background-color: #fafafa;">
        <i class="fa-solid fa-circle-info me-2 text-primary"></i>
        <strong>Rapprochement de caisse :</strong> Le solde physique de la caisse mutuelle associé aux différents comptes de dépôt en banque doit impérativement être égal à la somme des liquidités totales affichées dans la section 2 du présent bilan.
    </div>

    <div class="row mt-5 pt-4">
        <div class="col-6 text-center">
            <p class="mb-4 fw-bold text-decoration-underline">Le Trésorier de la mutuelle</p>
            <div style="height: 45px;"></div>
            <small class="text-muted font-monospace">(Nom et Signature)</small>
        </div>
        <div class="col-6 text-center">
            <p class="mb-4 fw-bold text-decoration-underline">Le Comité de contrôle / Audit</p>
            <div style="height: 45px;"></div>
            <small class="text-muted font-monospace">(Visas et Cachet)</small>
        </div>
    </div>
</div>

</body>
</html>