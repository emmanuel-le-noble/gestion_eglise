<?php
// eglise_db/gouvernance/journal.php
require_once "../config/database.php";
require_once "../includes/session.php";

// Toutes les pages du dossier gouvernance contiendront cette ligne :
securiser_par_module($pdo, 'gouvernance');

// Gestion des filtres (Module et Utilisateur)
$filtre_module = $_GET['module'] ?? '';
$filtre_user = $_GET['utilisateur'] ?? '';
$params = [];

$sql_logs = "SELECT * FROM logs_systeme WHERE 1=1";

if (!empty($filtre_module)) {
    $sql_logs .= " AND module = ?";
    $params[] = $filtre_module;
}

if (!empty($filtre_user)) {
    $sql_logs .= " AND utilisateur_nom LIKE ?";
    $params[] = "%" . $filtre_user . "%";
}

$sql_logs .= " ORDER BY date_action DESC LIMIT 150"; // Affichage des 150 dernières actions de l'église

$stmt = $pdo->prepare($sql_logs);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Journal d'activité général"; 
require_once '../includes/header.php'; 
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h3 class="fw-bold text-dark m-0"><i class="fa-solid fa-clock-rotate-left me-2 text-secondary"></i>Journal d'activité général</h3>
            <p class="text-muted small mb-0">Historique global des actions effectuées sur le système de gestion de l'église.</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="index.php" class="btn btn-sm btn-outline-dark shadow-sm">
                <i class="fa-solid fa-house me-2"></i>Tableau de bord
            </a>
        </div>
    </div>

    <!-- Formulaire de recherche et filtres avancés -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-center">
                <!-- Filtre par Module -->
                <div class="col-md-4">
                    <label class="form-label small text-muted mb-1 fw-semibold">Filtrer par module</label>
                    <select name="module" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Tous les modules</option>
                        <option value="membres" <?= $filtre_module === 'membres' ? 'selected' : '' ?>>Fidèles & Membres</option>
                        <option value="tresorerie" <?= $filtre_module === 'tresorerie' ? 'selected' : '' ?>>Trésorerie Église (Caisse)</option>
                        <option value="mutuelle" <?= $filtre_module === 'mutuelle' ? 'selected' : '' ?>>Mutuelle & Tontine</option>
                        <option value="visiteurs" <?= $filtre_module === 'visiteurs' ? 'selected' : '' ?>>Suivi des Visiteurs</option>
                        <option value="evenements" <?= $filtre_module === 'evenements' ? 'selected' : '' ?>>Agenda & Événements</option>
                    </select>
                </div>
                
                <!-- Recherche par Utilisateur -->
                <div class="col-md-5">
                    <label class="form-label small text-muted mb-1 fw-semibold">Rechercher un utilisateur</label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="utilisateur" class="form-control" placeholder="Nom de l'utilisateur..." value="<?= htmlspecialchars($filtre_user) ?>">
                        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </div>
                </div>

                <!-- Réinitialisation -->
                <?php if(!empty($filtre_module) || !empty($filtre_user)): ?>
                    <div class="col-md-3 text-md-end mt-4">
                        <a href="journal.php" class="btn btn-sm btn-link text-danger text-decoration-none small p-0">
                            <i class="fa-solid fa-trash-can me-1"></i>Effacer les filtres
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Tableau de l'historique général -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light small text-secondary">
                        <tr>
                            <th class="ps-4" style="width: 15%;">Date / Heure</th>
                            <th style="width: 20%;">Opérateur</th>
                            <th style="width: 15%;">Module ciblé</th>
                            <th style="width: 20%;">Action menée</th>
                            <th class="pe-4" style="width: 30%;">Détails de l'opération</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted small">
                                    <i class="fa-solid fa-receipt d-block mb-2 fa-3xl opacity-25 text-secondary"></i>
                                    Aucune trace d'activité ne correspond à vos critères.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): 
                                // Assignation dynamique des couleurs de badge selon le module de l'église
                                switch($log['module']) {
                                    case 'membres':
                                        $badge = 'bg-primary-subtle text-primary border border-primary-subtle';
                                        break;
                                    case 'tresorerie':
                                        $badge = 'bg-success-subtle text-success border border-success-subtle';
                                        break;
                                    case 'mutuelle':
                                        $badge = 'bg-info-subtle text-info border border-info-subtle';
                                        break;
                                    case 'visiteurs':
                                        $badge = 'bg-warning-subtle text-warning border border-warning-subtle';
                                        break;
                                    default:
                                        $badge = 'bg-light text-dark border';
                                }
                            ?>
                                <tr>
                                    <!-- Date de l'action -->
                                    <td class="ps-4 small text-muted">
                                        <?= date('d/m/Y à H:i:s', strtotime($log['date_action'])) ?>
                                    </td>
                                    <!-- Utilisateur / IP -->
                                    <td>
                                        <div class="fw-bold small text-dark">
                                            <i class="fa-solid fa-circle-user text-secondary me-2"></i><?= htmlspecialchars($log['utilisateur_nom']) ?>
                                        </div>
                                        <small class="text-muted text-xs">Adresse IP : <?= htmlspecialchars($log['adresse_ip'] ?? '0.0.0.0') ?></small>
                                    </td>
                                    <!-- Module -->
                                    <td>
                                        <span class="badge <?= $badge ?> small text-uppercase px-2 py-1" style="font-size: 0.65rem; font-weight: 600;">
                                            <?= htmlspecialchars($log['module']) ?>
                                        </span>
                                    </td>
                                    <!-- Action -->
                                    <td class="small fw-semibold text-dark">
                                        <?= htmlspecialchars($log['action']) ?>
                                    </td>
                                    <!-- Détails -->
                                    <td class="pe-4 text-muted small text-break">
                                        <?= htmlspecialchars($log['details'] ?? 'Aucune précision complémentaire.') ?>
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

<style>
    .text-xs { font-size: 0.72rem; }
    .table th { font-weight: 600; text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.5px; }
    .table-hover tbody tr:hover { background-color: #fcfcfd; }
</style>

<?php require_once '../includes/footer.php'; ?>