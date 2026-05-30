<?php
// eglise_db/gouvernance/plan_actions.php
require_once "../config/database.php";
require_once "../includes/session.php";

// Sécurisation de la page
securiser_par_module($pdo, 'gouvernance');

$message = "";
$erreur = "";

// 1. Identification de l'année budgétaire active
$annee_actuelle = date('Y');
$stmt_b = $pdo->prepare("SELECT id FROM budgets WHERE annee = ?");
$stmt_b->execute([$annee_actuelle]);
$budget_id = $stmt_b->fetchColumn();

// 2. TRAITEMENT DES FORMULAIRES (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $budget_id) {
    
    // Validation du jeton CSRF (si la fonction existe dans vos includes)
    $csrf_valide = true;
    if (function_exists('verifier_token_csrf') && (!isset($_POST['csrf_token']) || !verifier_token_csrf($_POST['csrf_token']))) {
        $csrf_valide = false;
        $erreur = "Erreur de sécurité : Jeton CSRF invalide.";
    }

    if ($csrf_valide) {
        // --- AJOUT D'UNE ACTION ---
        if (isset($_POST['ajouter_action'])) {
            $objectif = trim($_POST['objectif']);
            $theme = trim($_POST['theme']);
            $activite = trim($_POST['activite']);
            $resultats = trim($_POST['resultats_attendus']);
            $periode = trim($_POST['periode_visee']);
            $budget_estime = (float)$_POST['budget_estime'];
            $statut = $_POST['statut_action'];

            if (!empty($objectif) && !empty($activite)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO plan_actions (budget_id, objectif, theme, activite, resultats_attendus, periode_visee, budget_estime, statut_action) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$budget_id, $objectif, $theme, $activite, $resultats, $periode, $budget_estime, $statut]);
                    $message = "Activité planifiée avec succès !";
                } catch (PDOException $e) {
                    $erreur = "Erreur lors de l'ajout : " . $e->getMessage();
                }
            } else {
                $erreur = "L'objectif et l'activité sont obligatoires.";
            }
        }

        // --- MODIFICATION D'UNE ACTION ---
        if (isset($_POST['modifier_action'])) {
            $action_id = (int)$_POST['action_id'];
            $objectif = trim($_POST['objectif']);
            $theme = trim($_POST['theme']);
            $activite = trim($_POST['activite']);
            $resultats = trim($_POST['resultats_attendus']);
            $periode = trim($_POST['periode_visee']);
            $budget_estime = (float)$_POST['budget_estime'];
            $statut = $_POST['statut_action'];

            if (!empty($objectif) && !empty($activite)) {
                try {
                    $stmt = $pdo->prepare("UPDATE plan_actions SET objectif = ?, theme = ?, activite = ?, resultats_attendus = ?, periode_visee = ?, budget_estime = ?, statut_action = ? WHERE id = ? AND budget_id = ?");
                    $stmt->execute([$objectif, $theme, $activite, $resultats, $periode, $budget_estime, $statut, $action_id, $budget_id]);
                    $message = "L'activité a été mise à jour avec succès !";
                } catch (PDOException $e) {
                    $erreur = "Erreur lors de la modification : " . $e->getMessage();
                }
            } else {
                $erreur = "L'objectif et l'activité ne peuvent pas être vides.";
            }
        }

        // --- SUPPRESSION D'UNE ACTION ---
        if (isset($_POST['supprimer_action'])) {
            $action_id = (int)$_POST['action_id'];

            try {
                $stmt = $pdo->prepare("DELETE FROM plan_actions WHERE id = ? AND budget_id = ?");
                $stmt->execute([$action_id, $budget_id]);
                $message = "L'activité a été retirée du plan d'actions.";
            } catch (PDOException $e) {
                $erreur = "Erreur lors de la suppression : " . $e->getMessage();
            }
        }
    }
}

