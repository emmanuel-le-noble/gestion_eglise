<?php
// eglise_db/membres/index.php
require_once "../config/database.php";
require_once "../includes/session.php"; 

// Toutes les pages du dossier membres contiendront cette ligne :
securiser_par_module($pdo, 'membres');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Gestion des filtres de recherche et de sélection
$recherche = isset($_GET['recherche']) ? trim($_GET['recherche']) : '';
$filtre_groupe = isset($_GET['groupe']) ? trim($_GET['groupe']) : '';
$filtre_qualite = isset($_GET['qualite']) ? trim($_GET['qualite']) : '';

// 2. Construction de la requête SQL pour le tableau de liste
$sql = "SELECT * FROM membres WHERE 1=1";
$params = [];

if (!empty($recherche)) {
    $sql .= " AND (nom LIKE ? OR prenoms LIKE ? OR matricule LIKE ?)";
    $params[] = "%$recherche%";
    $params[] = "%$recherche%";
    $params[] = "%$recherche%";
}

if (!empty($filtre_groupe)) {
    $sql .= " AND groupe_action = ?";
    $params[] = $filtre_groupe;
}

if (!empty($filtre_qualite)) {
    $sql .= " AND qualite = ?";
    $params[] = $filtre_qualite;
}

$sql .= " ORDER BY date_enregistrement DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$membres = $stmt->fetchAll();

// 3. Initialisation sécurisée des compteurs par défaut
$stats = [
    'total_actifs' => 0, 'total_inactifs' => 0, 'total_abandon' => 0, 'total_sorties' => 0,
    'hommes' => 0, 'femmes' => 0, 'jeunes' => 0, 'enfants_total' => 0
];

try {
    // REQUÊTE UNIFIÉE ET SÉCURISÉE POUR LES STATISTIQUES
    $stats_sql = "SELECT 
            SUM(CASE WHEN statut_membre = 'Actif' THEN 1 ELSE 0 END) as total_actifs,
            SUM(CASE WHEN statut_membre = 'Inactif' THEN 1 ELSE 0 END) as total_inactifs,
            SUM(CASE WHEN statut_membre = 'Abandon' THEN 1 ELSE 0 END) as total_abandon,
            SUM(CASE WHEN statut_membre IN ('Départ', 'Voyage', 'Excommunié', 'Décédé') THEN 1 ELSE 0 END) as total_sorties,
            
            SUM(CASE WHEN groupe_action = 'Hommes' AND statut_membre = 'Actif' THEN 1 ELSE 0 END) as hommes,
            SUM(CASE WHEN groupe_action = 'Femmes' AND statut_membre = 'Actif' THEN 1 ELSE 0 END) as femmes,
            SUM(CASE WHEN groupe_action = 'Jeunesses' AND statut_membre = 'Actif' THEN 1 ELSE 0 END) as jeunes,
            
            (SELECT COALESCE(COUNT(*), 0) FROM membres WHERE groupe_action = 'Enfants' AND statut_membre = 'Actif') + 
            (SELECT COALESCE(COUNT(*), 0) FROM enfants) as enfants_total
        FROM membres";
        
    $res = $pdo->query($stats_sql)->fetch(PDO::FETCH_ASSOC);
    
    // Si la table est vide, SUM() renvoie NULL, on s'assure d'avoir des entiers
    if ($res) {
        foreach ($res as $key => $value) {
            $stats[$key] = (int)($value ?? 0);
        }
    }

} catch (PDOException $e) {
    // Note: En production, il vaut mieux logguer l'erreur et afficher un message générique
    $error_stats = "Erreur de calcul des effectifs : " . $e->getMessage();
}

// 4. Définition du titre et inclusion du header juste avant l'affichage HTML
$page_title = "Gestion des membres"; 
require_once '../includes/header.php'; 
?>

