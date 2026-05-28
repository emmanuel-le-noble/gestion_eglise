<?php
// eglise_db/dashboard/index.php
require_once "../config/database.php";
require_once "../includes/session.php";

// Initialisation sécurisée des compteurs par défaut
$stats = [
    'total_actifs' => 0, 'total_inactifs' => 0, 'total_abandon' => 0, 'total_sorties' => 0,
    'hommes' => 0, 'femmes' => 0, 'jeunes' => 0, 'enfants_total' => 0
];

try {
    // 1. REQUÊTE UNIFIÉE POUR LES STATISTIQUES DES MEMBRES
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
    
    if ($res) {
        foreach ($res as $key => $value) {
            $stats[$key] = (int)($value ?? 0);
        }
    }

    // Calcul du total des membres pour le ratio du taux de baptême
    $total_membres = $stats['total_actifs'] + $stats['total_inactifs'] + $stats['total_abandon'];

    // 2. Statistiques Trésorerie Générale (Église)
    $total_entrees = floatval($pdo->query("SELECT COALESCE(SUM(montant), 0) FROM tresorerie WHERE type_mouvement = 'ENTREE'")->fetchColumn());
    $total_sorties = floatval($pdo->query("SELECT COALESCE(SUM(montant), 0) FROM tresorerie WHERE type_mouvement = 'SORTIE'")->fetchColumn());
    $solde_caisse = $total_entrees - $total_sorties;

    // 3. Extraction et calculs liés à la Mutuelle (Épargne & Encours Prêts)
    $epargne_globale = floatval($pdo->query("SELECT COALESCE(SUM(solde_tontine), 0) FROM mutuelle_comptes")->fetchColumn());
    $prets_actifs = floatval($pdo->query("SELECT COALESCE(SUM((montant_prete + commission) - montant_rembourse), 0) FROM mutuelle_prets WHERE statut != 'SOLDE'")->fetchColumn());
    
    // Calcul de la liquidité disponible en caisse mutuelle
    $liquidite_mutuelle = $epargne_globale - $prets_actifs;

    // 4. Statistiques Spirituelles
    $total_baptises = intval($pdo->query("SELECT COUNT(*) FROM membres WHERE baptise = 1 AND statut_membre = 'Actif'")->fetchColumn());
    
    // Sécurisation contre la division par zéro
    $taux_bapteme = 0;
    if ($total_membres > 0) {
        $taux_bapteme = round(($total_baptises / $total_membres) * 100);
    }

    // 5. Alertes, Suivi & Listes
    $visiteurs_alerte = intval($pdo->query("SELECT COUNT(*) FROM visiteurs WHERE statut_suivi = 'À contacter'")->fetchColumn());
    $evenements_prochains = $pdo->query("SELECT type_evenement, date_evenement FROM evenements WHERE date_evenement >= CURRENT_DATE ORDER BY date_evenement ASC LIMIT 3")->fetchAll();
    $derniers_membres = $pdo->query("SELECT matricule, nom, prenoms, date_enregistrement, groupe_action FROM membres ORDER BY date_enregistrement DESC LIMIT 5")->fetchAll();

} catch (PDOException $e) {
    die("Erreur critique de base de données : " . $e->getMessage());
}

// Tableau des mois en français
$mois_fr = [
    '01' => 'Jan', '02' => 'Fév', '03' => 'Mar', '04' => 'Avr', '05' => 'Mai', '06' => 'Juin',
    '07' => 'Juil', '08' => 'Aoû', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Déc'
];

$page_title = "Tableau de bord"; 
require_once '../includes/header.php'; 
?>

