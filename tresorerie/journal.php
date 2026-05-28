<?php
// eglise_db/tresorerie/journal.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'tresorerie');



// --- GESTION DES FILTRES DE PÉRIODE ---
// Par défaut, on affiche le mois en cours
$date_debut = $_GET['date_debut'] ?? date('Y-m-01');
$date_fin = $_GET['date_fin'] ?? date('Y-m-t');
$filtre_type = $_GET['type_mouvement'] ?? '';

// --- ACCUMULATION DES CONDITIONS SQL ---
$conditions = ["t.date_operation BETWEEN ? AND ?"];
$params = [$date_debut, $date_fin];

if (!empty($filtre_type)) {
    $conditions[] = "t.type_mouvement = ?";
    $params[] = $filtre_type;
}

$where_clause = implode(" AND ", $conditions);

// --- REQUÊTE COMPLÈTE ---
// On lie `tresorerie` à `lignes_budget` pour afficher l'intitulé exact de la ligne budgétaire associée
$sql = "SELECT t.*, l.libelle as budget_ligne_nom  FROM tresorerie t LEFT JOIN lignes_budget l ON t.ligne_budget_id = l.id WHERE $where_clause ORDER BY t.date_operation DESC, t.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$flux = $stmt->fetchAll();

// --- CALCUL DES TOTALISATIONS POUR LA PÉRIODE SÉLECTIONNÉE ---
$total_entrees = 0;
$total_sorties = 0;

foreach ($flux as $f) {
    if ($f['type_mouvement'] === 'ENTREE') {
        $total_entrees += (float)$f['montant'];
    } else {
        $total_sorties += (float)$f['montant'];
    }
}
$solde_periode = $total_entrees - $total_sorties;

$page_title = "Journal de caisse"; 
require_once '../includes/header.php'; 
?>

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

    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h3 class="fw-bold text-dark m-0"><i class="fa-solid fa-book text-success me-2"></i>Journal de caisse</h3>
            <p class="text-muted small mb-0">Historique complet des mouvements de caisse réels par intervalle de temps.</p>
        </div>
        <div>
            <a href="rapport_caisse.php" target="_blank" class="btn btn-light border text-dark shadow-sm me-2">
                <i class="fa-solid fa-print me-2"></i>Imprimer le rapport
            </a>
            <a href="ajouter.php" class="btn btn-primary shadow-sm">
                <i class="fa-solid fa-plus me-2"></i>Saisir un flux
            </a>
            <a href="index.php" class="btn btn-secondary btn-sm border"><i class="fa-solid fa-book me-1"></i> Retour à la trésorerie</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4 no-print">
        <div class="card-body bg-light rounded p-3">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="small fw-bold text-muted mb-1">Date de début</label>
                    <input type="date" name="date_debut" class="form-control form-control-sm" value="<?= $date_debut ?>">
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted mb-1">Date de fin</label>
                    <input type="date" name="date_fin" class="form-control form-control-sm" value="<?= $date_fin ?>">
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted mb-1">Filtrer par nature</label>
                    <select name="type_mouvement" class="form-select form-select-sm">
                        <option value="">-- Toutes les opérations --</option>
                        <option value="ENTREE" <?= $filtre_type === 'ENTREE' ? 'selected' : '' ?>>Uniquement les Entrées</option>
                        <option value="SORTIE" <?= $filtre_type === 'SORTIE' ? 'selected' : '' ?>>Uniquement les Sorties</option>
                    </select>
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-success btn-sm fw-bold">
                        <i class="fa-solid fa-filter me-2"></i>Filtrer la caisse
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-success text-white">
                <div class="card-body p-3">
                    <span class="small text-uppercase opacity-75 fw-semibold">Total recettes</span>
                    <h3 class="fw-bold m-0 mt-1"><?= number_format($total_entrees, 0, ',', ' ') ?> F</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-danger text-white">
                <div class="card-body p-3">
                    <span class="small text-uppercase opacity-75 fw-semibold">Total dépenses</span>
                    <h3 class="fw-bold m-0 mt-1"><?= number_format($total_sorties, 0, ',', ' ') ?> F</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm <?= $solde_periode >= 0 ? 'bg-primary' : 'bg-warning' ?> text-white">
                <div class="card-body p-3">
                    <span class="small text-uppercase opacity-75 fw-semibold">Flux net de période</span>
                    <h3 class="fw-bold m-0 mt-1"><?= number_format($solde_periode, 0, ',', ' ') ?> F</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <?php if (empty($flux)): ?>
                <div class="text-center py-5">
                    <i class="fa-solid fa-folder-open text-muted fa-2x mb-2"></i>
                    <p class="text-muted small mb-0">Aucun enregistrement trouvé pour cette période.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Type</th>
                                <th>Ligne budgétaire cible</th>
                                <th>Description / Libellé</th>
                                <th class="no-print text-center">Justificatif</th>
                                <th class="text-end pe-4">Montant (FCFA)</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($flux as $row): ?>
                                <tr>
                                    <td class="ps-4 text-dark font-monospace small">
                                        <?= date('d/m/Y', strtotime($row['date_operation'])) ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $row['type_mouvement'] === 'ENTREE' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> small">
                                            <?= $row['type_mouvement'] === 'ENTREE' ? 'Entrée' : 'Sortie' ?>
                                        </span>
                                    </td>
                                    <td class="fw-semibold text-dark small">
                                        <?= htmlspecialchars($row['budget_ligne_nom'] ?? $row['categorie']) ?>
                                    </td>
                                    <td class="text-muted small text-truncate" style="max-width: 250px;">
                                        <?= htmlspecialchars($row['libelle'] ?? '') ?>
                                    </td>
                                    <td class="no-print text-center">
                                        <?php if(!empty($row['piece_justificative'])): ?>
                                            <a href="../uploads/pieces/<?= $row['piece_justificative'] ?>" target="_blank" class="text-primary small btn btn-light btn-xs py-0 px-2 border">
                                                <i class="fa-solid fa-file-pdf"></i> Ouvrir
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4 fw-bold <?= $row['type_mouvement'] === 'ENTREE' ? 'text-success' : 'text-danger' ?>">
                                        <?= $row['type_mouvement'] === 'ENTREE' ? '+' : '-' ?> <?= number_format($row['montant'], 0, ',', ' ') ?> F
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group btn-group-sm">
                                            <a href="voir.php?id=<?= $row['id'] ?>" class="btn btn-light border btn-sm text-dark px-3" title="Voir"><i class="fa-solid fa-eye"></i></a>
                                            <a href="modifier.php?id=<?= $row['id'] ?>" class="btn btn-light border btn-sm text-primary px-3" title="Modifier"><i class="fa-solid fa-pen"></i></a>
                                            <a onclick="confirmDeletion(<?= $row['id'] ?>)" class="btn btn-outline-warning border btn-sm text-danger px-3" title="Supprimer"><i class="fa-solid fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
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