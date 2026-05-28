<?php
// eglise_db/mutuelle/membres.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'mutuelle');

// Extraction des comptes de la mutuelle avec les infos de l'identité du membre
$sql = "SELECT mc.*, m.nom, m.prenoms, m.matricule 
        FROM mutuelle_comptes mc 
        JOIN membres m ON mc.membre_id = m.id 
        ORDER BY m.nom ASC";
$adherents = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registre_Membres_Mutuelle</title>
    <!-- Bootstrap 5.3.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

        body { 
            background: #f0f0f0; 
            font-family: 'poppins', sans-serif; 
            font-size: 14px; /* Légèrement plus compact pour faire rentrer plus de lignes sur le A4 */
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
            border-left: 5px solid #0d6efd; /* Code couleur bleu de la mutuelle */
            padding: 5px 12px; 
            font-weight: bold; 
            margin-top: 10px;
            margin-bottom: 15px;
            text-transform: uppercase;
            font-size: 12px;
        }

        .info-label { font-weight: 550; color: #555; }
        .info-value { padding: 3px 5px; font-weight: 600; color: #111; }

        /* Style du tableau type registre */
        .table-registre th {
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            background-color: #f8f9fa !important;
            color: #212529;
            font-weight: 700;
            padding: 8px 6px;
            border-bottom: 2px solid #333 !important;
        }

        .table-registre td {
            padding: 8px 6px;
            vertical-align: middle;
        }

        .badge-actif {
            background-color: #d1e7dd;
            color: #0f5132;
            font-weight: 600;
            font-size: 10px;
        }

        .badge-inactif {
            background-color: #f8d7da;
            color: #842029;
            font-weight: 600;
            font-size: 10px;
        }

        @media print {
            body { background: white; margin: 0; }
            .fiche-a4 { margin: 0; box-shadow: none; width: 100%; padding: 10mm; }
            .no-print { display: none !important; }
            .section-title { -webkit-print-color-adjust: exact; background-color: #f8f9fa !important; }
            .table-registre th { -webkit-print-color-adjust: exact; background-color: #f8f9fa !important; }
            .badge-actif { -webkit-print-color-adjust: exact; background-color: #d1e7dd !important; }
            .badge-inactif { -webkit-print-color-adjust: exact; background-color: #f8d7da !important; }
        }
    </style>
</head>
<body>

<div class="container text-center mt-3 no-print" style="max-width: 210mm;">
    <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm">
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-dark btn-sm">
                <i class="fa fa-print me-2"></i>Imprimer le Registre A4
            </button>
        </div>
        <a href="membres_mutuelle.php" class="btn btn-outline-secondary btn-sm">Retour à l'accueil</a>
    </div>
</div>

<div class="fiche-a4">
    
    <div class="header-fiche text-center">
        <h1 class="fs-2 mb-0" style="font-weight: bold;">REGISTRE DES MEMBRES</h1>
        <p class="fs-5 mb-1 text-muted text-uppercase">Situation Générale de la Mutuelle</p>
    </div>

    <div class="row mb-3">
        <div class="col-7">
            <span class="info-label">Document comptable :</span> 
            <span class="info-value">Liste intégrale des adhérents tontine</span>
        </div>
        <div class="col-5 text-end">
            <span class="info-label">Arrêté au :</span> 
            <span class="info-value"><?= date('d/m/Y à H:i') ?></span>
        </div>
    </div>

    <div class="section-title">Liste nominative des comptes de dépôt</div>
    
    <table class="table table-bordered table-registre table-striped mb-4">
        <thead>
            <tr>
                <th class="text-center" style="width: 15%;">Matricule</th>
                <th style="width: 36%;">Nom & Prénoms</th>
                <th class="text-center" style="width: 15%;">Adhésion</th>
                <th class="text-end" style="width: 16%;">Épargne tontine</th>
                <th class="text-end" style="width: 14%;">Fonds social</th>
                <th class="text-center" style="width: 14%;">Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($adherents) > 0): ?>
                <?php foreach($adherents as $a): ?>
                <tr>
                    <td class="text-center fw-bold text-secondary font-monospace"><?= htmlspecialchars($a['matricule']) ?></td>
                    <td class="fw-bold text-dark"><?= htmlspecialchars($a['nom'] . ' ' . $a['prenoms']) ?></td>
                    <td class="text-center"><?= date('d/m/Y', strtotime($a['date_adhesion'])) ?></td>
                    <td class="text-end fw-bold text-success font-monospace"><?= number_format($a['solde_tontine'], 0, ',', ' ') ?> F</td>
                    <td class="text-end fw-bold text-info font-monospace"><?= number_format($a['solde_social'], 0, ',', ' ') ?> F</td>
                    <td class="text-center">
                        <span class="badge rounded-pill <?= $a['statut'] == 'ACTIF' ? 'badge-actif' : 'badge-inactif' ?> px-2 py-1">
                            <?= htmlspecialchars($a['statut']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="fa-solid fa-inbox fa-2x mb-2 d-block text-black-50"></i>
                        Aucun adhérent enregistré dans la base de données.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="row bg-light border p-2 rounded mx-0 mb-4 font-monospace">
        <div class="col-6 small text-muted">
            <?php if(count($adherents) > 1): ?>
                Total des enregistrements : <strong><?= count($adherents) ?> membres</strong>
            <?php else: ?>
                Total des enregistrements : <strong><?= count($adherents) ?> membre</strong>
            <?php endif; ?>
        </div>
        <div class="col-6 text-end small text-muted">
            Format certifié conforme pour archivage papier A4
        </div>
    </div>

    <div class="row mt-4 pt-3">
        <div class="col-6 text-center">
            <p class="mb-4 fw-bold text-decoration-underline">Le Secrétariat Général</p>
            <div style="height: 40px;"></div>
            <small class="text-muted font-monospace">(Visa de contrôle)</small>
        </div>
        <div class="col-6 text-center">
            <p class="mb-4 fw-bold text-decoration-underline">Le Trésorier de la Mutuelle</p>
            <div style="height: 40px;"></div>
            <small class="text-muted font-monospace">(Signature et Cachet)</small>
        </div>
    </div>

</div>

</body>
</html>