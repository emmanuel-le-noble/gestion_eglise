<?php
// eglise_db/gouvernance/index.php
require_once "../config/database.php";
require_once "../includes/session.php";

// Toutes les pages du dossier gouvernance contiendront cette ligne :
securiser_par_module($pdo, 'gouvernance');

// ==========================================
// RÉCUPÉRATION DES INDICATEURS CLÉS
// ==========================================

// 1. Nombre total de comités / départements
$total_comites = $pdo->query("SELECT COUNT(*) FROM comites")->fetchColumn();

// 2. Postes vacants (comités sans responsable attitré)
$comites_vacants = $pdo->query("SELECT COUNT(*) FROM comites WHERE responsable_id IS NULL")->fetchColumn();

// 3. Récupération du budget de l'année en cours pour le plan d'action
$annee_actuelle = date('Y');
$budget_annee = $pdo->prepare("SELECT id FROM budgets WHERE annee = ?");
$budget_annee->execute([$annee_actuelle]);
$budget_id = $budget_annee->fetchColumn();

// 4. Statistiques du plan d'action annuel (si un budget existe)
$total_actions = 0;
$actions_realisees = 0;
$budget_actions_estime = 0;
$taux_avancement = 0;

if ($budget_id) {
    $stmt_actions = $pdo->prepare("SELECT COUNT(*) AS total, SUM(budget_estime) AS estimation FROM plan_actions WHERE budget_id = ?");
    $stmt_actions->execute([$budget_id]);
    $res_actions = $stmt_actions->fetch(PDO::FETCH_ASSOC);
    
    if ($res_actions) {
        $total_actions = (int)$res_actions['total'];
        $budget_actions_estime = (float)$res_actions['estimation'];
    }

    // Actions terminées ("Réalisé")
    $stmt_realisees = $pdo->prepare("SELECT COUNT(*) FROM plan_actions WHERE budget_id = ? AND statut_action = 'Réalisé'");
    $stmt_realisees->execute([$budget_id]);
    $actions_realisees = (int)$stmt_realisees->fetchColumn();

    // Calcul du pourcentage d'avancement
    if ($total_actions > 0) {
        $taux_avancement = round(($actions_realisees / $total_actions) * 100);
    }
}

// 5. Nombre de textes juridiques internes
$total_lois = $pdo->query("SELECT COUNT(*) FROM lois_eglise")->fetchColumn();

// 6. Nombre total de comptes utilisateurs actifs dans le système
$total_utilisateurs = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE statut = 'actif'")->fetchColumn();

