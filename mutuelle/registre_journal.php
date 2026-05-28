<?php
// eglise_db/mutuelle/journal.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'mutuelle');

$page_title = "Registre du journal de caisse"; 

// Extraction des 50 dernières opérations de caisse
$sql = "SELECT o.*, m.nom, m.prenoms 
        FROM mutuelle_operations o 
        JOIN mutuelle_comptes mc ON o.compte_id = mc.id 
        JOIN membres m ON mc.membre_id = m.id 
        ORDER BY o.date_op DESC, o.id DESC LIMIT 50";
$operations = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journal_Caisse_Mutuelle</title>
    <!-- Bootstrap 5.3.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

        body { 
            background: #f0f0f0; 
            font-family: 'poppins', sans-serif; 
            font-size: 13px; /* Idéal pour maximiser les lignes comptables sur un seul feuillet */
        }
        
        /* Structure stricte de la Fiche A4 officielle */
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
            border-left: 5px solid #198754; /* Marqueur vert pour le journal des flux de trésorerie */
            padding: 5px 12px; 
            font-weight: bold; 
            margin-top: 10px;
            margin-bottom: 15px;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.3px;
        }

        .info-label { font-weight: 550; color: #555; }
        .info-value { padding: 3px 5px; font-weight: 600; color: #111; }

        /* Style du tableau de bord d'écritures */
        .table-journal th {
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.3px;
            background-color: #f8f9fa !important;
            color: #212529;
            font-weight: 700;
            padding: 8px 4px;
            border-bottom: 2px solid #333 !important;
            text-align: center;
        }

        .table-journal td {
            padding: 7px 5px;
            vertical-align: middle;
        }

        /* Badges de types d'opération sobres pour impression */
        .op-badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 9px;
            font-weight: 700;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .badge-depot { background-color: #e8f5e9; color: #1b5e20; border: 1px solid #c8e6c9; }
        .badge-retrait { background-color: #ffebee; color: #b71c1c; border: 1px solid #ffcdd2; }
        .badge-pret { background-color: #fff8e1; color: #b77a1c; border: 1px solid #ffe082; }
        .badge-remboursement { background-color: #e1f5fe; color: #01579b; border: 1px solid #b3e5fc; }
        .badge-social { background-color: #f5f5f5; color: #424242; border: 1px solid #e0e0e0; }

        @media print {
            body { background: white; margin: 0; }
            .fiche-a4 { margin: 0; box-shadow: none; width: 100%; padding: 10mm; }
            .no-print { display: none !important; }
            .section-title { -webkit-print-color-adjust: exact; background-color: #f8f9fa !important; }
            .table-journal th { -webkit-print-color-adjust: exact; background-color: #f8f9fa !important; }
            .op-badge { -webkit-print-color-adjust: exact; }
            .badge-depot { background-color: #e8f5e9 !important; color: #1b5e20 !important; }
            .badge-retrait { background-color: #ffebee !important; color: #b71c1c !important; }
            .badge-pret { background-color: #fff8e1 !important; color: #b77a1c !important; }
            .badge-remboursement { background-color: #e1f5fe !important; color: #01579b !important; }
            .badge-social { background-color: #f5f5f5 !important; color: #424242 !important; }
        }
    </style>
</head>
<body>

<div class="container text-center mt-3 no-print" style="width: 230mm;">
    <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm">
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-dark btn-sm">
                <i class="fa fa-print me-2"></i>Imprimer le journal en format A4
            </button>
        </div>
        <a href="journal.php" class="btn btn-outline-secondary btn-sm">Retour au journal</a>
    </div>
</div>

<div class="fiche-a4">
    
    <div class="header-fiche text-center">
        <h1 class="fs-2 mb-0" style="font-weight: bold;">JOURNAL DES OPÉRATIONS</h1>
        <p class="fs-6 mb-1 text-muted text-uppercase">Livre de caisse générale de la mutuelle</p>
    </div>

    <div class="row mb-3">
        <div class="col-7">
            <span class="info-label">Périmètre du grand livre :</span> 
            <span class="info-value">Historique du journal des mouvements de flux</span>
        </div>
        <div class="col-5 text-end">
            <span class="info-label">Édité le :</span> 
            <span class="info-value"><?= date('d/m/Y à H:i') ?></span>
        </div>
    </div>

    <div class="section-title">Grand livre des encaissements & décaissements de caisse</div>
    
    <table class="table table-bordered table-journal table-striped mb-4">
        <thead>
            <tr>
                <th style="width: 12%;">Date</th>
                <th class="text-start" style="width: 32%;">Membre (Bénéficiaire/Déposant)</th>
                <th style="width: 15%;">Nature opération</th>
                <th class="text-end" style="width: 16%;">Montant flux</th>
                <th class="text-start" style="width: 25%;">Observations / Libellé</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($operations) > 0): ?>
                <?php foreach($operations as $op): 
                    // Assignation de la classe du badge
                    $badge_class = 'badge-social';
                    if($op['type_operation'] == 'DEPOT') $badge_class = 'badge-depot';
                    if($op['type_operation'] == 'RETRAIT') $badge_class = 'badge-retrait';
                    if($op['type_operation'] == 'PRET') $badge_class = 'badge-pret';
                    if($op['type_operation'] == 'REMBOURSEMENT') $badge_class = 'badge-remboursement';
                    
                    // Détermination du signe de la transaction pour la couleur financière
                    $is_credit = in_array($op['type_operation'], ['DEPOT', 'REMBOURSEMENT', 'SOCIAL']);
                    $text_color = $is_credit ? 'text-success' : 'text-danger';
                    $prefixe = $is_credit ? '+ ' : '- ';
                ?>
                <tr>
                    <td class="text-center text-muted font-monospace"><?= date('d/m/Y', strtotime($op['date_op'])) ?></td>
                    <td class="text-start fw-bold text-dark"><?= htmlspecialchars($op['nom'] . ' ' . $op['prenoms']) ?></td>
                    <td class="text-center">
                        <span class="op-badge <?= $badge_class ?>">
                            <?= htmlspecialchars($op['type_operation']) ?>
                        </span>
                    </td>
                    <td class="text-end fw-bold <?= $text_color ?> font-monospace">
                        <?= $prefixe . number_format($op['montant'], 0, ',', ' ') ?> F
                    </td>
                    <td class="text-start text-muted small"><?= htmlspecialchars($op['commentaire']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">
                        <i class="fa-solid fa-inbox fa-2x mb-2 d-block text-black-50"></i>
                        Aucune transaction financière n'a été saisie dans ce registre.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="row bg-light border p-2 rounded mx-0 mb-4 font-monospace" style="font-size: 10px;">
        <div class="col-6 text-muted">
            Lignes éditées : <strong><?= count($operations) ?> opérations listées</strong>
        </div>
        <div class="col-6 text-end text-muted">
            Clôture journalière automatisée
        </div>
    </div>

    <div class="row mt-3 pt-3">
        <div class="col-6 text-center">
            <p class="mb-4 fw-bold text-decoration-underline">Le Secrétaire de Séance</p>
            <div style="height: 35px;"></div>
            <small class="text-muted font-monospace">(Visa pour enregistrement)</small>
        </div>
        <div class="col-6 text-center">
            <p class="mb-4 fw-bold text-decoration-underline">Le Trésorier de Caisse</p>
            <div style="height: 35px;"></div>
            <small class="text-muted font-monospace">(Certification des pièces justificatives)</small>
        </div>
    </div>

</div>

</body>
</html>