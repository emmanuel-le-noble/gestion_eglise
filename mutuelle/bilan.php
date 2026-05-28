<?php
// eglise_db/mutuelle/bilan.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'mutuelle');

try {
    // 1. Calcul de l'épargne tontine globale et du nombre de membres actifs
    $sql_base = "SELECT 
        COALESCE(SUM(solde_tontine), 0) as total_tontine,
        (SELECT COUNT(*) FROM mutuelle_comptes WHERE statut = 'ACTIF') as nb_membres
        FROM mutuelle_comptes";
    $res_base = $pdo->query($sql_base)->fetch();

    $total_tontine = floatval($res_base['total_tontine']);
    $nb_membres = intval($res_base['nb_membres']);

    // 2. Calcul du Reste Dehors (Capital + Intérêts restants dus par les membres)
    $sql_dettes = "SELECT COALESCE(SUM((montant_prete + commission) - montant_rembourse), 0) as total_dettes
                   FROM mutuelle_prets 
                   WHERE statut != 'SOLDE'";
    $total_dettes = floatval($pdo->query($sql_dettes)->fetchColumn());

    // 3. Calcul des Gains Générés par la Mutuelle
    // Gain A : Les intérêts/commissions cumulés sur les prêts
    $sql_gains_interets = "SELECT COALESCE(SUM(commission), 0) FROM mutuelle_prets";
    $gains_interets = floatval($pdo->query($sql_gains_interets)->fetchColumn());

    // Gain B : Les frais de tenue de compte prélevés
    $sql_gains_frais = "SELECT COALESCE(SUM(montant), 0) FROM mutuelle_operations WHERE type_operation = 'FRAIS_TENUE'";
    $gains_frais = floatval($pdo->query($sql_gains_frais)->fetchColumn());

    // Total des gains accumulés par la mutuelle
    $total_gains = $gains_interets + $gains_frais;

    // La liquidité totale en caisse correspond à l'épargne disponible augmentée des gains propres de la mutuelle
    $total_caisse = $total_tontine + $total_gains;

    // 4. Intégration du Log : Enregistrement de la consultation du bilan
    if (function_exists('enregistrer_log')) {
        enregistrer_log(
            $pdo, 
            'Consultation Bilan', 
            "Visualisation du bilan financier global de la mutuelle. Caisse théorique : " . number_format($total_caisse, 0, ',', ' ') . " FCFA."
        );
    }

} catch (PDOException $e) {
    // Log de l'erreur technique avant l'arrêt du script
    if (function_exists('enregistrer_log')) {
        enregistrer_log($pdo, 'Erreur Critique', "Échec de chargement du bilan financier. Erreur : " . $e->getMessage());
    }
    echo "Erreur : " . $e->getMessage();
    exit;
}

$page_title = "Bilan financier de la mutuelle"; 
require_once '../includes/header.php'; 
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold m-0">Situation globale de la mutuelle</h3>
            <p class="text-muted small mb-0">Vue consolidée des réserves financières, encours de prêts et gains générés.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="rapport_bilan.php" target="_blank" class="btn btn-outline-dark btn-sm d-print-none d-flex align-items-center">
                <i class="fa-solid fa-print me-2"></i>Imprimer le bilan
            </a>
            <a href="index.php" class="btn btn-light btn-sm border d-flex align-items-center">Retour</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body">
                    <h6 class="small text-uppercase opacity-75">Épargne tontine</h6>
                    <h2 class="fw-bold mb-0"><?= number_format($total_tontine, 0, ',', ' ') ?> <small class="fs-6">F</small></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success text-white">
                <div class="card-body">
                    <h6 class="small text-uppercase opacity-75">Gains Mutuelle cumulés</h6>
                    <h2 class="fw-bold mb-0"><?= number_format($total_gains, 0, ',', ' ') ?> <small class="fs-6">F</small></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-warning text-dark">
                <div class="card-body">
                    <h6 class="small text-uppercase opacity-75">Prêts dehors (+ Intérêts)</h6>
                    <h2 class="fw-bold mb-0"><?= number_format($total_dettes, 0, ',', ' ') ?> <small class="fs-6">F</small></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-dark text-white">
                <div class="card-body">
                    <h6 class="small text-uppercase opacity-75">Membres actifs</h6>
                    <h2 class="fw-bold mb-0"><?= $nb_membres ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-wallet text-muted me-2"></i>Résumé de la trésorerie</h5>
                </div>
                <div class="card-body">
                    <div class="p-4 bg-light rounded-3 text-center mb-3">
                        <span class="d-block text-muted small text-uppercase mb-1">Liquidités totales théoriques en caisse</span>
                        <h1 class="display-5 fw-bold text-dark"><?= number_format($total_caisse, 0, ',', ' ') ?> <small class="fs-4">FCFA</small></h1>
                    </div>
                    
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                            <span>Part tontine (Épargne brute appartenant aux membres)</span>
                            <span class="fw-bold text-dark"><?= number_format($total_tontine, 0, ',', ' ') ?> F</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                            <div>
                                <span>Gains propres de la structure</span>
                                <div class="text-muted text-xs mt-1">
                                    • Intérêts sur prêts : <?= number_format($gains_interets, 0, ',', ' ') ?> F <br>
                                    • Frais de tenue prélevés : <?= number_format($gains_frais, 0, ',', ' ') ?> F
                                </div>
                            </div>
                            <span class="fw-bold text-success">+ <?= number_format($total_gains, 0, ',', ' ') ?> F</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-chart-pie text-muted me-2"></i>Analyse de l'occupation des fonds</h5>
                </div>
                <div class="card-body">
                    <?php 
                        // Calcul du ratio d'endettement (Prêts globaux avec intérêts / Épargne totale tontine)
                        $ratio = ($total_tontine > 0) ? ($total_dettes / $total_tontine) * 100 : 0;
                    ?>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small fw-bold text-secondary">Taux d'occupation des fonds</span>
                            <span class="small fw-bold text-dark"><?= round($ratio, 1) ?>%</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?= min($ratio, 100) ?>%" aria-valuenow="<?= $ratio ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <p class="small text-muted mt-2 mb-0">
                            Indique quel pourcentage de l'épargne globale des membres est actuellement immobilisé dans les crédits en cours de recouvrement.
                        </p>
                    </div>

                    <div class="alert alert-warning small border-0 mt-4 mb-0">
                        <i class="fa-solid fa-circle-info me-2"></i>
                        Le montant de la <strong>Liquidité totale</strong> calculé à gauche représente ce que la banque ou la caisse physique devrait posséder si tous les gains et dépôts y étaient centralisés.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .text-xs {
        font-size: 0.8rem;
    }
</style>

<?php require_once '../includes/footer.php'; ?>