<style>
    .border-fuchsia { border-color: #d63384 !important; }
    .text-fuchsia { color: #d63384 !important; }
    .icon-shape { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; }
    .animate-pulse { animation: pulse 2s infinite; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }
    .card { transition: 0.2s; }
    .card:hover { transform: translateY(-3px); }
</style>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-end">
            <div>
                <h3 class="fw-bold text-dark m-0">Ravi de vous revoir, <?= htmlspecialchars($_SESSION['user_nom'] ?? 'Admin') ?> !</h3>
                <p class="text-muted small mb-0">État de la communauté au <?= date('d/m/Y') ?>.</p>
            </div>
            <a href="../finance/rapport_global.php" class="btn btn-dark shadow-sm btn-sm">
                <i class="fa-solid fa-print me-2"></i>Rapport consolidé
            </a>
        </div>
    </div>

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

    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm bg-success text-white">
                <div class="card-body py-4 h-100">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fa-solid fa-church me-2"></i> <small class="text-white-50 text-uppercase fw-bold">Caisse Église</small>
                    </div>
                    <h3 class="fw-bold m-0"><?= number_format($solde_caisse, 0, ',', ' ') ?> <small class="fs-6">FCFA</small></h3>
                    <hr class="bg-white opacity-25">
                    <div class="d-flex justify-content-between small">
                        <span>Entrées: <?= number_format($total_entrees, 0, ',', ' ') ?> F</span>
                        <span>Sorties: <?= number_format($total_sorties, 0, ',', ' ') ?> F</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm bg-white border-start border-info border-4">
                <div class="card-body py-4 d-flex flex-column justify-content-center">
                    <div class="d-flex align-items-center">
                        <div class="text-info me-3"><i class="fa-solid fa-droplet fa-2xl"></i></div>
                        <div class="flex-grow-1">
                            <small class="text-muted fw-bold">IMPACT SPIRITUEL</small>
                            <h4 class="fw-bold m-0 text-dark"><?= $total_baptises ?> Baptisés</h4>
                            <div class="progress mt-3 mb-2" style="height: 10px;">
                                <div class="progress-bar bg-info" style="width: <?= $taux_bapteme ?>%"></div>
                            </div>
                            <small class="text-muted small"><?= $taux_bapteme ?>% de la communauté</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold text-dark mb-0">Dernières inscriptions</h6>
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
                                <?php if (!empty($derniers_membres)): ?>
                                    <?php foreach ($derniers_membres as $dm): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold small"><?= htmlspecialchars($dm['matricule'] ?? '') ?></td>
                                            <td class="fw-semibold small"><?= htmlspecialchars(($dm['nom'] ?? '') . ' ' . ($dm['prenoms'] ?? '')) ?></td>
                                            <td><span class="badge bg-white text-dark border small fw-normal"><?= htmlspecialchars($dm['groupe_action'] ?: 'Aucun') ?></span></td>
                                            <td class="pe-4 text-end text-muted small">
                                                <?= (!empty($dm['date_enregistrement']) && $dm['date_enregistrement'] !== '0000-00-00') ? date('d/m/y', strtotime($dm['date_enregistrement'])) : '-' ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center text-muted py-3">Aucun membre inscrit récemment.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm border-start border-warning border-4">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-user-clock fa-2x text-warning me-3 <?= $visiteurs_alerte > 0 ? 'animate-pulse' : '' ?>"></i>
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
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold text-danger mb-0 small"><i class="fa-solid fa-calendar-day me-2"></i>AGENDA DU MOIS</h6>
                </div>
                <div class="card-body pt-0">
                    <?php if(!empty($evenements_prochains)): ?>
                        <?php foreach($evenements_prochains as $ev): ?>
                        <?php 
                            $timestamp = strtotime($ev['date_evenement']);
                            $jour = date('d', $timestamp);
                            $num_mois = date('m', $timestamp);
                            $nom_mois = $mois_fr[$num_mois] ?? 'Mois';
                            $heure = date('H:i', $timestamp);
                        ?>
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-danger bg-opacity-10 text-danger rounded p-2 text-center me-3" style="min-width: 45px;">
                                <span class="d-block fw-bold mb-0"><?= $jour ?></span>
                                <small class="text-uppercase" style="font-size: 0.6rem"><?= $nom_mois ?></small>
                            </div>
                            <div class="border-start ps-3">
                                <p class="mb-0 fw-bold small text-dark"><?= htmlspecialchars($ev['type_evenement'] ?? '') ?></p>
                                <small class="text-muted small"><?= ($heure !== '00:00') ? 'Prévu à '.$heure : 'Toute la journée' ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted small py-3 mb-0">Aucun événement à venir.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm bg-dark">
                <div class="card-body d-grid gap-2">
                    <h6 class="text-white-50 small fw-bold mb-2">ACTIONS RAPIDES</h6>
                    <a href="../membres/ajouter.php" class="btn btn-primary text-start small border-0">
                        <i class="fa-solid fa-user-plus me-3"></i> Inscrire fidèle
                    </a>
                    <a href="../tresorerie/ajouter.php" class="btn btn-success text-start small border-0">
                        <i class="fa-solid fa-hand-holding-dollar me-3"></i> Offrandes / Dîmes
                    </a>
                    <a href="../mutuelle/cotisations.php" class="btn btn-info text-start small border-0 text-white">
                        <i class="fa-solid fa-piggy-bank me-3"></i> Verser cotisation
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>