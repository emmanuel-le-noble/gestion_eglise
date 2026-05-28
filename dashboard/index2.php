<?php
// eglise_db/dashboard/index.php
require_once "../config/database.php";
require_once "../includes/session.php"; 
$page_title = "Tableau de bord"; 
require_once '../includes/header.php'; 

try {
    // 1. Statistiques Membres par groupe
    $total_membres = $pdo->query("SELECT COUNT(*) FROM membres WHERE statut_membre = 'Actif'")->fetchColumn();
    $total_hommes = $pdo->query("SELECT COUNT(*) FROM membres WHERE groupe_action = 'Hommes' AND statut_membre = 'Actif'")->fetchColumn();
    $total_femmes = $pdo->query("SELECT COUNT(*) FROM membres WHERE groupe_action = 'Femmes' AND statut_membre = 'Actif'")->fetchColumn();
    $total_jeunes = $pdo->query("SELECT COUNT(*) FROM membres WHERE groupe_action = 'Jeunesses' AND statut_membre = 'Actif'")->fetchColumn();
    $total_enfants = $pdo->query("SELECT COUNT(*) FROM membres WHERE groupe_action = 'Enfants' AND statut_membre = 'Actif'")->fetchColumn();
    
    // 2. Statistiques Membres par statut
    $total_Inactif = $pdo->query("SELECT COUNT(*) FROM membres WHERE statut_membre = 'Inactif'")->fetchColumn();
    $total_Abandon = $pdo->query("SELECT COUNT(*) FROM membres WHERE statut_membre = 'Abandon'")->fetchColumn();
    $total_Depart = $pdo->query("SELECT COUNT(*) FROM membres WHERE statut_membre = 'Depart'")->fetchColumn();

    // 3. Statistiques Trésorerie Générale
    $total_entrees = $pdo->query("SELECT COALESCE(SUM(montant), 0) FROM tresorerie WHERE type_mouvement = 'ENTREE'")->fetchColumn();
    $total_sorties = $pdo->query("SELECT COALESCE(SUM(montant), 0) FROM tresorerie WHERE type_mouvement = 'SORTIE'")->fetchColumn();
    $solde_caisse = $total_entrees - $total_sorties;

    // 4. Statistiques Mutuelle
    $stats_mutuelle = $pdo->query("SELECT 
        COALESCE(SUM(solde_tontine), 0) as epargne,
        COALESCE(SUM(solde_social), 0) as social
        FROM mutuelle_comptes")->fetch();
    
    $prets_actifs = $pdo->query("SELECT COALESCE(SUM(montant_prete - montant_rembourse), 0) FROM mutuelle_prets WHERE statut != 'SOLDE'")->fetchColumn();
    $liquidite_mutuelle = ($stats_mutuelle['epargne'] + $stats_mutuelle['social']) - $prets_actifs;

    // 5. Statistiques Spirituelles
    $total_baptises = $pdo->query("SELECT COUNT(*) FROM membres WHERE baptise = 1 AND statut_membre = 'Actif'")->fetchColumn();
    $taux_bapteme = ($total_membres > 0) ? round(($total_baptises / $total_membres) * 100) : 0;

    // 6. Alertes & Suivi
    $visiteurs_alerte = $pdo->query("SELECT COUNT(*) FROM visiteurs WHERE statut_suivi = 'À contacter'")->fetchColumn();
    
    // 7. Prochains événements
    $evenements_prochains = $pdo->query("SELECT type_evenement, date_evenement FROM evenements WHERE date_evenement >= CURRENT_DATE ORDER BY date_evenement ASC LIMIT 3")->fetchAll();

    // 8. Activité récente
    $derniers_membres = $pdo->query("SELECT matricule, nom, prenoms, date_enregistrement, groupe_action FROM membres ORDER BY date_enregistrement DESC LIMIT 5")->fetchAll();

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>

<style>
    :root {
        --glass-bg: rgba(255, 255, 255, 0.06);
        --glass-border: rgba(255, 255, 255, 0.12);
        --fuchsia: #d63384;
    }

    body {
        background-color: #0b0f19; /* Fond ultra sombre pour un contraste optimal */
        background-image: 
            radial-gradient(at 0% 0%, rgba(30, 64, 175, 0.25) 0, transparent 45%), 
            radial-gradient(at 100% 100%, rgba(214, 51, 132, 0.15) 0, transparent 45%);
        color: #f8fafc;
        min-height: 100vh;
        overflow-x: hidden;
    }

    /* Éléments fluides en arrière-plan (Blobs Liquid) */
    .liquid-blob {
        position: fixed;
        width: 450px;
        height: 450px;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(214, 51, 132, 0.15));
        filter: blur(90px);
        border-radius: 50%;
        z-index: -1;
        animation: floatBlob 25s infinite alternate ease-in-out;
    }

    @keyframes floatBlob {
        0% { transform: translate(-10%, -10%) scale(1); }
        100% { transform: translate(15%, 15%) scale(1.1); }
    }

    /* Style général des cartes de type "Verre Dépoli" */
    .card {
        background: var(--glass-bg) !important;
        backdrop-filter: blur(14px) saturate(180%);
        -webkit-backdrop-filter: blur(14px) saturate(180%);
        border: 1px solid var(--glass-border) !important;
        border-radius: 16px !important;
        color: #f8fafc !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .card:hover {
        transform: translateY(-4px);
        background: rgba(255, 255, 255, 0.1) !important;
        border-color: rgba(255, 255, 255, 0.25) !important;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3) !important;
    }

    .card-header {
        background: transparent !important;
        border-bottom: 1px solid var(--glass-border) !important;
    }

    /* Gestion des textes hérités de Bootstrap */
    .text-dark { color: #f8fafc !important; }
    .text-muted { color: #94a3b8 !important; }
    
    /* Tables translucides */
    .table { color: #cbd5e1 !important; }
    .table-hover tbody tr:hover { background-color: rgba(255, 255, 255, 0.04) !important; }
    .bg-light { background-color: rgba(255, 255, 255, 0.03) !important; }
    thead th { border-bottom: 1px solid var(--glass-border) !important; color: #94a3b8 !important; }
    td { border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important; }

    /* Badges et barres de progression */
    .badge.bg-white { background-color: rgba(255, 255, 255, 0.08) !important; color: #e2e8f0 !important; }
    .badge.border { border-color: var(--glass-border) !important; }
    .progress { background-color: rgba(255, 255, 255, 0.1) !important; border-radius: 10px; }

    /* Boutons et éléments interactifs */
    .btn-dark {
        background: rgba(255, 255, 255, 0.08) !important;
        border: 1px solid var(--glass-border) !important;
        color: #fff !important;
    }
    .btn-dark:hover { background: rgba(255, 255, 255, 0.15) !important; }
    .btn-outline-primary { color: #3b82f6 !important; }
    .btn-outline-primary:hover { background: rgba(59, 130, 246, 0.1) !important; }
    
    /* Ronds pour enveloppes d'icônes */
    .icon-wrapper {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    /* Couleurs personnalisées */
    .text-fuchsia { color: var(--fuchsia) !important; }
    .border-fuchsia { border-left: 4px solid var(--fuchsia) !important; }
    .border-glass-primary { border-left: 4px solid #3b82f6 !important; }
    .border-glass-success { border-left: 4px solid #10b981 !important; }
    .border-glass-warning { border-left: 4px solid #f59e0b !important; }
    .border-glass-danger { border-left: 4px solid #ef4444 !important; }
    .border-glass-info { border-left: 4px solid #06b6d4 !important; }

    /* Animations */
    .animate-pulse { animation: pulse 2s infinite ease-in-out; }
    @keyframes pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: .4; transform: scale(1.08); } }
</style>

<!-- Initialisation des formes Liquid en tâche de fond -->
<div class="liquid-blob" style="top: -5%; left: -5%;"></div>
<div class="liquid-blob" style="bottom: -5%; right: -5%; background: linear-gradient(135deg, rgba(214, 51, 132, 0.1), rgba(6, 182, 212, 0.12));"></div>

<div class="container mt-4 position-relative">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-end">
            <div>
                <h3 class="fw-bold text-white m-0">Ravi de vous revoir, <?= htmlspecialchars($_SESSION['user_nom'] ?? 'Admin') ?> !</h3>
                <p class="text-muted small mb-0">État de la communauté au <?= date('d/m/Y') ?>.</p>
            </div>
            <a href="../rapports/bilan_global.php" class="btn btn-dark shadow-sm btn-sm">
                <i class="fa-solid fa-print me-2 text-muted"></i>Rapport Consolider
            </a>
        </div>
    </div>

    <!-- LIGNE 1 : Statuts globaux des fidèles -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-glass-primary">
                <div class="card-body py-4 d-flex align-items-center">
                    <div class="icon-wrapper me-3" style="background: rgba(59, 130, 246, 0.15);"><i class="fa-solid fa-user-check fa-lg text-primary"></i></div>
                    <div><small class="text-muted d-block">Membres Actifs</small><h4 class="fw-bold m-0"><?= $total_membres ?></h4></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-glass-secondary" style="border-left: 4px solid #64748b !important;">
                <div class="card-body py-4 d-flex align-items-center">
                    <div class="icon-wrapper me-3" style="background: rgba(148, 163, 184, 0.15);"><i class="fa-solid fa-user-slash fa-lg text-muted"></i></div>
                    <div><small class="text-muted d-block">Membres Inactifs</small><h4 class="fw-bold m-0"><?= $total_Inactif ?></h4></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-glass-warning">
                <div class="card-body py-4 d-flex align-items-center">
                    <div class="icon-wrapper me-3" style="background: rgba(245, 158, 11, 0.15);"><i class="fa-solid fa-user-minus fa-lg text-warning"></i></div>
                    <div><small class="text-muted d-block">Abandons</small><h4 class="fw-bold m-0"><?= $total_Abandon ?></h4></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-glass-danger">
                <div class="card-body py-4 d-flex align-items-center">
                    <div class="icon-wrapper me-3" style="background: rgba(239, 68, 68, 0.15);"><i class="fa-solid fa-door-open fa-lg text-danger"></i></div>
                    <div><small class="text-muted d-block">Départs / Sorties</small><h4 class="fw-bold m-0"><?= $total_Depart ?></h4></div>
                </div>
            </div>
        </div>
    </div>

    <!-- LIGNE 2 : Groupes d'actions -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-glass-primary h-100">
                <div class="card-body py-4 d-flex align-items-center">
                    <div class="icon-wrapper me-3" style="background: rgba(59, 130, 246, 0.1);"><i class="fa-solid fa-mars fa-lg text-primary"></i></div>
                    <div><small class="text-muted d-block">Hommes</small><h4 class="fw-bold m-0"><?= $total_hommes ?></h4></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-fuchsia h-100">
                <div class="card-body py-4 d-flex align-items-center">
                    <div class="icon-wrapper me-3" style="background: rgba(214, 51, 132, 0.1);"><i class="fa-solid fa-venus fa-lg text-fuchsia"></i></div>
                    <div><small class="text-muted d-block">Femmes</small><h4 class="fw-bold m-0"><?= $total_femmes ?></h4></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-glass-success h-100">
                <div class="card-body py-4 d-flex align-items-center">
                    <div class="icon-wrapper me-3" style="background: rgba(16, 185, 129, 0.1);"><i class="fa-solid fa-person-running fa-lg text-success"></i></div>
                    <div><small class="text-muted d-block">Jeunes</small><h4 class="fw-bold m-0"><?= $total_jeunes ?></h4></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-glass-warning h-100">
                <div class="card-body py-4 d-flex align-items-center">
                    <div class="icon-wrapper me-3" style="background: rgba(245, 158, 11, 0.1);"><i class="fa-solid fa-baby fa-lg text-warning"></i></div>
                    <div><small class="text-muted d-block">Enfants</small><h4 class="fw-bold m-0"><?= $total_enfants ?></h4></div>
                </div>
            </div>
        </div>
    </div>

    <!-- LIGNE 3 : Caisse, Épargne & Impact Spirituel -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm border-glass-success h-100">
                <div class="card-body py-4">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fa-solid fa-church me-2 text-success"></i> <small class="text-muted text-uppercase fw-bold">Caisse Église</small>
                    </div>
                    <h2 class="fw-bold m-0 text-white"><?= number_format($solde_caisse, 0, ',', ' ') ?> <small class="fs-6 text-muted">FCFA</small></h2>
                    <hr style="border-color: var(--glass-border)">
                    <div class="d-flex justify-content-between small text-muted">
                        <span>Entrées: <strong class="text-success"><?= number_format($total_entrees, 0, ',', ' ') ?> F</strong></span>
                        <span>Sorties: <strong class="text-danger"><?= number_format($total_sorties, 0, ',', ' ') ?> F</strong></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm border-glass-info h-100">
                <div class="card-body py-4">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fa-solid fa-vault me-2 text-info"></i> <small class="text-muted text-uppercase fw-bold">Trésorerie Mutuelle</small>
                    </div>
                    <h2 class="fw-bold m-0 text-white"><?= number_format($liquidite_mutuelle, 0, ',', ' ') ?> <small class="fs-6 text-muted">FCFA</small></h2>
                    <hr style="border-color: var(--glass-border)">
                    <div class="d-flex justify-content-between small text-muted">
                        <span>Épargne: <strong class="text-info"><?= number_format($stats_mutuelle['epargne'], 0, ',', ' ') ?> F</strong></span>
                        <span>Prêts: <strong class="text-warning"><?= number_format($prets_actifs, 0, ',', ' ') ?> F</strong></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card border-0 shadow-sm border-glass-info">
                <div class="card-body py-4">
                    <div class="d-flex align-items-center">
                        <div class="icon-wrapper me-3" style="background: rgba(6, 182, 212, 0.15);"><i class="fa-solid fa-droplet fa-xl text-info"></i></div>
                        <div class="flex-grow-1">
                            <small class="text-muted fw-bold d-block">IMPACT SPIRITUEL</small>
                            <h5 class="fw-bold m-0 text-white"><?= $total_baptises ?> Baptisés</h5>
                            <div class="progress mt-2" style="height: 6px;">
                                <div class="progress-bar bg-info" style="width: <?= $taux_bapteme ?>%"></div>
                            </div>
                            <small class="text-muted small mt-1 d-block"><?= $taux_bapteme ?>% de la communauté active</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- LIGNE 4 : Tableaux, Visiteurs et Agenda -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header border-0 py-3 d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold text-white mb-0">Dernières Inscriptions</h6>
                    <a href="../membres/index.php" class="btn btn-sm btn-outline-primary border-0">Voir tout</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light small">
                                <tr>
                                    <th class="ps-4">Matricule</th>
                                    <th>Nom & Prénoms</th>
                                    <th>Groupe</th>
                                    <th class="pe-4 text-end">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($derniers_membres as $dm): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold small text-primary"><?= htmlspecialchars($dm['matricule']) ?></td>
                                        <td class="fw-semibold small"><?= htmlspecialchars($dm['nom'] . ' ' . $dm['prenoms']) ?></td>
                                        <td><span class="badge bg-white text-muted border small fw-normal"><?= htmlspecialchars($dm['groupe_action']) ?></span></td>
                                        <td class="pe-4 text-end text-muted small"><?= date('d/m/y', strtotime($dm['date_enregistrement'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm border-glass-warning">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="me-3 <?= $visiteurs_alerte > 0 ? 'animate-pulse' : '' ?>">
                            <i class="fa-solid fa-user-clock fa-2x text-warning"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold"><?= $visiteurs_alerte ?> Nouveau(x) visiteur(s)</h6>
                            <small class="text-muted small">Suivi spirituel et appels à planifier.</small>
                        </div>
                    </div>
                    <a href="../visiteurs/index.php" class="btn btn-warning btn-sm fw-bold px-3">Suivre</a>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header border-0 py-3">
                    <h6 class="fw-bold text-danger mb-0 small"><i class="fa-solid fa-calendar-day me-2"></i>AGENDA DU MOIS</h6>
                </div>
                <div class="card-body pt-3">
                    <?php if(!empty($evenements_prochains)): ?>
                        <?php foreach($evenements_prochains as $ev): ?>
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-danger bg-opacity-10 text-danger rounded p-2 text-center me-3" style="min-width: 45px; border: 1px solid rgba(239, 68, 68, 0.2)">
                                <span class="d-block fw-bold mb-0"><?= date('d', strtotime($ev['date_evenement'])) ?></span>
                                <small class="text-uppercase" style="font-size: 0.6rem"><?= date('M', strtotime($ev['date_evenement'])) ?></small>
                            </div>
                            <div class="border-start border-secondary ps-3">
                                <p class="mb-0 fw-bold small text-white"><?= htmlspecialchars($ev['type_evenement']) ?></p>
                                <small class="text-muted small">Prévu à <?= date('H:i', strtotime($ev['date_evenement'])) ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted small py-2 m-0">Aucun événement à venir.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="background: rgba(255,255,255,0.03) !important;">
                <div class="card-body d-grid gap-2">
                    <h6 class="text-muted small fw-bold mb-2">ACTIONS RAPIDES</h6>
                    <a href="../membres/ajouter.php" class="btn btn-dark text-start small border-0 py-2">
                        <i class="fa-solid fa-user-plus me-3 text-primary"></i> Inscrire Fidèle
                    </a>
                    <a href="../tresorerie/ajouter.php" class="btn btn-dark text-start small border-0 py-2">
                        <i class="fa-solid fa-hand-holding-dollar me-3 text-success"></i> Offrandes / Dîmes
                    </a>
                    <a href="../mutuelle/operation.php" class="btn btn-dark text-start small border-0 py-2">
                        <i class="fa-solid fa-piggy-bank me-3 text-info"></i> Verser Cotisation
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>