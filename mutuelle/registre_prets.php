<?php
// eglise_db/mutuelle/prets.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'mutuelle');

$page_title = "Suivi des prêts & alertes"; 

try {
    // ÉTAPE 1 : Automatisation — On passe en 'RETARD' les prêts non soldés dont l'échéance est dépassée
    $pdo->query("UPDATE mutuelle_prets SET statut = 'RETARD' WHERE statut = 'EN_COURS' AND date_echeance < CURRENT_DATE AND montant_rembourse < montant_prete");

    // ÉTAPE 2 : Récupération des statistiques des prêts
    $stats = $pdo->query("SELECT COUNT(id) as total_prets, SUM(CASE WHEN statut = 'RETARD' THEN 1 ELSE 0 END) as nb_retards, SUM(montant_prete) as capital_prete, SUM(montant_rembourse) as capital_rembourse, SUM(montant_prete - montant_rembourse) as reste_a_recouvrer FROM mutuelle_prets WHERE statut != 'SOLDE'")->fetch();

    // ÉTAPE 3 : Liste complète des prêts avec les informations du membre
    $sql = "SELECT p.*, m.nom, m.prenoms, (p.montant_prete - p.montant_rembourse) as reste_a_payer FROM mutuelle_prets p JOIN mutuelle_comptes mc ON p.compte_id = mc.id JOIN membres m ON mc.membre_id = m.id ORDER BY CASE WHEN p.statut = 'RETARD' THEN 1 WHEN p.statut = 'EN_COURS' THEN 2 ELSE 3 END, p.date_echeance ASC";
    $prets = $pdo->query($sql)->fetchAll();

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
    <title>Registre_Suivi_Prets</title>
    <!-- Bootstrap 5.3.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

        body { 
            background: #f0f0f0; 
            font-family: 'poppins', sans-serif; 
            font-size: 13px; /* Écritures condensées pour faire tenir le tableau financier sur A4 */
        }
        
        /* Conteneur Format A4 Officiel */
        .fiche-a4 {
            width: 210mm;
            min-height: 297mm;
            padding: 15mm 15mm;
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
            border-left: 5px solid #dc3545; /* Couleur rouge/alerte pour le suivi de recouvrement */
            padding: 5px 12px; 
            font-weight: bold; 
            margin-top: 15px;
            margin-bottom: 15px;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.3px;
        }

        .info-label { font-weight: 550; color: #555; }
        .info-value { padding: 3px 5px; font-weight: 600; color: #111; }

        /* Mini widgets statistiques adaptés au A4 */
        .kpi-box {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 8px 10px;
            background-color: #f8f9fa;
        }

        /* Styles du tableau financier */
        .table-prets th {
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.3px;
            background-color: #f8f9fa !important;
            color: #212529;
            font-weight: 700;
            padding: 8px 4px;
            border-bottom: 2px solid #333 !important;
            vertical-align: middle;
        }

        .table-prets td {
            padding: 8px 4px;
            vertical-align: middle;
        }

        .table-danger-custom {
            background-color: rgba(220, 53, 69, 0.04) !important;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-3px); }
        }
        .animate-bounce { animation: bounce 1s infinite; }

        @media print {
            body { background: white; margin: 0; }
            .fiche-a4 { margin: 0; box-shadow: none; width: 100%; padding: 10mm; }
            .no-print { display: none !important; }
            .section-title { -webkit-print-color-adjust: exact; background-color: #f8f9fa !important; }
            .table-prets th { -webkit-print-color-adjust: exact; background-color: #f8f9fa !important; }
            .table-danger-custom { -webkit-print-color-adjust: exact; background-color: rgba(220, 53, 69, 0.04) !important; }
            .kpi-box { -webkit-print-color-adjust: exact; background-color: #f8f9fa !important; }
            .bg-dark { -webkit-print-color-adjust: exact; background-color: #212529 !important; color: white !important; }
        }
    </style>
</head>
<body>

<div class="container text-center mt-3 no-print">
    <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm">
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-dark btn-sm ">
                <i class="fa fa-print me-2"></i>Imprimer le Registre A4
            </button>
        </div>
        <a href="prets.php" class="btn btn-outline-secondary btn-sm">Retour au gestionnaire de prêts</a>
    </div>
</div>

<div class="fiche-a4">
    
    <div class="header-fiche text-center">
        <h1 class="fs-2 mb-0" style="font-weight: bold;">REGISTRE & SUIVI DES PRÊTS</h1>
        <p class="fs-6 mb-1 text-muted text-uppercase">Contrôle du recouvrement et des échéances</p>
    </div>

    <div class="row mb-3">
        <div class="col-7">
            <span class="info-label">Nature du document :</span> 
            <span class="info-value text-danger">Registre des capitaux engagés</span>
        </div>
        <div class="col-5 text-end">
            <span class="info-label">Mise à jour :</span> 
            <span class="info-value"><?= date('d/m/Y à H:i') ?></span>
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-3">
            <div class="kpi-box border-start border-danger border-3">
                <span class="text-muted d-block small fw-bold">DOSSIERS EN RETARD</span>
                <span class="fs-6 fw-bold text-danger font-monospace">
                    <?= (int)$stats['nb_retards'] ?>
                    <?php if($stats['nb_retards'] > 0): ?>
                        <i class="fa-solid fa-triangle-exclamation animate-bounce ms-1" style="font-size: 11px;"></i>
                    <?php endif; ?>
                </span>
            </div>
        </div>
        <div class="col-3">
            <div class="kpi-box border-start border-primary border-3">
                <span class="text-muted d-block small fw-bold">CAPITAL ENGAGÉ</span>
                <span class="fs-6 fw-bold text-dark font-monospace"><?= number_format($stats['capital_prete'] ?? 0, 0, ',', ' ') ?> F</span>
            </div>
        </div>
        <div class="col-3">
            <div class="kpi-box border-start border-success border-3">
                <span class="text-muted d-block small fw-bold">TOTAL RECOUVRÉ</span>
                <span class="fs-6 fw-bold text-success font-monospace"><?= number_format($stats['capital_rembourse'] ?? 0, 0, ',', ' ') ?> F</span>
            </div>
        </div>
        <div class="col-3">
            <div class="kpi-box bg-dark text-white border-start border-warning border-3">
                <span class="text-white-50 d-block small fw-bold">RESTE EN DEHORS</span>
                <span class="fs-6 fw-bold text-warning font-monospace"><?= number_format($stats['reste_a_recouvrer'] ?? 0, 0, ',', ' ') ?> F</span>
            </div>
        </div>
    </div>

    <div class="section-title">État d'avancement et échéancier des remboursements</div>
    
    <table class="table table-bordered table-prets align-middle mb-4">
        <thead>
            <tr class="text-center">
                <th class="text-start" style="width: 30%;">Bénéficiaire (Adhérent)</th>
                <th style="width: 13%;">Date prêt</th>
                <th style="width: 13%;">Échéance</th>
                <th style="width: 16%;">Montant prêté</th>
                <th style="width: 16%;">Remboursé</th>
                <th style="width: 14%;">Reste à payer</th>
                <th style="width: 10%;">Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($prets)): ?>
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">Aucun prêt actif enregistré dans les archives.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($prets as $p): 
                    $row_class = '';
                    $badge_style = 'border: 1px solid #198754; color: #198754; background-color: #e8f5e9;';
                    
                    if ($p['statut'] == 'RETARD') {
                        $row_class = 'table-danger-custom';
                        $badge_style = 'border: 1px solid #dc3545; color: #dc3545; background-color: #fde8e8; font-weight: 700;';
                    } elseif ($p['statut'] == 'EN_COURS') {
                        $badge_style = 'border: 1px solid #ffc107; color: #664d03; background-color: #fff8e1;';
                    }
                    
                    $pct = ($p['montant_prete'] > 0) ? ($p['montant_rembourse'] / $p['montant_prete']) * 100 : 0;
                ?>
                    <tr class="<?= $row_class ?>">
                        <td class="text-start">
                            <span class="fw-bold text-dark text-uppercase"><?= htmlspecialchars($p['nom'] . ' ' . $p['prenoms']) ?></span>
                        </td>
                        <td class="text-center text-muted font-monospace"><?= date('d/m/Y', strtotime($p['date_pret'])) ?></td>
                        <td class="text-center font-monospace fw-semibold <?= $p['statut'] == 'RETARD' ? 'text-danger' : '' ?>">
                            <?= date('d/m/Y', strtotime($p['date_echeance'])) ?>
                        </td>
                        <td class="text-end fw-bold font-monospace"><?= number_format($p['montant_prete'], 0, ',', ' ') ?> F</td>
                        <td class="text-end font-monospace">
                            <span class="text-success fw-semibold"><?= number_format($p['montant_rembourse'], 0, ',', ' ') ?> F</span>
                            <div class="progress mt-1 mx-auto" style="height: 3px; max-width: 90px;">
                                <div class="progress-bar bg-success" style="width: <?= $pct ?>%"></div>
                            </div>
                        </td>
                        <td class="text-end fw-bold text-primary font-monospace"><?= number_format($p['reste_a_payer'], 0, ',', ' ') ?> F</td>
                        <td class="text-center">
                            <span class="badge rounded-pill small px-2 py-1" style="<?= $badge_style ?> font-size: 9px; text-transform: uppercase;">
                                <?= $p['statut'] == 'SOLDE' ? 'Soldé' : ($p['statut'] == 'RETARD' ? 'En retard' : 'En cours') ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="row bg-light border p-2 rounded mx-0 mb-4 font-monospace text-muted" style="font-size: 10px;">
        <div class="col-6">
            Dossiers audités : <strong><?= count($prets) ?> lignes de crédit</strong>
        </div>
        <div class="col-6 text-end">
            Document réglementaire de contrôle interne
        </div>
    </div>

    <div class="row mt-4 pt-3">
        <div class="col-6 text-center">
            <p class="mb-4 fw-bold text-decoration-underline">Le Responsable des Crédits</p>
            <div style="height: 40px;"></div>
            <small class="text-muted font-monospace">(Visa et validation)</small>
        </div>
        <div class="col-6 text-center">
            <p class="mb-4 fw-bold text-decoration-underline">Le Trésorier Principal</p>
            <div style="height: 40px;"></div>
            <small class="text-muted font-monospace">(Signature, Cachet d'arrêté de caisse)</small>
        </div>
    </div>

</div>

</body>
</html>