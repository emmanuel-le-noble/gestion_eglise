<?php
// eglise_db/evenements/registre_rapport.php
require_once "../config/database.php";
require_once "../includes/session.php";

// Sécurisation de l'accès direct aux impressions de registres
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) { die("Session expirée. Veuillez vous reconnecter."); }

// Récupération des filtres depuis l'URL (concordance stricte avec le tableau de bord)
$mois = $_GET['mois'] ?? date('m');
$annee = $_GET['annee'] ?? date('Y');

// Extraction des données ordonnées chronologiquement
$sql = "SELECT * FROM evenements WHERE MONTH(date_evenement) = ? AND YEAR(date_evenement) = ? ORDER BY date_evenement ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$mois, $annee]);
$evenements = $stmt->fetchAll();

$liste_mois = [
    '01' => 'Janvier', '02' => 'Février', '03' => 'Mars', '04' => 'Avril',
    '05' => 'Mai', '06' => 'Juin', '07' => 'Juillet', '08' => 'Août',
    '09' => 'Septembre', '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registre_Activites_<?= $liste_mois[$mois] ?>_<?= $annee ?></title>
    <!-- Bootstrap 5.3.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { 
            background: #f0f0f0; 
            font-family: 'poppins', sans-serif; 
            font-size: 14px; 
            color: #212529;
        }
        
        /* Encapsulation stricte aux dimensions physiques A4 Portrait */
        .fiche-a4 {
            width: 210mm;
            min-height: 297mm;
            padding: 15mm 15mm;
            margin: 10mm auto;
            background: white;
            box-shadow: 0 0 15px rgba(0,0,0,0.15);
            box-sizing: border-box;
            position: relative;
        }

        .header-fiche { 
            border-bottom: 3px solid #1a252f; 
            margin-bottom: 20px; 
            padding-bottom: 12px; 
        }

        .section-title { 
            background: #f8f9fa; 
            border-left: 5px solid #2c3e50; 
            padding: 6px 12px; 
            font-weight: 700; 
            margin: 15px 0;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }

        .info-label { font-weight: 600; color: #555; }
        .info-value { padding-left: 5px; font-weight: 700; color: #111; }

        /* Configuration du tableau d'archivage administratif */
        .table-a4 th {
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.3px;
            background-color: #f8f9fa !important;
            color: #1a252f;
            font-weight: 700;
            padding: 10px 6px;
            border-bottom: 2px solid #1a252f !important;
            text-align: center;
        }

        .table-a4 td {
            padding: 9px 6px;
            vertical-align: top;
            border-color: #dee2e6 !important;
        }

        /* Barre de contrôle flottante pour l'appel de l'imprimante système */
        @media print {
            body { background: white; margin: 0; }
            .fiche-a4 { margin: 0; box-shadow: none; width: 100%; padding: 10mm; }
            .no-print { display: none !important; }
            .section-title { -webkit-print-color-adjust: exact; background-color: #f8f9fa !important; }
            .table-a4 th { -webkit-print-color-adjust: exact; background-color: #f8f9fa !important; }
        }
    </style>
</head>
<body>

<div class="container mt-3 no-print" style="width: 260mm;">
    <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-3 shadow-sm border">
        <div>
            <span class="badge bg-dark font-monospace mb-1">Prêt pour traitement</span>
            <p class="mb-0 text-muted small">Veuillez privilégier l'option <strong>"Enregistrer au format PDF"</strong> ou l'impression directe sur papier A4.</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary btn-sm fw-bold px-3">
                <i class="fa-solid fa-print me-1"></i> Lancer l'impression
            </button>
            <button onclick="window.close()" class="btn btn-light btn-sm border px-3">Fermer</button>
        </div>
    </div>
</div>

<div class="fiche-a4">
    
    <div class="header-fiche text-center">
        <h1 class="fw-bold mb-1" style="letter-spacing: 1.5px; color: #1a252f; font-size: 24px;">RAPPORT MENSUEL D'ACTIVITÉS</h1>
        <p class="text-uppercase text-muted tracking-wide mb-0 small" style="font-weight: 600;">Registre de Suivi Événementiel et Paroissial</p>
    </div>

    <div class="row mb-3" style="font-size: 12px;">
        <div class="col-7">
            <span class="info-label">Période d'évaluation :</span> 
            <span class="info-value text-uppercase"><?= $liste_mois[$mois] ?> <?= htmlspecialchars($annee) ?></span>
        </div>
        <div class="col-5 text-end">
            <span class="info-label">Date d'édition :</span> 
            <span class="info-value"><?= date('d/m/Y à H:i') ?></span>
        </div>
    </div>

    <div class="section-title">Pointage des réunions, cultes et événements exécutés</div>
    
    <table class="table table-bordered table-a4 table-striped mb-3">
        <thead>
            <tr>
                <th style="width: 12%;">Date Act.</th>
                <th style="width: 25%;">Intitulé / Thème de l'Événement</th>
                <th style="width: 15%;">Catégorie</th>
                <th style="width: 18%;">Lieu Affecté</th>
                <th style="width: 30%;">Synthèse & Observations</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($evenements)): ?>
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted bg-white">
                        Aucune activité ou évenement n'a fait l'objet d'un enregistrement dans le journal au cours de cette période.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach($evenements as $ev): ?>
                <tr>
                    <td class="text-center font-monospace fw-bold" style="font-size: 12px;">
                        <?= date('d/m/Y', strtotime($ev['date_evenement'])) ?>
                    </td>
                    <td class="text-start fw-bold text-dark" style="font-size: 12px; text-align: justify;">
                        <?= htmlspecialchars($ev['titre']) ?>
                    </td>
                    <td class="text-center small text-secondary">
                        <?= htmlspecialchars($ev['type_evenement']) ?>
                    </td>
                    <td class="text-start small font-monospace">
                        <?= htmlspecialchars($ev['lieu']) ?>
                    </td>
                    <td class="text-start" style="font-size: 12px; text-align: justify;">
                        <?= !empty($ev['description']) ? htmlspecialchars($ev['description']) : '<em>Aucune note descriptive n\'a été consignée au registre.</em>' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="row font-monospace mb-1" style="font-size: 11px;">
        <div class="col-12 text-muted">
            Total d'enregistrements validés pour l'archive : <strong><?= count($evenements) ?> ligne(s) d'activité(s) comptabilisée(s)</strong>.
        </div>
    </div>

    <div class="row pt-4 text-center" style="position: absolute; bottom: 25mm; left: 15mm; right: 15mm;">
        <div class="col-6">
            <p class="mb-5 fw-bold text-decoration-underline">Le Secrétariat Général</p>
            <div style="height: 40px;"></div>
            <small class="text-muted font-monospace">(Visa pour dépôt d'archives)</small>
        </div>
        <div class="col-6">
            <p class="mb-5 fw-bold text-decoration-underline">Le Conseil d'Administration / Pasteur</p>
            <div style="height: 40px;"></div>
            <small class="text-muted font-monospace">(Attestation de conformité)</small>
        </div>
    </div>

</div>

</body>
</html>