$page_title = "Tableau de bord de l'administration";
require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="mb-4">
        <h3 class="fw-bold text-dark m-0"><i class="fa-solid fa-shield-halved text-primary me-2"></i>Administration & Pilotage</h3>
        <p class="text-muted small m-0">Suivi stratégique des départements, de la planification annuelle, de la sécurité et des règles communautaires.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2.4 style-col-5">
            <div class="card border-0 shadow-sm bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-bold text-muted uppercase">Départements</span>
                    <div class="bg-light-primary rounded px-2 py-1"><i class="fa-solid fa-sitemap text-primary small"></i></div>
                </div>
                <h3 class="fw-bold m-0 text-dark"><?= $total_comites ?></h3>
                <?php if($comites_vacants > 0): ?>
                    <span class="text-danger fw-semibold" style="font-size: 0.75rem;"><i class="fa-solid fa-triangle-exclamation me-1"></i><?= $comites_vacants ?> Poste(s) vacant(s)</span>
                <?php else: ?>
                    <span class="text-success fw-semibold" style="font-size: 0.75rem;"><i class="fa-solid fa-circle-check me-1"></i>Tous pourvus</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2.4 style-col-5">
            <div class="card border-0 shadow-sm bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-bold text-muted uppercase">Plan d'action (<?= $annee_actuelle ?>)</span>
                    <div class="bg-light-success rounded px-2 py-1"><i class="fa-solid fa-calendar-check text-success small"></i></div>
                </div>
                <h3 class="fw-bold m-0 text-dark"><?= $total_actions ?> <span class="text-muted fs-6 font-normal">projets</span></h3>
                <span class="text-muted" style="font-size: 0.75rem;"><i class="fa-solid fa-check-double me-1 text-success"></i><?= $actions_realisees ?> finalisés</span>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2.4 style-col-5">
            <div class="card border-0 shadow-sm bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-bold text-muted uppercase">Exécution globale</span>
                    <div class="bg-light-warning rounded px-2 py-1"><i class="fa-solid fa-chart-line text-warning small"></i></div>
                </div>
                <h3 class="fw-bold m-0 text-dark"><?= $taux_avancement ?>%</h3>
                <div class="progress mt-1" style="height: 5px;">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $taux_avancement ?>%" aria-valuenow="<?= $taux_avancement ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2.4 style-col-5">
            <div class="card border-0 shadow-sm bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-bold text-muted uppercase">Textes & lois</span>
                    <div class="bg-light-dark rounded px-2 py-1"><i class="fa-solid fa-gavel text-dark small"></i></div>
                </div>
                <h3 class="fw-bold m-0 text-dark"><?= $total_lois ?></h3>
                <span class="text-muted" style="font-size: 0.75rem;"><i class="fa-solid fa-book-open me-1"></i>Articles au registre</span>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2.4 style-col-5">
            <div class="card border-0 shadow-sm bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-bold text-muted uppercase">Accès Actifs</span>
                    <div class="bg-light-info rounded px-2 py-1"><i class="fa-solid fa-user-lock text-info small"></i></div>
                </div>
                <h3 class="fw-bold m-0 text-dark"><?= $total_utilisateurs ?></h3>
                <span class="text-muted" style="font-size: 0.75rem;"><i class="fa-solid fa-users-gear me-1"></i>Comptes configurés</span>
            </div>
        </div>
    </div>

    <h5 class="fw-bold text-secondary text-uppercase mb-3" style="font-size:0.8rem; letter-spacing:0.5px;">Structure & Alignement Annuel</h5>
    <div class="row g-4 mb-5">
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="bg-light text-danger rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px;">
                            <i class="fa-solid fa-sitemap fa-lg"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Organigramme</h5>
                        <p class="text-muted small">Pilotez la structure hiérarchique et descendante de l'église. Configurez l'arbre des chaînes de commandement et d'autorité.</p>
                    </div>
                    <div class="mt-3">
                        <a href="organigramme.php" class="btn btn-danger btn-sm w-100 fw-bold py-2">
                            Voir l'organigramme <i class="fa-solid fa-arrow-right ms-1 small"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="bg-light text-primary rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px;">
                            <i class="fa-solid fa-people-group fa-lg"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Comités & Départements</h5>
                        <p class="text-muted small">Configurez les différents comités de l'église (Diaconat, Jeunesse, Femmes, Écho), attribuez-leur des responsables et gérez la vie interne.</p>
                    </div>
                    <div class="mt-3">
                        <a href="comites.php" class="btn btn-primary btn-sm w-100 fw-bold py-2">
                            Ouvrir les comités <i class="fa-solid fa-arrow-right ms-1 small"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="bg-light text-success rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px;">
                            <i class="fa-solid fa-calendar-check fa-lg"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Planification annuelle</h5>
                        <p class="text-muted small">Inscrivez les grands projets et activités de l'année. Liez chaque action à un budget prévisionnel estimé pour analyser l'effort financier global de l'église.</p>
                    </div>
                    <div class="mt-3">
                        <a href="plan_actions.php" class="btn btn-success btn-sm w-100 fw-bold py-2">
                            Ouvrir le plan d'actions <i class="fa-solid fa-arrow-right ms-1 small"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="bg-light text-dark rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px;">
                            <i class="fa-solid fa-gavel fa-lg"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Textes de lois & statuts</h5>
                        <p class="text-muted small">Consignez de manière immuable la Constitution de la communauté, le règlement intérieur général, ainsi que les chartes morales de comportement.</p>
                    </div>
                    <div class="mt-3">
                        <a href="lois.php" class="btn btn-dark btn-sm w-100 fw-bold py-2">
                            Consulter le registre <i class="fa-solid fa-arrow-right ms-1 small"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h5 class="fw-bold text-secondary text-uppercase mb-3" style="font-size:0.8rem; letter-spacing:0.5px;">Sécurité & Authentifications</h5>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="bg-light text-danger rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px;">
                            <i class="fa-solid fa-key fa-lg"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Rôles & niveaux d'accès</h5>
                        <p class="text-muted small">Déclarez les différents profils d'utilisateurs requis pour l'administration (Admin, Pasteur, Trésorier). C'est le prérequis obligatoire avant l'ouverture des accès.</p>
                    </div>
                    <div class="mt-3">
                        <a href="roles.php" class="btn btn-outline-danger btn-sm w-100 fw-bold py-2">
                            Configurer les rôles <i class="fa-solid fa-chevron-right ms-1 small"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="bg-light text-info rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px;">
                            <i class="fa-solid fa-users-gear fa-lg"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Comptes utilisateurs</h5>
                        <p class="text-muted small">Générez les accès sécurisés des collaborateurs, associez-les à leurs adresses e-mails, attribuez un mot de passe et activez ou révoquez un accès à tout moment.</p>
                    </div>
                    <div class="mt-3">
                        <a href="utilisateurs.php" class="btn btn-outline-info btn-sm w-100 fw-bold py-2">
                            Gérer les comptes <i class="fa-solid fa-chevron-right ms-1 small"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="bg-light text-primary rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px;">
                            <i class="fa-solid fa-cubes fa-lg"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Matrice des Droits</h5>
                        <p class="text-muted small">Gérez les habilitations dynamiques directement depuis l'application. Cochez les modules (Cultes, Finances, Mutuelle...) accessibles pour chaque rôle.</p>
                    </div>
                    <div class="mt-3">
                        <a href="matrice_droits.php" class="btn btn-outline-primary btn-sm w-100 fw-bold py-2">
                            Gérer les autorisations <i class="fa-solid fa-chevron-right ms-1 small"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-light border small mt-4 p-3 d-flex align-items-center gap-2">
        <i class="fa-solid fa-circle-info text-primary"></i>
        <span class="text-muted">Les données financières du plan d'action proviennent du budget prévisionnel. Assurez-vous que le <b>Budget <?= $annee_actuelle ?></b> est validé pour lier correctement vos activités.</span>
    </div>
</div>

<style>
.bg-light-primary { background-color: rgba(13, 110, 253, 0.1); }
.bg-light-success { background-color: rgba(25, 135, 84, 0.1); }
.bg-light-warning { background-color: rgba(255, 193, 7, 0.1); }
.bg-light-dark { background-color: rgba(33, 37, 41, 0.1); }
.bg-light-info { background-color: rgba(13, 202, 240, 0.1); }
.uppercase { font-size: 0.72rem; letter-spacing: 0.5px; text-transform: uppercase;}

/* Hack CSS Bootstrap propre pour répartir équitablement 5 colonnes sur les résolutions larges */
@media (min-width: 1200px) {
    .style-col-5 {
        flex: 0 0 20% !important;
        max-width: 20% !important;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>