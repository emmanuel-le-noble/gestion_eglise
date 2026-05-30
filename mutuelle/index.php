<?php
// eglise_db/mutuelle/index.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'mutuelle');

try {
    // 1. Récupération des indicateurs de base (Adhérents et Épargne Tontine)
    $sql_base = "SELECT 
        COUNT(id) as total_adherents,
        COALESCE(SUM(solde_tontine), 0) as epargne_globale
        FROM mutuelle_comptes";
    $stats_base = $pdo->query($sql_base)->fetch();

    $total_adherents = intval($stats_base['total_adherents']);
    $epargne_globale = floatval($stats_base['epargne_globale']);

    // 2. Calcul du Reste Dehors (Prêts actifs avec intérêts inclus)
    $sql_prets = "SELECT COALESCE(SUM((montant_prete) - montant_rembourse), 0) as total_prets_dehors
                  FROM mutuelle_prets 
                  WHERE statut NOT IN ('SOLDE', 'ANNULE', 'REJETE')"; // Ajout de sécurité sur les statuts
    $total_prets_dehors = floatval($pdo->query($sql_prets)->fetchColumn());

    // 3. Calcul des Gains Cumulés de la Mutuelle (Commissions prêts valides + Frais de tenue)
    $sql_gains_interets = "SELECT COALESCE(SUM(commission), 0) FROM mutuelle_prets WHERE statut NOT IN ('ANNULE', 'REJETE')";
    $gains_interets = floatval($pdo->query($sql_gains_interets)->fetchColumn());
    
    $gains_frais = floatval($pdo->query("SELECT COALESCE(SUM(montant), 0) FROM mutuelle_operations WHERE type_operation = 'FRAIS_TENUE'")->fetchColumn());
    $total_gains = $gains_interets + $gains_frais;

    // 4. Dernières opérations (Mouvements récents)
    $recentes = $pdo->query("SELECT o.*, m.matricule, m.nom, m.prenoms 
        FROM mutuelle_operations o 
        JOIN mutuelle_comptes mc ON o.compte_id = mc.id 
        JOIN membres m ON mc.membre_id = m.id 
        ORDER BY o.date_op DESC LIMIT 5")->fetchAll();

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
    exit;
}

$page_title = "Gestion de la mutuelle"; 
require_once '../includes/header.php'; 
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark m-0">Mutuelle & Tontine</h2>
        <div class="d-flex gap-2">
            <!-- Bouton Administration Ajouté ici -->
            <a href="admin.php" class="btn btn-outline-dark shadow-sm">
                <i class="fa-solid fa-cogs me-2"></i>Administration
            </a>
            <a href="bilan.php" class="btn btn-dark shadow-sm">
                <i class="fa-solid fa-chart-pie me-2"></i>Bilan global
            </a>
        </div>
    </div>

    <!-- Cartes Indicateurs -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-primary-subtle text-primary rounded-3 p-3 me-3">
                        <i class="fa-solid fa-users-gear fa-xl"></i>
                    </div>
                    <div>
                        <small class="text-muted small mb-1 d-block text-uppercase fw-semibold">Adhérents</small>
                        <h4 class="fw-bold mb-0"><?= $total_adherents ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-success-subtle text-success rounded-3 p-3 me-3">
                        <i class="fa-solid fa-vault fa-xl"></i>
                    </div>
                    <div>
                        <small class="text-muted small mb-1 d-block text-uppercase fw-semibold">Épargne totale</small>
                        <h4 class="fw-bold mb-0"><?= number_format($epargne_globale, 0, ',', ' ') ?> F</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-info-subtle text-info rounded-3 p-3 me-3">
                        <i class="fa-solid fa-chart-line fa-xl"></i>
                    </div>
                    <div>
                        <small class="text-muted small mb-1 d-block text-uppercase fw-semibold">Gains Mutuelle</small>
                        <h4 class="fw-bold mb-0 text-info"><?= number_format($total_gains, 0, ',', ' ') ?> F</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-warning-subtle text-warning rounded-3 p-3 me-3">
                        <i class="fa-solid fa-hand-holding-dollar fa-xl"></i>
                    </div>
                    <div>
                        <small class="text-muted small mb-1 d-block text-uppercase fw-semibold">Prêts actifs</small>
                        <h4 class="fw-bold mb-0 text-warning"><?= number_format($total_prets_dehors, 0, ',', ' ') ?> F</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Principale : Menu & Tableau -->
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-layer-group me-2 text-primary"></i>Menu de gestion</h6>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="membres_mutuelle.php" class="btn btn-outline-primary border w-100 py-3 mb-2">
                                <i class="fa-solid fa-address-book d-block mb-2 text-primary fa-lg"></i>
                                <span class="small fw-bold">Liste adhérents</span>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="adhesion.php" class="btn btn-outline-primary border w-100 py-3 mb-2">
                                <i class="fa-solid fa-user-plus d-block mb-2 text-success fa-lg"></i>
                                <span class="small fw-bold">Inscrire membre</span>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="cotisations.php" class="btn btn-outline-primary border w-100 py-3 mb-2">
                                <i class="fa-solid fa-piggy-bank d-block mb-2 text-info fa-lg"></i>
                                <span class="small fw-bold">Verser cotisation</span>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="nouveau_pret.php" class="btn btn-outline-primary border w-100 py-3 mb-2">
                                <i class="fa-solid fa-money-bill-transfer d-block mb-2 text-warning fa-lg"></i>
                                <span class="small fw-bold">Nouveau prêt</span>
                            </a>
                        </div>
                        <div class="col-16 mt-2">
                            <a href="journal.php" class="btn btn-outline-dark w-100 mb-2 py-2">
                                <i class="fa-solid fa-list-check me-2"></i>Consulter le journal
                            </a>
                        </div>
                        <div class="col-16 mt-2">
                            <a href="prets.php" class="btn btn-outline-dark w-100 mb-2 py-2">
                                <i class="fa-solid fa-clock-rotate-left me-2"></i>Suivi des prêts
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-history me-2 text-secondary"></i>Mouvements récents</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light small text-secondary">
                                <tr>
                                    <th class="ps-3">Date</th>
                                    <th>Membre</th>
                                    <th>Opération</th>
                                    <th class="text-end pe-3">Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($recentes)): ?>
                                    <tr><td colspan="4" class="text-center py-4 text-muted small">Aucun mouvement enregistré.</td></tr>
                                <?php else: ?>
                                    <?php foreach($recentes as $r): ?>
                                    <tr>
                                        <td class="ps-3 small text-muted">
                                            <?= date('d/m/y', strtotime($r['date_op'])) ?>
                                        </td>
                                        <td class="small fw-bold text-dark">
                                            <?= htmlspecialchars($r['matricule'] . ' ' . $r['nom'] . ' ' . $r['prenoms']) ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border small py-1 px-2">
                                                <?= htmlspecialchars($r['type_operation']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-3 fw-bold small text-dark">
                                            <?= number_format($r['montant'], 0, ',', ' ') ?> F
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
    </div>
</div>

<style>
    .icon-box { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; }
</style>

<?php require_once '../includes/footer.php'; ?>