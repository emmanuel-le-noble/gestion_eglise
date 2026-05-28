<?php
// eglise_db/rapports/bilan_global.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'finances');


// Filtres de période
$annee = $_GET['annee'] ?? date('Y');

// --- 1. STATISTIQUES TRÉSORERIE ÉGLISE ---
// Alignement sur ta table 'tresorerie' (Entree / Sortie)
$sqlEglise = "SELECT 
    COALESCE(SUM(CASE WHEN type_mouvement = 'Entree' THEN montant ELSE 0 END), 0) as total_entrees,
    COALESCE(SUM(CASE WHEN type_mouvement = 'Sortie' THEN montant ELSE 0 END), 0) as total_sorties
    FROM tresorerie WHERE YEAR(date_operation) = ?";
$stmtE = $pdo->prepare($sqlEglise);
$stmtE->execute([$annee]);
$eglise = $stmtE->fetch();

// --- 2. STATISTIQUES MUTUELLE ---
// Alignement sur tes tables : mutuelle_operations et mutuelle_prets
// Note : Les gains de l'église en mutuelle sont souvent les 'COMMISSION' (Module 4.2)
$sqlMutuelle = "SELECT 
    COALESCE(SUM(CASE WHEN type_operation = 'REMBOURSEMENT' THEN montant ELSE 0 END), 0) as total_rembourse,
    (SELECT COALESCE(SUM(commission), 0) FROM mutuelle_prets WHERE YEAR(date_pret) = ?) as total_commissions,
    (SELECT COALESCE(SUM(solde_tontine), 0) FROM mutuelle_comptes) as epargne_membres,
    (SELECT COALESCE(SUM(montant_prete - montant_rembourse), 0) FROM mutuelle_prets WHERE statut != 'SOLDE') as prets_dehors
    FROM mutuelle_operations WHERE YEAR(date_op) = ?";

$stmtM = $pdo->prepare($sqlMutuelle);
// On passe l'année deux fois pour la sous-requête et la requête principale
$stmtM->execute([$annee, $annee]);
$mutuelle = $stmtM->fetch();

// --- 3. CALCULS FINAUX ---
$solde_eglise = $eglise['total_entrees'] - $eglise['total_sorties'];
// Le gain total réel = Ce qu'il reste en caisse église + les commissions générées par la mutuelle
$gain_total = $solde_eglise + $mutuelle['total_commissions'];

$page_title = "Rapports financiers globaux"; 
require_once '../includes/header.php'; 
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
        <h3 class="fw-bold"><i class="fa-solid fa-chart-line text-primary me-2"></i>Bilan financier global <?= $annee ?></h3>
        <form class="d-flex gap-2">
            <select name="annee" class="form-select w-auto" onchange="this.form.submit()">
                <?php for($y=date('Y'); $y>=2024; $y--): ?>
                    <option value="<?= $y ?>" <?= $annee == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </form>
    </div>

    <div class="d-none d-print-block text-center mb-4">
        <h2>Rapport financier annuel - <?= $annee ?></h2>
        <hr>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-primary text-white p-4 h-100">
                <small class="text-uppercase fw-bold opacity-75">Solde en caisse de l'église</small>
                <h2 class="fw-bold mb-0"><?= number_format($solde_eglise, 0, ',', ' ') ?> <small class="fs-6">FCFA</small></h2>
                <hr class="my-3 border-white-50">
                <div class="d-flex justify-content-between small">
                    <span>Flux Entrées :</span>
                    <span>+<?= number_format($eglise['total_entrees'], 0, ',', ' ') ?></span>
                </div>
                <div class="d-flex justify-content-between small">
                    <span>Flux Sorties :</span>
                    <span>-<?= number_format($eglise['total_sorties'], 0, ',', ' ') ?></span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-success text-white p-4 h-100">
                <small class="text-uppercase fw-bold opacity-75">Commissions mutuelle (gains)</small>
                <h2 class="fw-bold mb-0"><?= number_format($mutuelle['total_commissions'], 0, ',', ' ') ?> <small class="fs-6">FCFA</small></h2>
                <p class="mt-3 small mb-0">Intérêts générés par les prêts accordés durant l'année.</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-dark text-white p-4 h-100">
                <small class="text-uppercase fw-bold opacity-75">Avoirs réels de l'église</small>
                <h2 class="fw-bold text-warning mb-0"><?= number_format($gain_total, 0, ',', ' ') ?> <small class="fs-6">FCFA</small></h2>
                <p class="mt-3 small opacity-75 italic mb-0">Somme disponible en caisse église + bénéfices mutuelle.</p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold py-3">
                    <i class="fa-solid fa-building-columns text-danger me-2"></i>État des Fonds Mutuelle
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            Épargne totale déposée par les membres
                            <span class="fw-bold"><?= number_format($mutuelle['epargne_membres'], 0, ',', ' ') ?> F</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            Prêts à recouvrer (Argent en circulation)
                            <span class="fw-bold text-danger"><?= number_format($mutuelle['prets_dehors'], 0, ',', ' ') ?> F</span>
                        </li>
                    </ul>
                    <div class="mt-3 p-2 bg-light rounded small text-muted">
                        <i class="fa-solid fa-circle-info me-1"></i> L'épargne des membres est une dette de la mutuelle envers les fidèles.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100 bg-light border-start border-4 border-info">
                <div class="card-body d-flex align-items-center py-4">
                    <div class="ps-3">
                        <i class="fa-solid fa-scale-balanced fa-2x text-info mb-3"></i>
                        <h5 class="fw-bold">Équilibre & Sécurité</h5>
                        <p class="text-muted small mb-0">
                            Ce rapport permet de distinguer ce qui appartient à l'Église (Caisse + Commissions) de ce qui appartient aux membres (Épargne). 
                            <strong>Ne jamais utiliser l'épargne des membres pour les dépenses courantes de l'église.</strong>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-5 d-print-none">
        <a href="bilan_global.php" target="_blank" class="btn btn-dark px-5 shadow-sm">
            <i class="fa-solid fa-print me-2"></i> Imprimer le rapport de synthèse
        </a>
    </div>
</div>

<style>
@media print {
    .d-print-none { display: none !important; }
    .card { border: 1px solid #ddd !important; box-shadow: none !important; }
    body { background: white !important; font-size: 12pt; }
}
</style>

<?php require_once '../includes/footer.php'; ?>