// 3. RÉCUPÉRATION DES ACTIONS
$actions = [];
if ($budget_id) {
    $stmt = $pdo->prepare("SELECT * FROM plan_actions WHERE budget_id = ? ORDER BY objectif ASC, id ASC");
    $stmt->execute([$budget_id]);
    $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$page_title = "Plan d'actions " . $annee_actuelle;
require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0"><i class="fa-solid fa-calendar-check text-success me-2"></i>Plan d'action annuel (<?= $annee_actuelle ?>)</h3>
            <p class="text-muted small mb-0">Définition des axes stratégiques, thématiques et projets de l'église.</p>
        </div>
        <div>
            <a href="index.php" class="btn btn-outline-success btn-sm fw-bold">
                <i class="fa-solid fa-arrow-left me-1"></i> Tableau de bord
            </a>
        </div>
    </div>

    <?php if(!$budget_id): ?>
        <div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation me-2"></i> Aucun exercice budgétaire trouvé pour l'année <?= $annee_actuelle ?>.</div>
    <?php else: ?>

        <?php if($message): ?> <div class="alert alert-success small mb-3"><i class="fa-solid fa-circle-check me-2"></i><?= $message ?></div> <?php endif; ?>
        <?php if($erreur): ?> <div class="alert alert-danger small mb-3"><i class="fa-solid fa-circle-exclamation me-2"></i><?= $erreur ?></div> <?php endif; ?>

        <!-- FORMULAIRE D'AJOUT -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-plus text-success me-1"></i> Ajouter une ligne au plan d'action</h6>
                <form method="POST">
                    <?php if (function_exists('generer_token_csrf')): ?>
                        <input type="hidden" name="csrf_token" value="<?= generer_token_csrf(); ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="small text-muted fw-bold">Objectif</label>
                            <input type="text" name="objectif" class="form-control form-control-sm" placeholder="Ex: Croissance spirituelle" required>
                        </div>
                        <div class="col-md-5">
                            <label class="small text-muted fw-bold">Thème de référence</label>
                            <input type="text" name="theme" class="form-control form-control-sm" placeholder="Ex: Sois donc le serviteur fidèle, Mat. 24:45">
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted fw-bold">Période visée</label>
                            <input type="text" name="periode_visee" class="form-control form-control-sm" placeholder="Ex: Trimestre 1, Mensuel, Juin...">
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold">Activité</label>
                            <input type="text" name="activite" class="form-control form-control-sm" placeholder="Ex: Enseignements avec les mariés (4 programmés)" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold">Résultats attendus</label>
                            <textarea name="resultats_attendus" class="form-control form-control-sm" rows="1" placeholder="Ex: Renforcer le lien conjugal, amener 150 nouveaux membres..."></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted fw-bold">Budget estimé (FCFA)</label>
                            <input type="number" step="0.01" name="budget_estime" class="form-control form-control-sm" value="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted fw-bold">Statut</label>
                            <select name="statut_action" class="form-select form-select-sm">
                                <option value="En attente">En attente</option>
                                <option value="En cours">En cours</option>
                                <option value="Réalisé">Réalisé</option>
                            </select>
                        </div>
                        <div class="col-md-4 text-end align-self-end">
                            <button type="submit" name="ajouter_action" class="btn btn-success btn-sm px-4 fw-bold">Ajouter au plan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- TABLEAU DES ACTIONS -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-uppercase font-monospace text-secondary">
                            <tr>
                                <th style="width: 15%;" class="ps-3">Objectif</th>
                                <th style="width: 35%;">Activités & Thèmes</th>
                                <th style="width: 25%;">Résultats Attendus</th>
                                <th style="width: 15%;">Période / Budget</th>
                                <th style="width: 10%;" class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($actions)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted small">Aucune activité enregistrée pour le moment.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($actions as $act): ?>
                                    <tr>
                                        <td class="ps-3 align-top pt-3">
                                            <span class="badge bg-secondary-subtle text-secondary fw-bold px-2 py-1" style="font-size: 0.8rem;">
                                                <?= htmlspecialchars($act['objectif']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark mb-1"><?= htmlspecialchars($act['activite']) ?></div>
                                            
                                            <?php if(!empty($act['theme'])): ?>
                                                <div class="text-danger small mb-2 fw-medium" style="font-size: 0.8rem;">
                                                    <i class="fa-solid fa-book-open me-1"></i> Thème : <?= htmlspecialchars($act['theme']) ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if($act['statut_action'] === 'Réalisé'): ?>
                                                <span class="badge bg-success-subtle text-success small" style="font-size: 0.65rem;"><i class="fa-solid fa-check"></i> RÉALISÉ</span>
                                            <?php elseif($act['statut_action'] === 'En cours'): ?>
                                                <span class="badge bg-warning-subtle text-warning small" style="font-size: 0.65rem;"><i class="fa-solid fa-spinner fa-spin"></i> EN COURS</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border small" style="font-size: 0.65rem;">EN ATTENTE</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-secondary small align-top pt-3">
                                            <?= !empty($act['resultats_attendus']) ? nl2br(htmlspecialchars($act['resultats_attendus'])) : '—' ?>
                                        </td>
                                        <td class="align-top pt-3">
                                            <?php if(!empty($act['periode_visee'])): ?>
                                                <div class="text-dark small fw-medium"><i class="fa-regular fa-clock text-muted me-1"></i><?= htmlspecialchars($act['periode_visee']) ?></div>
                                            <?php endif; ?>
                                            <div class="text-muted small mt-1">
                                                <i class="fa-solid fa-wallet me-1"></i><?= number_format($act['budget_estime'], 0, ',', ' ') ?> FCFA
                                            </div>
                                        </td>
                                        <!-- CELLULE BOUTONS ACTIONS -->
                                        <td class="text-end align-top pt-3 pe-3">
                                            <div class="d-flex justify-content-end gap-1">
                                                <button type="button" 
                                                        class="btn btn-xs btn-light border text-primary btn-edit" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#modalModifierAction"
                                                        data-id="<?= $act['id'] ?>"
                                                        data-objectif="<?= htmlspecialchars($act['objectif']) ?>"
                                                        data-theme="<?= htmlspecialchars($act['theme']) ?>"
                                                        data-periode="<?= htmlspecialchars($act['periode_visee']) ?>"
                                                        data-activite="<?= htmlspecialchars($act['activite']) ?>"
                                                        data-resultats="<?= htmlspecialchars($act['resultats_attendus']) ?>"
                                                        data-budget="<?= $act['budget_estime'] ?>"
                                                        data-statut="<?= htmlspecialchars($act['statut_action']) ?>"
                                                        title="Modifier">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-xs btn-light border text-danger btn-delete" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#modalSupprimerAction"
                                                        data-id="<?= $act['id'] ?>"
                                                        data-activite="<?= htmlspecialchars($act['activite']) ?>"
                                                        title="Supprimer">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
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
    <?php endif; ?>
</div>

<!-- ==========================================
    4. MODAL DE MODIFICATION
========================================== -->
<div class="modal fade" id="modalModifierAction" tabindex="-1" aria-labelledby="modalModifierActionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold" id="modalModifierActionLabel"><i class="fa-solid fa-pen-to-square text-primary me-2"></i>Modifier l'activité</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body text-start">
                    <?php if (function_exists('generer_token_csrf')): ?>
                        <input type="hidden" name="csrf_token" value="<?= generer_token_csrf(); ?>">
                    <?php endif; ?>
                    <input type="hidden" name="action_id" id="edit_id">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="small text-muted fw-bold">Objectif *</label>
                            <input type="text" name="objectif" id="edit_objectif" class="form-control" required>
                        </div>
                        <div class="col-md-8">
                            <label class="small text-muted fw-bold">Thème de référence</label>
                            <input type="text" name="theme" id="edit_theme" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="small text-muted fw-bold">Activité *</label>
                            <input type="text" name="activite" id="edit_activite" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="small text-muted fw-bold">Résultats attendus</label>
                            <textarea name="resultats_attendus" id="edit_resultats" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted fw-bold">Période visée</label>
                            <input type="text" name="periode_visee" id="edit_periode" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted fw-bold">Budget estimé (FCFA)</label>
                            <input type="number" step="0.01" name="budget_estime" id="edit_budget" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted fw-bold">Statut</label>
                            <select name="statut_action" id="edit_statut" class="form-select">
                                <option value="En attente">En attente</option>
                                <option value="En cours">En cours</option>
                                <option value="Réalisé">Réalisé</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="modifier_action" class="btn btn-sm btn-success fw-bold">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================
    5. MODAL DE CONFIRMATION DE SUPPRESSION
========================================== -->
<div class="modal fade" id="modalSupprimerAction" tabindex="-1" aria-labelledby="modalSupprimerActionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0 py-2">
                <h6 class="modal-title fw-bold" id="modalSupprimerActionLabel"><i class="fa-solid fa-triangle-exclamation me-2"></i>Confirmation de retrait</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body text-center py-4">
                    <?php if (function_exists('generer_token_csrf')): ?>
                        <input type="hidden" name="csrf_token" value="<?= generer_token_csrf(); ?>">
                    <?php endif; ?>
                    <input type="hidden" name="action_id" id="delete_id">
                    <p class="mb-2 small text-muted text-uppercase fw-bold">Attention</p>
                    <p class="m-0 small">Êtes-vous sûr de vouloir supprimer cette activité du plan annuel ?<br><strong id="delete_activite" class="text-dark"></strong></p>
                </div>
                <div class="modal-footer border-0 justify-content-center pt-0">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="supprimer_action" class="btn btn-sm btn-danger fw-bold">Confirmer la suppression</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================
    6. JAVASCRIPT D'INTERACTION DES MODALS
========================================== -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    
    // Au clic sur Modifier : Récupération des attributs de données HTML5 et remplissage de la modal
    const editButtons = document.querySelectorAll('.btn-edit');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.getAttribute('data-id');
            document.getElementById('edit_objectif').value = this.getAttribute('data-objectif');
            document.getElementById('edit_theme').value = this.getAttribute('data-theme');
            document.getElementById('edit_activite').value = this.getAttribute('data-activite');
            document.getElementById('edit_resultats').value = this.getAttribute('data-resultats');
            document.getElementById('edit_periode').value = this.getAttribute('data-periode');
            document.getElementById('edit_budget').value = this.getAttribute('data-budget');
            document.getElementById('edit_statut').value = this.getAttribute('data-statut');
        });
    });

    // Au clic sur Supprimer : Récupération de l'ID et de l'intitulé pour confirmation
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('delete_id').value = this.getAttribute('data-id');
            document.getElementById('delete_activite').textContent = this.getAttribute('data-activite');
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>