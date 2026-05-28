<?php
// eglise_db/tresorerie/index.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'tresorerie');


// Gestion des filtres
$date_debut = $_GET['date_debut'] ?? date('Y-m-01'); // Par défaut : début du mois
$date_fin = $_GET['date_fin'] ?? date('Y-m-d');
$ligne_budget_filter = !empty($_GET['ligne_budget_id']) ? (int)$_GET['ligne_budget_id'] : '';

// --- CHARGEMENT DES LIGNES BUDGÉTAIRES POUR LE FILTRE DÉROULANT ---
$lignes_options = $pdo->query("SELECT l.id, l.libelle, b.annee  FROM lignes_budget l  JOIN budgets b ON l.budget_id = b.id  ORDER BY b.annee DESC, l.libelle ASC")->fetchAll();

// 1. Calcul des totaux avec filtres
$stats_query = "SELECT 
    SUM(CASE WHEN type_mouvement = 'ENTREE' THEN montant ELSE 0 END) as total_entrees,
    SUM(CASE WHEN type_mouvement = 'SORTIE' THEN montant ELSE 0 END) as total_sorties
    FROM tresorerie WHERE date_operation BETWEEN ? AND ?";
$params_stats = [$date_debut, $date_fin];

if ($ligne_budget_filter) {
    $stats_query .= " AND ligne_budget_id = ?";
    $params_stats[] = $ligne_budget_filter;
}

$stmt_stats = $pdo->prepare($stats_query);
$stmt_stats->execute($params_stats);
$stats = $stmt_stats->fetch();

$solde = ($stats['total_entrees'] ?? 0) - ($stats['total_sorties'] ?? 0);

// 2. Récupération des transactions filtrées (avec liaisons membres et lignes budgétaires)
$sql = "SELECT t.*, m.nom, m.prenoms, l.libelle as budget_ligne_nom  FROM tresorerie t  LEFT JOIN membres m ON t.membre_id = m.id  LEFT JOIN lignes_budget l ON t.ligne_budget_id = l.id WHERE t.date_operation BETWEEN ? AND ?";
$params_list = [$date_debut, $date_fin];

if ($ligne_budget_filter) {
    $sql .= " AND t.ligne_budget_id = ?";
    $params_list[] = $ligne_budget_filter;
}

$sql .= " ORDER BY t.date_operation DESC, t.id DESC LIMIT 100";
$stmt_list = $pdo->prepare($sql);
$stmt_list->execute($params_list);
$transactions = $stmt_list->fetchAll();

$page_title = "Trésorerie"; 
require_once '../includes/header.php'; 

?>

<!-- eglise_db/tresorerie/index.php (ou membres/index.php) -->

<div class="container mt-4">
    
    <!-- ZONE D'AFFICHAGE DES MESSAGES FLASH -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4 d-flex align-items-center" role="alert">
            <i class="fa-solid fa-circle-check fs-4 me-3 text-success"></i>
            <div>
                <?= htmlspecialchars($_SESSION['flash_message']); ?>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); // On efface le message pour le prochain rechargement ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4 d-flex align-items-center" role="alert">
            <i class="fa-solid fa-triangle-exclamation fs-4 me-3 text-danger"></i>
            <div>
                <strong>Erreur :</strong> <?= htmlspecialchars($_SESSION['flash_error']); ?>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_error']); // On efface l'erreur pour le prochain rechargement ?>
    <?php endif; ?>

    <!-- Le reste de votre tableau ou interface commence ici -->
    <div class="card border-0 shadow-sm">
        <!-- ... votre tableau ... -->
    </div>
</div>

