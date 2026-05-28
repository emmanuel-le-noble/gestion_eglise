<?php
// eglise_db/evenements/rapports.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'communication');

$page_title = "Rapport d'activités"; 
require_once '../includes/header.php'; 

// Gestion des filtres (Mois ou Année)
$mois = $_GET['mois'] ?? date('m');
$annee = $_GET['annee'] ?? date('Y');

$sql = "SELECT * FROM evenements  WHERE MONTH(date_evenement) = ? AND YEAR(date_evenement) = ? ORDER BY date_evenement ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$mois, $annee]);
$evenements = $stmt->fetchAll();

// Liste des mois pour le sélecteur
$liste_mois = [
    '01' => 'Janvier', '02' => 'Février', '03' => 'Mars', '04' => 'Avril',
    '05' => 'Mai', '06' => 'Juin', '07' => 'Juillet', '08' => 'Août',
    '09' => 'Septembre', '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
];
?>

<div class="container mt-4">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h5 class="fw-bold mb-2"><i class="fa-solid fa-file-lines me-2 text-primary"></i>Générer un rapport d'activités</h5>
            <form class="row g-3" method="GET">
                <div class="col-md-3 d-flex align-items-end">
                    <div class="w-100">
                        <label class="small fw-bold">Mois</label>
                        <select name="mois" class="form-select">
                            <?php foreach($liste_mois as $num => $nom): ?>
                                <option value="<?= $num ?>" <?= $mois == $num ? 'selected' : '' ?>><?= $nom ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div>
                        <label class="small fw-bold">Année</label>
                        <input type="number" name="annee" class="form-control" value="<?= $annee ?>">
                    </div>
                </div>
                
                <div class="col-md-5 d-flex align-items-end gap-4">
                    <button type="submit" class="btn btn-dark w-100">Afficher</button>
                    <a href="registre_rapports.php?annee=<?= $annee ?>&mois=<?= $mois ?>" target="_blank" class="btn btn-outline-primary btn-sm w-100">
                        <i class="fa fa-print me-1"></i> Imprimer le rapport
                    </a>
                </div>
                <div class="col-md-2 d-flex flex-column gap-3">
                    <a href="index.php" class="btn btn-light btn-sm border w-100">Retour</a>
                    <a href="export_evenements.php?mois=<?= $mois ?>&annee=<?= $annee ?>" class="btn btn-success w-100">
                        <i class="fa-solid fa-file-excel me-1"></i> Export
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold">Activités de <?= $liste_mois[$mois] ?> <?= $annee ?></h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3">Date</th>
                            <th>Événement</th>
                            <th>Type</th>
                            <th>Lieu</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($evenements)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Aucune activité enregistrée pour cette période.</td></tr>
                        <?php else: ?>
                            <?php foreach($evenements as $ev): ?>
                            <tr>
                                <td class="ps-3 small"><?= date('d/m/Y', strtotime($ev['date_evenement'])) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($ev['titre']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= $ev['type_evenement'] ?></span></td>
                                <td><?= htmlspecialchars($ev['lieu']) ?></td>
                                <td class="small text-muted"><?= substr(htmlspecialchars($ev['description']), 0, 50) ?>...</td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>