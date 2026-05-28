<?php
// eglise_db/tresorerie/budget.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'tresorerie');

$message = "";
$erreur = "";
$annee_selectionnee = $_GET['annee'] ?? date('Y');

// ==========================================
// 1. TRAITEMENTS DES FORMULAIRES (POST) & REDIRECTIONS
// ==========================================

// CRÉATION DE L'ENVELOPPE GLOBALE ANNUELLE (INITIALISATION)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['creer_budget_annuel'])) {
    $annee = $_POST['annee'];
    $description = trim($_POST['description']);

    if (!empty($annee)) {
        try {
            $insert_b = $pdo->prepare("INSERT INTO budgets (annee, description, statut) VALUES (?, ?, 'Brouillon')");
            $insert_b->execute([$annee, $description]);
            header("Location: budget.php?annee=" . $annee);
            exit();
        } catch (PDOException $e) {
            $erreur = "Erreur lors de l'initialisation : " . $e->getMessage();
        }
    }
}

// CHANGER LE STATUT DU BUDGET (PASSER DE BROUILLON À VALIDÉ ET INVERSEMENT)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['changer_statut_budget'])) {
    $budget_id = (int)$_POST['budget_id'];
    $nouveau_statut = $_POST['nouveau_statut'];
    
    if ($budget_id > 0 && in_array($nouveau_statut, ['Brouillon', 'Validé'])) {
        try {
            $update_b = $pdo->prepare("UPDATE budgets SET statut = ? WHERE id = ?");
            $update_b->execute([$nouveau_statut, $budget_id]);
            $message = "Le statut du budget a été mis à jour avec succès ($nouveau_statut) !";
        
        } catch (PDOException $e) {
            $erreur = "Erreur lors du changement de statut : " . $e->getMessage();
        }
    }
}

// AJOUT D'UNE LIGNE BUDGÉTAIRE (TOUJOURS AUTORISÉ)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_ligne'])) {
    $budget_id = (int)$_POST['budget_id'];
    $libelle_ligne = trim($_POST['libelle_ligne']);
    $type_ligne = $_POST['type_ligne'];
    $montant_prevu = (float)$_POST['montant_prevu'];

    if ($budget_id > 0 && !empty($libelle_ligne) && $montant_prevu >= 0) {
        try {
            $insert_l = $pdo->prepare("INSERT INTO lignes_budget (budget_id, libelle, type_ligne, montant_prevu) VALUES (?, ?, ?, ?)");
            $insert_l->execute([$budget_id, $libelle_ligne, $type_ligne, $montant_prevu]);
            $message = "Ligne budgétaire ajoutée avec succès !";
        } catch (PDOException $e) {
            $erreur = "Erreur lors de l'ajout : " . $e->getMessage();
        }
    } else {
        $erreur = "Veuillez remplir correctement tous les champs requis.";
    }
}

// TRAITEMENT DE LA MODIFICATION D'UNE LIGNE BUDGÉTAIRE (TOUJOURS AUTORISÉ)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_ligne'])) {
    $ligne_id = (int)$_POST['ligne_id'];
    $libelle_edit = trim($_POST['libelle_edit']);
    $type_edit = $_POST['type_edit'];
    $montant_edit = (float)$_POST['montant_edit'];

    if ($ligne_id > 0 && !empty($libelle_edit) && $montant_edit >= 0) {
        try {
            $update_l = $pdo->prepare("UPDATE lignes_budget SET libelle = ?, type_ligne = ?, montant_prevu = ? WHERE id = ?");
            $update_l->execute([$libelle_edit, $type_edit, $montant_edit, $ligne_id]);
            $message = "Ligne budgétaire mise à jour avec succès !";
        } catch (PDOException $e) {
            $erreur = "Erreur lors de la modification : " . $e->getMessage();
        }
    } else {
        $erreur = "Veuillez remplir correctement tous les champs de modification.";
    }
}

// ==========================================
// 2. REQUÊTES DE LECTURE & CALCULS
// ==========================================

$stmt_b = $pdo->prepare("SELECT * FROM budgets WHERE annee = ?");
$stmt_b->execute([$annee_selectionnee]);
$budget_global = $stmt_b->fetch();

$lignes = [];
$total_recettes = 0;
$total_depenses = 0;