<div class="container mt-4">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form class="row g-3 align-items-end" method="GET">
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">Du</label>
                    <input type="date" name="date_debut" class="form-control" value="<?= $date_debut ?>">
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">Au</label>
                    <input type="date" name="date_fin" class="form-control" value="<?= $date_fin ?>">
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">Rubrique budgétaire</label>
                    <select name="ligne_budget_id" class="form-select">
                        <option value="">Toutes les rubriques</option>
                        <?php foreach($lignes_options as $opt): ?>
                            <option value="<?= $opt['id'] ?>" <?= $ligne_budget_filter == $opt['id'] ? 'selected' : '' ?>>
                                [<?= $opt['annee'] ?>] <?= htmlspecialchars($opt['libelle']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark w-100"><i class="fa-solid fa-filter me-2"></i>Filtrer</button>
                    <a href="export_treso.php?date_debut=<?= $date_debut ?>&date_fin=<?= $date_fin ?>&ligne_budget_id=<?= $ligne_budget_filter ?>" class="btn btn-success w-100"><i class="fa-solid fa-file-excel me-2"></i>Exporter</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-white border-start border-success border-4">
                <div class="card-body py-4">
                    <h6 class="small text-muted text-uppercase mb-1">Entrées période</h6>
                    <h3 class="fw-bold mb-0 text-success"><?= number_format($stats['total_entrees'] ?? 0, 0, ',', ' ') ?> <small class="fs-6 text-muted">F</small></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-white border-start border-danger border-4">
                <div class="card-body py-4">
                    <h6 class="small text-muted text-uppercase mb-1">Sorties période</h6>
                    <h3 class="fw-bold mb-0 text-danger"><?= number_format($stats['total_sorties'] ?? 0, 0, ',', ' ') ?> <small class="fs-6 text-muted">F</small></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body py-4">
                    <h6 class="small text-white-50 text-uppercase mb-1">Solde net</h6>
                    <h3 class="fw-bold mb-0"><?= number_format($solde, 0, ',', ' ') ?> <small class="fs-6 text-white-50">FCFA</small></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="fa-solid fa-book text-success me-2"></i>Journal des transactions (Caisse)
                    </h5>
                    <small class="text-muted">Historique des flux réels de l'église</small>
                </div>
                <div class="d-flex gap-2">
                    <?php 
                    $role_actuel = strtolower($_SESSION['user_role_nom'] ?? ''); 
                    if (in_array($role_actuel, ['admin', 'tresorier', 'trésorier'])): 
                    ?>
                        <a href="budget.php" class="btn btn-outline-primary btn-sm">
                            <i class="fa-solid fa-chart-pie me-1"></i> Plan budgétaire
                        </a>
                    <?php endif; ?>
                    <a href="journal.php" class="btn btn-secondary btn-sm">
                        <i class="fa-solid fa-book me-1"></i> Journal de caisse
                    </a>
                    
                    <a href="ajouter.php" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus me-1"></i> Saisir une opération
                    </a>
                </div>
            </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-3" style="width: 12%;">Date</th>
                            <th style="width: 45%;">Détails / Imputation Budgétaire</th>
                            <th class="text-end" style="width: 18%;">Montant</th>
                            <th class="text-center" style="width: 10%;">Pièce</th>
                            <th class="text-end pe-3" style="width: 15%;">Action</th> 
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($transactions)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted small">Aucune opération enregistrée pour cette période.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($transactions as $t): ?>
                            <tr>
                                <td class="ps-3 small text-secondary font-monospace"><?= date('d/m/Y', strtotime($t['date_operation'])) ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($t['budget_ligne_nom'] ?? $t['categorie']) ?></div>
                                    <small class="text-muted text-truncate d-inline-block" style="max-width: 400px;">
                                        <?php if(!empty($t['nom'])): ?>
                                            <i class="fa-solid fa-user me-1 text-primary"></i> Fidèle : <?= htmlspecialchars($t['nom'].' '.$t['prenoms']) ?>
                                        <?php else: ?>
                                            <?= htmlspecialchars($t['libelle'] ?? '') ?>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td class="text-end fw-bold <?= $t['type_mouvement'] == 'ENTREE' ? 'text-success' : 'text-danger' ?>">
                                    <?= $t['type_mouvement'] == 'ENTREE' ? '+' : '-' ?> <?= number_format($t['montant'], 0, ',', ' ') ?> F
                                </td>
                                <td class="text-center">
                                    <?php if(!empty($t['piece_justificative'])): ?>
                                        <a href="../assets/uploads/pieces/<?= $t['piece_justificative'] ?>" target="_blank" class="btn btn-link btn-sm p-0">
                                            <i class="fa-solid fa-paperclip text-info"></i>
                                        </a>
                                    <?php else: ?>
                                        <i class="fa-solid fa-minus text-muted small"></i>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="voir.php?id=<?= $t['id'] ?>" class="btn btn-light border btn-sm text-dark px-3" title="Voir"><i class="fa-solid fa-eye"></i></a>
                                        <a href="modifier.php?id=<?= $t['id'] ?>" class="btn btn-light border btn-sm text-primary px-3" title="Modifier"><i class="fa-solid fa-pen"></i></a>
                                        <a onclick="confirmDeletion(<?= $t['id'] ?>)" class="btn btn-outline-warning border btn-sm text-danger px-3" title="Supprimer"><i class="fa-solid fa-trash"></i></a>
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
        if (confirm("Êtes-vous sûr de vouloir supprimer cette opération de la Trésorerie ? Cette action est irréversible.")) {
            window.location.href = "supprimer.php?id=" + id;
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>