<style>
    .border-fuchsia { border-color: #d63384 !important; }
    .text-fuchsia { color: #d63384 !important; }
    .bg-fuchsia { background-color: #d63384 !important; }
    .cursor-pointer { cursor: pointer; }
</style>

<div class="container mt-4">
    
    <?php if (isset($error_stats)): ?>
        <div class='alert alert-danger'><?= htmlspecialchars($error_stats) ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4 d-flex align-items-center" role="alert">
            <i class="fa-solid fa-circle-check fs-4 me-3 text-success"></i>
            <div><?= htmlspecialchars($_SESSION['flash_message']); ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4 d-flex align-items-center" role="alert">
            <i class="fa-solid fa-triangle-exclamation fs-4 me-3 text-danger"></i>
            <div><strong>Erreur :</strong> <?= htmlspecialchars($_SESSION['flash_error']); ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0"><i class="fa-solid fa-users text-primary me-2"></i>Registre des membres</h3>
            <p class="text-muted small m-0">Résultat de la recherche : <strong><?= count($membres) ?></strong> personne(s) répertoriée(s)</p>
        </div>
        <a href="ajouter.php" class="btn btn-primary px-4 shadow-sm fw-bold">
            <i class="fa-solid fa-user-plus me-2"></i> Inscrire un membre
        </a>
    </div>

    <!-- Cartes de statistiques -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white h-100">
                <div class="card-body py-3 d-flex align-items-center">
                    <div class="rounded-circle bg-white bg-opacity-25 p-2 me-3 d-none d-sm-block"><i class="fa-solid fa-user-check fa-lg"></i></div>
                    <div><small class="text-white-50 d-block">Membres Actifs</small><h4 class="fw-bold m-0"><?= $stats['total_actifs'] ?></h4></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm bg-secondary text-white h-100">
                <div class="card-body py-3 d-flex align-items-center">
                    <div class="rounded-circle bg-white bg-opacity-25 p-2 me-3 d-none d-sm-block"><i class="fa-solid fa-user-slash fa-lg"></i></div>
                    <div><small class="text-white-50 d-block">Inactifs</small><h4 class="fw-bold m-0"><?= $stats['total_inactifs'] ?></h4></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm bg-white border-start border-warning border-4 h-100">
                <div class="card-body py-3 d-flex align-items-center">
                    <div class="text-warning me-3 d-none d-sm-block"><i class="fa-solid fa-user-minus fa-xl"></i></div>
                    <div><small class="text-muted d-block">Abandons</small><h4 class="fw-bold m-0 text-dark"><?= $stats['total_abandon'] ?></h4></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm bg-white border-start border-danger border-4 h-100">
                <div class="card-body py-3 d-flex align-items-center">
                    <div class="text-danger me-3 d-none d-sm-block"><i class="fa-solid fa-door-open fa-xl"></i></div>
                    <div><small class="text-muted d-block">Départs & Sorties</small><h4 class="fw-bold m-0 text-dark"><?= $stats['total_sorties'] ?></h4></div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm bg-white border-start border-primary border-4 h-100">
                <div class="card-body py-3 d-flex align-items-center">
                    <div class="text-primary me-3 d-none d-sm-block"><i class="fa-solid fa-mars fa-xl"></i></div>
                    <div><small class="text-muted d-block">Hommes (Actifs)</small><h4 class="fw-bold m-0 text-dark"><?= $stats['hommes'] ?></h4></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm bg-white border-start border-fuchsia border-4 h-100">
                <div class="card-body py-3 d-flex align-items-center">
                    <div class="text-fuchsia me-3 d-none d-sm-block"><i class="fa-solid fa-venus fa-xl"></i></div>
                    <div><small class="text-muted d-block">Femmes (Actifs)</small><h4 class="fw-bold m-0 text-dark"><?= $stats['femmes'] ?></h4></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm bg-white border-start border-success border-4 h-100">
                <div class="card-body py-3 d-flex align-items-center">
                    <div class="text-success me-3 d-none d-sm-block"><i class="fa-solid fa-person-running fa-xl"></i></div>
                    <div><small class="text-muted d-block">Jeunesses (Actifs)</small><h4 class="fw-bold m-0 text-dark"><?= $stats['jeunes'] ?></h4></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm bg-white border-start border-info border-4 h-100">
                <div class="card-body py-3 d-flex align-items-center">
                    <div class="text-info me-3 d-none d-sm-block"><i class="fa-solid fa-baby fa-xl"></i></div>
                    <div><small class="text-muted d-block">Enfants Globaux</small><h4 class="fw-bold m-0 text-dark" title="Fiches Enfants actives + Naissances liées"><?= $stats['enfants_total'] ?></h4></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulaire de filtres -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="index.php" class="row g-3">
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="recherche" class="form-control bg-light border-start-0" placeholder="Nom, prénom, matricule..." value="<?= htmlspecialchars($recherche) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="groupe" class="form-select bg-light">
                        <option value="">Tous les groupes d'action</option>
                        <option value="Hommes" <?= $filtre_groupe === 'Hommes' ? 'selected' : '' ?>>Hommes</option>
                        <option value="Femmes" <?= $filtre_groupe === 'Femmes' ? 'selected' : '' ?>>Femmes</option>
                        <option value="Jeunesses" <?= $filtre_groupe === 'Jeunesses' ? 'selected' : '' ?>>Jeunesses</option>
                        <option value="Enfants" <?= $filtre_groupe === 'Enfants' ? 'selected' : '' ?>>Enfants</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="qualite" class="form-select bg-light">
                        <option value="">Toutes les qualités</option>
                        <option value="Membre" <?= $filtre_qualite === 'Membre' ? 'selected' : '' ?>>Membre</option>
                        <option value="Ami" <?= $filtre_qualite === 'Ami' ? 'selected' : '' ?>>Ami</option>
                        <option value="Enfant" <?= $filtre_qualite === 'Enfant' ? 'selected' : '' ?>>Enfant</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-secondary w-100 fw-bold"><i class="fa-solid fa-filter me-2"></i>Filtrer</button>
                </div>
                <div class="col-md-2 d-grid">
                    <a href="export_membres.php" class="btn btn-outline-success w-100 fw-bold">
                        <i class="fa fa-file-excel me-2"></i> Exporter
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tableau de la liste des membres -->
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary">
                        <tr>
                            <th class="ps-4">Matricule</th>
                            <th>Nom & Prénoms</th>
                            <th>Sexe</th>
                            <th>Téléphone</th>
                            <th>Groupe d'action</th>
                            <th>Qualité</th>
                            <th>Statut Église</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($membres)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fa-regular fa-folder-open fa-3x mb-3 text-secondary opacity-50"></i>
                                    <p class="m-0">Aucun résultat ne correspond à ces critères.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($membres as $m): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-primary"><?= htmlspecialchars($m['matricule']) ?></td>
                                    <td>
                                        <span class="fw-semibold text-dark"><?= htmlspecialchars($m['nom']) ?></span> 
                                        <span class="text-secondary"><?= htmlspecialchars($m['prenoms']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($m['sexe'] === 'Masculin'): ?>
                                            <span class="badge bg-primary-subtle text-primary rounded-pill px-2">M</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger rounded-pill px-2">F</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($m['telephone1'] ?? '') ?: '<span class="text-muted small">Aucun</span>' ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border fw-normal"><?= htmlspecialchars($m['groupe_action']) ?></span>
                                    </td>
                                    <td><span class="text-secondary small"><?= htmlspecialchars($m['qualite']) ?></span></td>
                                    <td>
                                        <?php if ($m['statut_membre'] === 'Actif'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Actif</span>
                                        <?php elseif($m['statut_membre'] === 'Inactif'): ?>
                                            <span class="badge bg-secondary-subtle text-secondary">Inactif</span>
                                        <?php elseif ($m['statut_membre'] === 'Abandon'): ?>
                                            <span class="badge bg-warning-subtle text-warning">Abandon</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger" title="<?= htmlspecialchars($m['statut_membre']) ?>">
                                                <?= htmlspecialchars($m['statut_membre']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group">
                                            <a href="voir.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-light border text-dark" title="Consulter la fiche complète & Gérer la situation">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <a href="modifier.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-light border text-warning" title="Modifier la fiche">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <!-- Correction ici : Changement en bouton propre pour l'action JS -->
                                            <button type="button" onclick="confirmDeletion(<?= $m['id'] ?>)" class="btn btn-sm btn-outline-warning border text-danger cursor-pointer" title="Supprimer définitivement">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                            <a href="fiche.php?id=<?= $m['id'] ?>" class="btn btn-info btn-sm fw-bold" target="_blank" title="Imprimer l'attestation de fiche">
                                                <i class="fa fa-print"></i> Fiche
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDeletion(id) {
        if (confirm("Attention ! Êtes-vous sûr de vouloir supprimer ce membre ? Cette action détruira son historique financier et familial.")) {
            window.location.href = "supprimer.php?id=" + id;
        }
    }
</script>

<?php 
require_once '../includes/footer.php'; 
?>