if ($budget_global) {
    $stmt_l = $pdo->prepare("SELECT * FROM lignes_budget WHERE budget_id = ? ORDER BY type_ligne ASC, libelle ASC");
    $stmt_l->execute([$budget_global['id']]);
    $lignes = $stmt_l->fetchAll();

    foreach ($lignes as $l) {
        if ($l['type_ligne'] === 'ENTREE') {
            $total_recettes += (float)$l['montant_prevu'];
        } else {
            $total_depenses += (float)$l['montant_prevu'];
        }
    }
}

// ==========================================
// 3. AFFICHAGE HTML
// ==========================================
$page_title = "Gestion du budget prévisionnel"; 
require_once '../includes/header.php'; 
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0"><i class="fa-solid fa-chart-pie text-primary me-2"></i>Budget prévisionnel</h3>
            <p class="text-muted small mb-0">Gestion et planification des objectifs financiers.</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="rapport.php?annee=<?= $annee_selectionnee ?>" target="_blank" class="btn btn-light border text-dark shadow-sm me-2">
                <i class="fa-solid fa-print me-2"></i>Imprimer le bilan comparatif officiel
            </a>
            <a href="index.php" class="btn btn-light btn-sm border fw-bold"><i class="fa-solid fa-book me-1"></i> Retour à la trésorerie</a>
            
            <form method="GET" class="d-flex align-items-center bg-white p-2 rounded shadow-sm border m-0">
                <label class="me-2 small fw-bold text-muted mb-0">Année :</label>
                <select name="annee" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                    <?php for($y = date('Y')-1; $y <= date('Y')+2; $y++): ?>
                        <option value="<?= $y ?>" <?= $annee_selectionnee == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>
    </div>

    <?php if($message): ?> <div class="alert alert-success small mb-3"><i class="fa-solid fa-circle-check me-2"></i><?= $message ?></div> <?php endif; ?>
    <?php if($erreur): ?> <div class="alert alert-danger small mb-3"><i class="fa-solid fa-circle-exclamation me-2"></i><?= $erreur ?></div> <?php endif; ?>

    <?php if (!$budget_global): ?>
        <div class="card border-0 shadow-sm text-center p-5">
            <i class="fa-solid fa-folder-open text-muted fa-3x mb-3"></i>
            <h5 class="fw-bold">Aucun budget initialisé pour l'année <?= $annee_selectionnee ?></h5>
            <p class="text-muted small">Initialisez le conteneur annuel pour commencer à y ajouter vos lignes d'entrées et sorties.</p>
            <button type="button" class="btn btn-primary btn-sm mx-auto mt-2" data-bs-toggle="modal" data-bs-target="#modalInitialiser">Initialiser l'année</button>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-dark text-white mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small text-uppercase fw-bold">Plafond prévu (<?= $budget_global['annee'] ?>)</span>
                            <span class="badge <?= ($budget_global['statut'] ?? 'Brouillon') === 'Validé' ? 'bg-success' : 'bg-warning text-dark' ?> btn-sm">
                                <?= htmlspecialchars($budget_global['statut'] ?? 'Brouillon') ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between small mb-1"><span>Total recettes :</span><span class="text-success fw-bold">+ <?= number_format($total_recettes, 0, ',', ' ') ?> F</span></div>
                        <div class="d-flex justify-content-between small mb-3"><span>Total dépenses :</span><span class="text-danger fw-bold">- <?= number_format($total_depenses, 0, ',', ' ') ?> F</span></div>
                        <hr class="border-secondary my-2">
                        <div class="d-flex justify-content-between small mb-2">
                            <span>Balance :</span>
                            <span class="fw-bold <?= ($total_recettes - $total_depenses) >= 0 ? 'text-info' : 'text-warning' ?>">
                                <?= number_format($total_recettes - $total_depenses, 0, ',', ' ') ?> F
                            </span>
                        </div>

                        <form method="POST" class="mt-3 pt-2 border-top border-secondary text-center">
                            <input type="hidden" name="budget_id" value="<?= $budget_global['id'] ?>">
                            <?php if (($budget_global['statut'] ?? 'Brouillon') === 'Brouillon'): ?>
                                <input type="hidden" name="nouveau_statut" value="Validé">
                                <button type="submit" name="changer_statut_budget" class="btn btn-success btn-sm w-100 fw-bold">Validé le budget annuelle
                                </button>
                            <?php else: ?>
                                <input type="hidden" name="nouveau_statut" value="Brouillon">
                                <button type="submit" name="changer_statut_budget" class="btn btn-outline-warning btn-sm w-100 fw-bold text-white">
                                    <i class="fa-solid fa-undo me-1"></i> Repasser en mode Brouillon
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3"><h6 class="fw-bold mb-0">Nouvelle rubrique</h6></div>
                    <div class="card-body pt-0">
                        <form method="POST">
                            <input type="hidden" name="budget_id" value="<?= $budget_global['id'] ?>">
                            <div class="mb-3">
                                <label class="small fw-bold text-muted">Nature</label>
                                <select name="type_ligne" class="form-select" required>
                                    <option value="ENTREE">RECETTE (Entrée)</option>
                                    <option value="SORTIE">DÉPENSE (Sortie)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="small fw-bold text-muted">Intitulé</label>
                                <input type="text" name="libelle_ligne" class="form-control" placeholder="Ex: Dîmes, Électricité, Assurances..." required>
                            </div>
                            <div class="mb-3">
                                <label class="small fw-bold text-muted">Montant estimé (FCFA)</label>
                                <input type="number" step="0.01" name="montant_prevu" class="form-control" required>
                            </div>
                            <button type="submit" name="ajouter_ligne" class="btn btn-primary w-100 fw-bold">Ajouter au plan</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3"><h6 class="fw-bold mb-0">Détails des rubriques budgétaires</h6></div>
                    <div class="card-body p-0">
                        <?php if (empty($lignes)): ?>
                            <p class="text-center py-4 text-muted small">Aucune rubrique créée pour le moment.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light small">
                                        <tr>
                                            <th class="ps-3">Type</th>
                                            <th>Rubrique</th>
                                            <th class="text-end">Montant Prévu</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($lignes as $l): ?>
                                            <tr>
                                                <td class="ps-3">
                                                    <span class="badge <?= $l['type_ligne'] === 'ENTREE' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> small">
                                                        <?= $l['type_ligne'] === 'ENTREE' ? 'Recette' : 'Dépense' ?>
                                                    </span>
                                                </td>
                                                <td class="fw-semibold text-dark"><?= htmlspecialchars($l['libelle']) ?></td>
                                                <td class="text-end fw-bold <?= $l['type_ligne'] === 'ENTREE' ? 'text-success' : 'text-danger' ?>">
                                                    <?= number_format($l['montant_prevu'], 0, ',', ' ') ?> F
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#modalModifierLigne"
                                                            data-id="<?= $l['id'] ?>"
                                                            data-libelle="<?= htmlspecialchars($l['libelle'], ENT_QUOTES, 'UTF-8') ?>"
                                                            data-type="<?= $l['type_ligne'] ?>"
                                                            data-montant="<?= $l['montant_prevu'] ?>">
                                                        <i class="fa-solid fa-pencil small"></i>
                                                    </button>
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
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="modalModifierLigne" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Modifier la rubrique</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="ligne_id" id="edit_ligne_id">
                    
                    <div class="mb-3">
                        <label class="small fw-bold mb-1">Nature du flux</label>
                        <select name="type_edit" id="edit_type" class="form-select" required>
                            <option value="ENTREE">RECETTE (Entrée)</option>
                            <option value="SORTIE">DÉPENSE (Sortie)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold mb-1">Intitulé / libellé de la ligne</label>
                        <input type="text" name="libelle_edit" id="edit_libelle" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold mb-1">Montant prévisionnel (FCFA)</label>
                        <input type="number" step="0.01" name="montant_edit" id="edit_montant" class="form-control fw-bold" required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="modifier_ligne" class="btn btn-primary btn-sm fw-bold px-3">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalInitialiser" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Initialiser le budget <?= $annee_selectionnee ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="annee" value="<?= $annee_selectionnee ?>">
                    <p class="text-muted small">Vous créez le conteneur budgétaire pour l'année collective sélectionnée. Par défaut, il sera enregistré comme <strong>Brouillon</strong>.</p>
                    <div class="mb-0">
                        <label class="small fw-bold">Description ou Note de contexte</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Ex: Plan budgétaire aligné sur la croissance..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="creer_budget_annuel" class="btn btn-primary btn-sm fw-bold px-3">Confirmer l'initialisation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalModifier = document.getElementById('modalModifierLigne');
    if (modalModifier) {
        modalModifier.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget; 
            
            var id = button.getAttribute('data-id');
            var libelle = button.getAttribute('data-libelle');
            var type = button.getAttribute('data-type');
            var montant = button.getAttribute('data-montant');

            document.getElementById('edit_ligne_id').value = id;
            document.getElementById('edit_libelle').value = libelle;
            document.getElementById('edit_type').value = type;
            document.getElementById('edit_montant').value = montant;
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>