<?php
// eglise_db/mutuelle/admin.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'mutuelle');

$message_success = "";
$message_error = "";

try {
    // 1. TRAITEMENT DU FORMULAIRE : CRÉATION D'UN NOUVEAU POSTE
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_creer_poste'])) {
        $nouveau_poste = trim($_POST['nom_poste']);

        if (!empty($nouveau_poste)) {
            // Vérifier si le poste existe déjà
            $stmt_verif = $pdo->prepare("SELECT COUNT(*) FROM mutuelle_comite WHERE LOWER(poste) = LOWER(?)");
            $stmt_verif->execute([$nouveau_poste]);
            
            if ($stmt_verif->fetchColumn() > 0) {
                $message_error = "Ce poste existe déjà dans le comité.";
            } else {
                $stmt_insert = $pdo->prepare("INSERT INTO mutuelle_comite (poste) VALUES (?)");
                $stmt_insert->execute([$nouveau_poste]);

                if (function_exists('enregistrer_log')) {
                    enregistrer_log($pdo, 'Création Poste Comité', "Ajout du poste de ($nouveau_poste) au comité de gestion par l'utilisateur ID " . $_SESSION['user_id']);
                }
                $message_success = "Le poste a été créé avec succès.";
            }
        } else {
            $message_error = "Le nom du poste ne peut pas être vide.";
        }
    }

    // 2. TRAITEMENT DU FORMULAIRE : AFFECTATION D'UN MEMBRE À UN POSTE
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_comite'])) {
        $poste_id = (int)$_POST['poste_id'];
        $membre_id = !empty($_POST['membre_id']) ? (int)$_POST['membre_id'] : null;

        $stmt_update_comite = $pdo->prepare("UPDATE mutuelle_comite SET membre_id = :membre_id WHERE id = :poste_id");
        $stmt_update_comite->execute([
            'membre_id' => $membre_id,
            'poste_id' => $poste_id
        ]);

        if (function_exists('enregistrer_log')) {
            enregistrer_log(
                $pdo, 
                'Mise à jour Comité Mutuelle', 
                "Modification des attributions pour le poste ID #$poste_id (Membre assigné ID : " . ($membre_id ?? 'Aucun') . ") par l'utilisateur ID " . $_SESSION['user_id']
            );
        }
        $message_success = "L'affectation du poste a bien été enregistrée.";
    }

    // 3. STATISTIQUES FINANCIÈRES CONSOLIDÉES
    $stats = $pdo->query("SELECT 
            COALESCE(SUM(solde_tontine), 0) as epargne_totale,
            COUNT(id) as total_comptes,
            SUM(CASE WHEN statut = 'DESACTIVE' THEN 1 ELSE 0 END) as comptes_clotures
        FROM mutuelle_comptes
    ")->fetch();

    $stats_prets = $pdo->query("SELECT 
            COALESCE(SUM(montant_prete), 0) as total_capital_prete,
            COALESCE(SUM(commission), 0) as total_interets_generes,
            COALESCE(SUM(montant_rembourse), 0) as total_rembourse,
            COUNT(id) as nb_total_prets
        FROM mutuelle_prets
    ")->fetch();

    $capital_dehors = ($stats_prets['total_capital_prete']) - $stats_prets['total_rembourse'];
    $fonds_disponibles = $stats['epargne_totale'] - $stats_prets['total_capital_prete'] + $stats_prets['total_rembourse'];

    // 4. RÉCUPÉRATION DU COMITÉ DE GESTION ACTUEL
    $comite = $pdo->query("SELECT mc.*, m.nom, m.prenoms, m.matricule 
        FROM mutuelle_comite mc 
        LEFT JOIN membres m ON mc.membre_id = m.id 
        ORDER BY mc.id ASC
    ")->fetchAll();

    // 5. RÉCUPÉRATION DE LA LISTE DES MEMBRES ACTIFS
    $liste_membres = $pdo->query("SELECT id, nom, prenoms, matricule FROM membres ORDER BY nom ASC, prenoms ASC")->fetchAll();

    // 6. RÉCUPÉRATION DES LOGS D'AUDIT
    $logs = [];
    $stmt_logs = $pdo->query("SELECT * FROM logs_systeme 
        WHERE module LIKE '%Consultation Suivi Prêts%' OR module LIKE '%Octroi Prêt%' OR module LIKE '%Consultation Profil Mutuelle%' OR module LIKE '%Modification Profil Mutuelle%' OR module LIKE '%Suppression Compte Mutuelle%' OR action LIKE '%Comité%' OR action LIKE '%Poste%'
        ORDER BY date_action DESC LIMIT 5
    ");
    if ($stmt_logs) {
        $logs = $stmt_logs->fetchAll();
    }

    if (function_exists('enregistrer_log')) {
        enregistrer_log($pdo, 'Accès Admin Mutuelle', "L'utilisateur ID " . $_SESSION['user_id'] . " a ouvert le panneau d'administration générale.");
    }

} catch (PDOException $e) {
    die("Erreur critique d'administration : " . $e->getMessage());
}

$page_title = "Administration Mutuelle"; 
require_once '../includes/header.php'; 
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0"><i class="fa-solid fa-sliders text-primary me-2"></i>Administration du Module Mutuelle</h3>
            <p class="text-muted small mb-0">Supervision financière, contrôle du comité de gestion, risques et audit.</p>
        </div>
        <a href="index.php" class="btn btn-light btn-sm border d-flex align-items-center">
            <i class="fa-solid fa-arrow-left me-1"></i> Vue d'ensemble
        </a>
    </div>

    <?php if (!empty($message_success)): ?>
        <div class="alert alert-success alert-dismissible fade show small shadow-sm" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> <?= htmlspecialchars($message_success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($message_error)): ?>
        <div class="alert alert-danger alert-dismissible fade show small shadow-sm" role="alert">
            <i class="fa-solid fa-circle-xmark me-2"></i> <?= htmlspecialchars($message_error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body p-4">
                    <small class="text-white-50 text-uppercase fw-bold small">Trésorerie / fonds disponibles</small>
                    <h2 class="fw-bold m-0 mt-1"><?= number_format($fonds_disponibles, 0, ',', ' ') ?> F CFA</h2>
                    <p class="text-white-50 text-xs mb-0 mt-2">Liquidités réelles en caisse prêtes à être octroyées.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-dark text-white">
                <div class="card-body p-4">
                    <small class="text-white-50 text-uppercase fw-bold small">Épargne totale adhérents</small>
                    <h2 class="fw-bold m-0 mt-1"><?= number_format($stats['epargne_totale'], 0, ',', ' ') ?> F CFA</h2>
                    <p class="text-white-50 text-xs mb-0 mt-2">Cumul des tontines de tous les comptes (<?= (int)$stats['total_comptes'] ?>).</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-warning text-dark">
                <div class="card-body p-4">
                    <small class="text-dark-50 text-uppercase fw-bold small">Encours global des crédits</small>
                    <h2 class="fw-bold m-0 mt-1"><?= number_format($capital_dehors, 0, ',', ' ') ?> F CFA</h2>
                    <p class="text-dark-50 text-xs mb-0 mt-2">Capital restant à recouvrer auprès des emprunteurs.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-wrench text-muted me-2"></i>Outils de configuration & Actions</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="nouveau_pret.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                            <div>
                                <div class="fw-bold text-dark small">Octroyer un nouveau crédit</div>
                                <small class="text-muted">Calculatrice d'intérêts et enregistrement immédiat du contrat.</small>
                            </div>
                            <span class="badge bg-primary rounded-pill"><i class="fa-solid fa-plus"></i></span>
                        </a>
                        <a href="prets.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                            <div>
                                <div class="fw-bold text-dark small">Suivi des relances & Retards</div>
                                <small class="text-muted">Passage automatique des échéances en anomalie et alertes.</small>
                            </div>
                            <span class="badge bg-danger rounded-pill"><i class="fa-solid fa-bell"></i></span>
                        </a>
                        <a href="membres_mutuelle.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                            <div>
                                <div class="fw-bold text-dark small">Auditer les comptes adhérents</div>
                                <small class="text-muted">Activer, désactiver ou exporter les fiches mensuelles des tontines.</small>
                            </div>
                            <span class="badge bg-secondary rounded-pill"><i class="fa-solid fa-users"></i></span>
                        </a>
                        <div class="list-group-item py-3">
                            <div class="fw-bold text-dark small mb-2">Paramètres de taux d'intérêt (Simulation)</div>
                            <div class="input-group input-group-sm max-w-xs">
                                <span class="input-group-text bg-light text-muted small">Taux en vigueur</span>
                                <input type="text" class="form-control font-monospace" value="5.00" disabled>
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-user-shield text-muted me-2"></i>Comité de Gestion de la Mutuelle</h6>
                    <button type="button" class="btn btn-primary btn-xs font-monospace px-2 py-1 fw-bold" style="font-size:0.75rem;" data-bs-toggle="modal" data-bs-target="#modalCreerPoste">
                        <i class="fa-solid fa-plus-circle me-1"></i> Nouveau poste
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-sm small">
                            <thead class="table-light text-secondary text-uppercase text-xs font-monospace">
                                <tr>
                                    <th>Poste / Responsabilité</th>
                                    <th>Occupant actuel</th>
                                    <th class="text-end">Assignation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($comite as $row_comite): ?>
                                    <tr>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($row_comite['poste']) ?></td>
                                        <td>
                                            <?php if ($row_comite['membre_id']): ?>
                                                <span class="fw-bold text-secondary"><?= htmlspecialchars($row_comite['nom'] . ' ' . $row_comite['prenoms']) ?></span>
                                                <div class="text-muted font-monospace" style="font-size: 0.65rem;">Matricule: <?= htmlspecialchars($row_comite['matricule']) ?></div>
                                            <?php else: ?>
                                                <span class="text-danger small font-monospace"><i class="fa-solid fa-triangle-exclamation me-1"></i> Non pourvu</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-outline-secondary btn-xs fw-semibold px-2 py-1" style="font-size: 0.7rem;" data-bs-toggle="modal" data-bs-target="#modalPoste<?= $row_comite['id'] ?>">
                                                <i class="fa-solid fa-user-pen"></i> Placer
                                            </button>

                                            <div class="modal fade text-start" id="modalPoste<?= $row_comite['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered modal-sm">
                                                    <div class="modal-content">
                                                        <form method="POST" action="admin.php">
                                                            <div class="modal-header py-2">
                                                                <h6 class="modal-title fw-bold text-dark"><?= htmlspecialchars($row_comite['poste']) ?></h6>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body py-3">
                                                                <input type="hidden" name="action_comite" value="1">
                                                                <input type="hidden" name="poste_id" value="<?= $row_comite['id'] ?>">
                                                                
                                                                <label class="form-label text-muted small fw-bold">Sélectionner un responsable :</label>
                                                                <select name="membre_id" class="form-select form-select-sm">
                                                                    <option value="">-- Laisser vacant / Retirer --</option>
                                                                    <?php foreach ($liste_membres as $m): ?>
                                                                        <option value="<?= $m['id'] ?>" <?= ($row_comite['membre_id'] == $m['id']) ? 'selected' : '' ?>>
                                                                            <?= htmlspecialchars($m['nom'] . ' ' . $m['prenoms'] . ' [' . $m['matricule'] . ']') ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="modal-footer py-1 justify-content-between">
                                                                <button type="button" class="btn btn-light btn-sm border text-xs" data-bs-dismiss="modal">Annuler</button>
                                                                <button type="submit" class="btn btn-primary btn-sm text-xs">Valider l'affectation</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade text-start" id="modalCreerPoste" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <form method="POST" action="admin.php">
                    <div class="modal-header py-2">
                        <h6 class="modal-title fw-bold text-dark"><i class="fa-solid fa-plus-circle text-primary me-2"></i>Nouveau Poste</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-3">
                        <input type="hidden" name="action_creer_poste" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Intitulé du poste :</label>
                            <input type="text" name="nom_poste" class="form-control form-control-sm" placeholder="Ex: Secrétaire Adjoint, Organisateur..." required>
                        </div>
                    </div>
                    <div class="modal-footer py-1 justify-content-between">
                        <button type="button" class="btn btn-light btn-sm border text-xs" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary btn-sm text-xs">Créer le poste</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-shield-halved text-muted me-2"></i>Piste d'audit & Événements récents</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($logs)): ?>
                        <div class="text-center py-3 text-muted small">Aucune action critique récente répertoriée.</div>
                    <?php else: ?>
                        <div class="timeline-sm">
                            <?php foreach ($logs as $log): ?>
                                <div class="border-left-line ps-3 pb-3 position-relative">
                                    <span class="bullet-dot bg-secondary"></span>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="fw-bold text-dark small font-monospace bg-light border px-2 py-0.5 rounded text-secondary" style="font-size:0.7rem;"><?= htmlspecialchars($log['module']) ?></small>
                                        <small class="text-muted font-monospace text-xs"><?= date('d/m H:i', strtotime($log['date_action'])) ?></small>
                                    </div>
                                    <p class="text-muted text-xs mb-0 mt-1 fw-semibold"><?= htmlspecialchars($log['action']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .max-w-xs { max-width: 250px; }
    .text-xs { font-size: 0.75rem; }
    .border-left-line { border-left: 2px solid #e9ecef; }
    .bullet-dot {
        position: absolute;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        left: -5px;
        top: 5px;
    }
    .btn-xs {
        padding: 0.25rem 0.4rem;
        font-size: 0.75rem;
        border-radius: 0.2rem;
    }
</style>

<?php require_once '../includes/footer.php'; ?>