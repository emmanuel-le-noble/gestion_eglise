<?php
// eglise_db/mutuelle/fiche_echeancier.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'mutuelle');


$pret_id = isset($_GET['pret_id']) ? (int)$_GET['pret_id'] : 0;

// 1. Extraction complète des informations du prêt et du membre
$stmt = $pdo->prepare("SELECT p.*, m.nom, m.prenoms, m.matricule, m.telephone1
                       FROM mutuelle_prets p 
                       JOIN mutuelle_comptes mc ON p.compte_id = mc.id 
                       JOIN membres m ON mc.membre_id = m.id 
                       WHERE p.id = ?");
$stmt->execute([$pret_id]);
$pret = $stmt->fetch();

if (!$pret) {
    die("Prêt introuvable.");
}

// 2. Calculs pour l'échéancier linéaire
$date_debut = new DateTime($pret['date_debut_remboursement']);
$date_fin = new DateTime($pret['date_echeance']);
$interval = $date_debut->diff($date_fin);

$nb_mois = ($interval->y * 12) + $interval->m + 1;
if ($nb_mois <= 0) $nb_mois = 1;

$mensualite_capital = $pret['montant_prete'] / $nb_mois;
$mensualite_commission = $pret['commission'] / $nb_mois;
$mensualite_totale = $mensualite_capital + $mensualite_commission;

$total_du = $pret['montant_prete'] + $pret['commission'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche Échéancier - <?= htmlspecialchars($pret['nom'] . '_' . $pret['prenoms']) ?></title>
    <!-- On charge Bootstrap pour les styles de base de l'écran, mais on le surcharge pour le A4 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- STYLE POUR L'ÉCRAN --- */
        body {
            background-color: #f3f4f6;
            font-family: 'poppins', sans-serif;
        }
        .actions-bar {
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            padding: 15px 0;
            margin-bottom: 30px;
        }
        /* Conteneur simulant une page A4 à l'écran */
        .a4-page {
            background: #ffffff;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 20mm;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
            position: relative;
        }
        .header-mutuelle {
            border-bottom: 3px double #333;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .table-echeancier th {
            background-color: #f8f9fa !important;
            color: #333 !important;
            font-weight: 600;
            font-size: 13px;
        }
        .table-echeancier td {
            font-size: 13px;
            padding: 8px 4px;
        }
        .signature-zone {
            margin-top: 50px;
        }
        .signature-box {
            border: 1px dashed #cbd5e1;
            height: 120px;
            border-radius: 4px;
            padding: 10px;
            font-size: 12px;
            color: #64748b;
        }

        /* --- STYLE STRICT POUR L'IMPRESSION A4 --- */
        @media print {
            @page {
                size: A4;
                margin: 15mm 15mm 15mm 15mm;
            }
            body {
                background-color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .actions-bar {
                display: none !important; /* Cache les boutons */
            }
            .a4-page {
                width: 100% !important;
                min-height: auto !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            /* Évite de couper une ligne de tableau en plein milieu sur deux pages */
            tr {
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>

<!-- Barre d'actions supérieure (Masquée à l'impression) -->
<div class="actions-bar no-print">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <a href="amortissement.php?pret_id=<?= $pret_id ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-arrow-left me-1"></i> Retour aux armotissements
            </a>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print();" class="btn btn-sm btn-primary px-3">
                <i class="fa fa-print me-1"></i> Imprimer / Enregistrer en PDF
            </button>
        </div>
    </div>
</div>

<!-- Conteneur Format A4 -->
<div class="a4-page">
    
    <!-- En-tête de la Structure -->
    <div class="header-mutuelle">
        <div class="row align-items-center">
            <div class="col-8">
                <h4 class="fw-bold mb-1 text-uppercase text-dark">MUTUELLE DE CRÉDIT ET D'ENTRAIDE</h4>
                <p class="text-muted small mb-0">
                    Section église évangélique<br>
                    Service gestion des engagements & recouvrement
                </p>
            </div>
            <div class="col-4 text-end">
                <div class="border p-2 text-center rounded bg-light">
                    <small class="text-muted d-block">Réf. engagement</small>
                    <span class="fw-bold text-dark">PRÊT-#<?= str_pad($pret['id'], 5, '0', STR_PAD_LEFT) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Titre du Document -->
    <div class="text-center my-4">
        <h4 class="fw-bold text-decoration-underline text-uppercase">Fiche d'échéancier de remboursement</h4>
        <small class="text-muted">Document contractuel d'amortissement linéaire</small>
    </div>

    <!-- Section Informations Part parties -->
    <div class="row g-4 mb-4">
        <!-- Cadre Membre -->
        <div class="col-6">
            <div class="card h-100 border-secondary-subtle">
                <div class="card-header bg-light py-2 fw-bold text-secondary small">IDENTIFICATION DU BÉNÉFICIAIRE</div>
                <div class="card-body py-2 small">
                    <div class="mb-1">Adhérent : <strong class="text-uppercase"><?= htmlspecialchars($pret['nom'] . ' ' . $pret['prenoms']) ?></strong></div>
                    <div class="mb-1">Matricule : <strong><?= htmlspecialchars($pret['matricule']) ?></strong></div>
                    <div>Téléphone : <span><?= htmlspecialchars($pret['telephone1'] ?? 'Non renseigné') ?></span></div>
                </div>
            </div>
        </div>
        <!-- Cadre Synthèse Prêt -->
        <div class="col-6">
            <div class="card h-100 border-secondary-subtle">
                <div class="card-header bg-light py-2 fw-bold text-secondary small">CONDITIONS FINANCIÈRES</div>
                <div class="card-body py-2 small">
                    <div class="mb-1">Capital octroyé : <strong><?= number_format($pret['montant_prete'], 0, ',', ' ') ?> F CFA</strong></div>
                    <div class="mb-1">Intérêts / Service (<?= number_format($pret['taux'], 2, ',', ' ') ?>%) : <strong><?= number_format($pret['commission'], 0, ',', ' ') ?> F CFA</strong></div>
                    <div class="text-primary"><b>Montant total dû : <?= number_format($total_du, 0, ',', ' ') ?> F CFA</b></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau de l'Échéancier -->
    <table class="table table-bordered table-echeancier align-middle text-center mb-4">
        <thead>
            <tr>
                <th width="12%">N° échéance</th>
                <th width="20%">Date prévue</th>
                <th width="22%">Principal (Capital)</th>
                <th width="22%">Frais de service</th>
                <th width="24%">Mensualité totale</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $capital_restant = $total_du;
            $date_echeance_courante = clone $date_debut;

            for ($i = 1; $i <= $nb_mois; $i++): 
                if ($i === $nb_mois) {
                    $mensualite_totale_ajustee = $capital_restant;
                } else {
                    $mensualite_totale_ajustee = $mensualite_totale;
                }
                $capital_restant_apres = $capital_restant - $mensualite_totale_ajustee;
            ?>
                <tr>
                    <td class="fw-bold text-secondary"># <?= $i ?></td>
                    <td class="fw-semibold"><?= $date_echeance_courante->format('d/m/Y') ?></td>
                    <td><?= number_format($mensualite_capital, 0, ',', ' ') ?> F</td>
                    <td><?= number_format($mensualite_commission, 0, ',', ' ') ?> F</td>
                    <td class="fw-bold bg-light text-dark"><?= number_format($mensualite_totale_ajustee, 0, ',', ' ') ?> F CFA</td>
                </tr>
            <?php 
                $date_echeance_courante->modify('+1 month');
                $capital_restant = $capital_restant_apres;
            endfor; 
            ?>
        </tbody>
    </table>

    <!-- Rappel des Règles d'engagement -->
    <div class="bg-light p-3 rounded border small mb-5">
        <span class="fw-bold text-danger d-block mb-1"><i class="fa fa-info-circle me-1"></i> Clause de remboursement :</span>
        <span class="text-muted">
            L'adhérent s'engage à honorer chacune des mensualités listées ci-dessus avant la date limite indiquée sous peine d'application des pénalités de retard en vigueur et du gel des droits de tirage de son compte mutuelle.
        </span>
    </div>

    <!-- Zone de validation des signatures en bas de page A4 -->
    <div class="signature-zone">
        <div class="row text-center">
            <div class="col-4">
                <div class="fw-semibold small text-dark mb-2">Le bénéficiaire</div>
                <div class="signature-box">
                    <br><br><small>(Précéder de la mention "Lu et approuvé")</small>
                </div>
            </div>
            <div class="col-4">
                <div class="fw-semibold small text-dark mb-2">Le trésorier</div>
                <div class="signature-box"></div>
            </div>
            <div class="col-4">
                <div class="fw-semibold small text-dark mb-2">Le pasteur principal</div>
                <div class="signature-box"></div>
            </div>
        </div>
    </div>

</div>

</body>
</html>