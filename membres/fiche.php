<?php
require_once "../config/database.php";
require_once "../includes/session.php";
require_once '../includes/helpers.php';

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: index.php"); exit; }

// 1. Récupération des infos du membre
$stmt = $pdo->prepare("SELECT * FROM membres WHERE id = ?");
$stmt->execute([$id]);
$m = $stmt->fetch();

if (!$m) { die("Membre introuvable."); }

// 2. Récupération des enfants
$enfants = $pdo->prepare("SELECT * FROM enfants WHERE membre_id = ?");
$enfants->execute([$id]);
$liste_enfants = $enfants->fetchAll();

// 3. Définition du chemin de la photo
$photo_folder = "../assets/uploads/membres/"; 
$photo_path = !empty($m['photo']) ? $photo_folder . $m['photo'] : '';
$display_photo = (!empty($m['photo']) && file_exists($photo_path) && $m['photo'] != 'default.png');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiche_<?= htmlspecialchars($m['matricule'] ?? '') ?>_<?= htmlspecialchars($m['nom'] ?? '') ?></title>
    <!-- Bootstrap 5.3.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

        body { 
            background: #f0f0f0; 
            font-family: 'Poppins', sans-serif; 
            font-size: 14px; 
        }
        
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
            border-left: 5px solid #0d6efd;
            padding: 5px 12px; 
            font-weight: bold; 
            margin-top: 15px;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-size: 13px;
        }

        .photo-cadre {
            width: 130px; 
            height: 150px;
            border: 2px solid #ddd; 
            overflow: hidden;
            background: #fafafa;
        }

        .photo-cadre img { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
        }

        .info-label { font-weight: 550; color: #555; }
        .info-value { padding: 5px; font-weight: 600; }

        @media print {
            body { background: white; margin: 0; }
            .fiche-a4 { margin: 0; box-shadow: none; width: 100%; padding: 10mm; }
            .no-print { display: none; }
            .section-title { -webkit-print-color-adjust: exact; background-color: #f8f9fa !important; }
        }
    </style>
</head>
<body>

<div class="container text-center mt-3 no-print">
    <button onclick="window.print()" class="btn btn-dark ms-2 shadow"><i class="fa fa-print"></i> Lancer l'impression</button>
    <a href="voir.php?id=<?= $id ?>" class="btn btn-outline-secondary me-2">Fermer</a>
</div>

<div class="fiche-a4">
    <div class="bottom-border mb-3" style="display: flex; flex-wrap: nowrap; justify-content: space-between; align-items: flex-start;">
        <div class="header-fiche text-start flex-grow-1">
            <h1 class="fw-bold mb-4">FICHE D'IDENTIFICATION</h1>
            <p class="fs-5 mb-1">MEMBRE N° : <span class="text-primary"><?= htmlspecialchars($m['matricule'] ?? '') ?></span></p>
        </div>

        <div class="photo-cadre ms-3">
            <?php if($display_photo): ?>
                <img src="<?= htmlspecialchars($photo_path) ?>" alt="Photo">
            <?php else: ?>
                <div class="text-center text-muted d-flex flex-column align-items-center justify-content-center h-100">
                    <i class="fa fa-user fa-3x opacity-25"></i>
                    <small class="mt-1" style="font-size: 10px;">SANS PHOTO</small>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="row mb-2">
        <div class="col-6">
            <span class="info-label">Date d'enregistrement :</span> 
            <span class="info-value"><?= (!empty($m['date_enregistrement']) && $m['date_enregistrement'] !== '0000-00-00') ? date('d/m/Y', strtotime($m['date_enregistrement'])) : '-' ?></span>
        </div>
        <div class="col-6 text-start">
            <span class="info-label">Agent :</span> 
            <span class="info-value text-uppercase"><?= htmlspecialchars($_SESSION['user_nom'] ?? 'Admin') ?></span>
        </div>
    </div>

    <div class="section-title">1. État Civil</div>
    <div class="row g-3">
        <div class="col-6"><span class="info-label">Nom :</span> <span class="info-value text-uppercase"><?= htmlspecialchars($m['nom'] ?? '') ?></span></div>
        <div class="col-6"><span class="info-label">Prénoms :</span> <span class="info-value"><?= htmlspecialchars($m['prenoms'] ?? '') ?></span></div>
        <div class="col-4"><span class="info-label">Sexe :</span> <span class="info-value"><?= htmlspecialchars($m['sexe'] ?? '-') ?></span></div>
        <div class="col-8">
            <span class="info-label">Né(e) le :</span> 
            <span class="info-value">
                <?= (!empty($m['date_naissance']) && $m['date_naissance'] !== '0000-00-00') ? date('d/m/Y', strtotime($m['date_naissance'])) : '-' ?> 
                à <?= htmlspecialchars($m['lieu_naissance'] ?: '-') ?>
            </span>
        </div>
        <div class="col-12"><span class="info-label">Profession :</span> <span class="info-value"><?= htmlspecialchars($m['profession'] ?: '-') ?></span></div>
    </div>

    <div class="section-title">2. Coordonnées & Adresse</div>
    <div class="row g-3">
        <div class="col-6"><span class="info-label">Téléphone 1 :</span> <span class="info-value"><?= htmlspecialchars($m['telephone1'] ?: '-') ?></span></div>
        <div class="col-6"><span class="info-label">Téléphone 2 :</span> <span class="info-value"><?= htmlspecialchars($m['telephone2'] ?: '-') ?></span></div>
        <div class="col-12"><span class="info-label">E-mail :</span> <span class="info-value"><?= htmlspecialchars($m['email'] ?: 'Non renseigné') ?></span></div>
        <div class="col-12"><span class="info-label">Quartier (Domicile) :</span> <span class="info-value"><?= htmlspecialchars($m['quartier'] ?: '-') ?></span></div>
    </div>

    <div class="section-title">3. Vie Spirituelle</div>
    <div class="row g-3">
        <div class="col-12"><span class="info-label">Église de provenance :</span> <span class="info-value"><?= htmlspecialchars($m['eglise_provenance'] ?: 'Néant') ?></span></div>
        <div class="col-4"><span class="info-label">Baptisé(e) :</span> <span class="info-value"><?= !empty($m['baptise']) ? 'OUI' : 'NON' ?></span></div>
        <div class="col-8">
            <span class="info-label">Date de Baptême :</span> 
            <span class="info-value"><?= (!empty($m['baptise']) && !empty($m['date_bapteme']) && $m['date_bapteme'] !== '0000-00-00') ? date('d/m/Y', strtotime($m['date_bapteme'])) : 'N/A' ?></span>
        </div>
        <div class="col-6"><span class="info-label">Groupe d'action :</span> <span class="info-value"><?= htmlspecialchars($m['groupe_action'] ?: 'Aucun') ?></span></div>
        <div class="col-6"><span class="info-label">Qualité :</span> <span class="info-value"><?= htmlspecialchars($m['qualite'] ?? 'Membre') ?></span></div>
    </div>

    <div class="section-title">4. Situation Familiale</div>
    <div class="row g-3 mb-2">
        <div class="col-6"><span class="info-label">Situation Matrimoniale :</span> <span class="info-value"><?= htmlspecialchars($m['situation_matrimoniale'] ?? '-') ?></span></div>
        <div class="col-6"><span class="info-label">Nom du Conjoint :</span> <span class="info-value"><?= htmlspecialchars($m['nom_conjoint'] ?: 'N/A') ?></span></div>
    </div>

    <div class="px-2 mt-3">
        <p class="info-label mb-1">Enfants :</p>
        <table class="table table-bordered table-sm align-middle">
            <thead class="table-light">
                <tr class="text-center">
                    <th>Nom & Prénoms</th>
                    <th>Sexe</th>
                    <th>Date de naissance</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($liste_enfants)): ?>
                    <?php foreach($liste_enfants as $e): ?>
                    <tr>
                        <td><?= htmlspecialchars(($e['nom'] ?? '') . ' ' . ($e['prenoms'] ?? '')) ?></td>
                        <td class="text-center"><?= htmlspecialchars($e['sexe'] ?? '-') ?></td>
                        <td class="text-center"><?= (!empty($e['date_naissance']) && $e['date_naissance'] !== '0000-00-00') ? date('d/m/Y', strtotime($e['date_naissance'])) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" class="text-center text-muted py-2">Aucun enfant enregistré</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="section-title">Observations</div>
    <div class="border p-2 mb-4" style="min-height: 80px; font-style: italic; border-radius: 5px; background-color: #fafafa;">
        <?= nl2br(htmlspecialchars($m['commentaire'] ?: 'Aucune observation particulière.')) ?>
    </div>

    <div class="row mt-5">
        <div class="col-6 text-center">
            <p class="mb-5 fw-bold text-decoration-underline">Signature du Membre</p>
            <div style="height: 60px;"></div>
        </div>
        <div class="col-6 text-center">
            <p class="mb-5 fw-bold text-decoration-underline">Le Secrétariat (Cachet)</p>
            <div style="height: 60px;"></div>
        </div>
    </div>
</div>

</body>
</html>