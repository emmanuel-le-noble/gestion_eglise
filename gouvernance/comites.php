<?php
// eglise_db/gouvernance/comites.php
require_once "../config/database.php";
require_once "../includes/session.php";

// Toutes les pages du dossier gouvernance contiendront cette ligne :
securiser_par_module($pdo, 'gouvernance');

$message = "";
$erreur = "";

// ==========================================
// 1. TRAITEMENT DES FORMULAIRES (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // validation CSRF commune (si la fonction existe dans vos includes)
    $csrf_valide = true;
    if (function_exists('verifier_token_csrf') && (!isset($_POST['csrf_token']) || !verifier_token_csrf($_POST['csrf_token']))) {
        $csrf_valide = false;
        $erreur = "Erreur de sécurité : Jeton CSRF invalide.";
    }

    if ($csrf_valide) {
        // --- AJOUT D'UN COMITÉ ---
        if (isset($_POST['ajouter_comite'])) {
            $nom = trim($_POST['nom']);
            $description = trim($_POST['description']);
            $responsable_id = !empty($_POST['responsable_id']) ? (int)$_POST['responsable_id'] : null;
            $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            $ordre = (int)$_POST['ordre_affichage'];

            if (!empty($nom)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO comites (nom, description, responsable_id, parent_id, ordre_affichage) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$nom, $description, $responsable_id, $parent_id, $ordre]);
                    $message = "Le comité / département a été créé avec succès !";
                } catch (PDOException $e) {
                    $erreur = "Erreur lors de la création du comité : " . $e->getMessage();
                }
            } else {
                $erreur = "Le nom du comité est obligatoire.";
            }
        }

        // --- MODIFICATION D'UN COMITÉ ---
        if (isset($_POST['modifier_comite'])) {
            $id = (int)$_POST['comite_id'];
            $nom = trim($_POST['nom']);
            $description = trim($_POST['description']);
            $responsable_id = !empty($_POST['responsable_id']) ? (int)$_POST['responsable_id'] : null;
            $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            $ordre = (int)$_POST['ordre_affichage'];

            // Anti-boucle : Un comité ne peut pas se définir lui-même comme son propre parent
            if ($parent_id === $id) {
                $erreur = "Un comité ne peut pas être son propre groupe parent.";
            } elseif (!empty($nom)) {
                try {
                    $stmt = $pdo->prepare("UPDATE comites SET nom = ?, description = ?, responsable_id = ?, parent_id = ?, ordre_affichage = ? WHERE id = ?");
                    $stmt->execute([$nom, $description, $responsable_id, $parent_id, $ordre, $id]);
                    $message = "Le comité a été mis à jour avec succès !";
                } catch (PDOException $e) {
                    $erreur = "Erreur lors de la modification du comité : " . $e->getMessage();
                }
            } else {
                $erreur = "Le nom du comité ne peut pas être vide.";
            }
        }

        // --- SUPPRESSION D'UN COMITÉ ---
        if (isset($_POST['supprimer_comite'])) {
            $id = (int)$_POST['comite_id'];

            try {
                // Optionnel : On remet à NULL le parent_id des sous-comités enfants pour éviter de briser la base
                $stmt_enfants = $pdo->prepare("UPDATE comites SET parent_id = NULL WHERE parent_id = ?");
                $stmt_enfants->execute([$id]);

                $stmt = $pdo->prepare("DELETE FROM comites WHERE id = ?");
                $stmt->execute([$id]);
                $message = "Le comité a été supprimé avec succès !";
            } catch (PDOException $e) {
                $erreur = "Erreur lors de la suppression du comité : " . $e->getMessage();
            }
        }
    }
}

// ==========================================
// 2. RÉCUPÉRATION DES DONNÉES
// ==========================================

// Liste de tous les membres pour le sélecteur de responsables
$membres = $pdo->query("SELECT id, matricule, nom, prenoms FROM membres WHERE statut_membre = 'Actif' ORDER BY nom ASC, prenoms ASC")->fetchAll();

// Liste de tous les comités pour le sélecteur de parents
$tous_comites = $pdo->query("SELECT id, nom FROM comites ORDER BY nom ASC")->fetchAll();

// Liste complète avec jointure pour l'affichage des comités
$sql = "SELECT c1.*, 
               CONCAT(m.nom, ' ', m.prenoms) AS nom_responsable, 
               c2.nom AS nom_parent
        FROM comites c1
        LEFT JOIN membres m ON c1.responsable_id = m.id
        LEFT JOIN comites c2 ON c1.parent_id = c2.id
        ORDER BY c1.parent_id ASC, c1.ordre_affichage ASC, c1.nom ASC";
$comites_affichage = $pdo->query($sql)->fetchAll();

$page_title = "Gestion des comités & départements";
require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0"><i class="fa-solid fa-people-group text-primary me-2"></i>Comités & Départements</h3>
            <p class="text-muted small mb-0">Gestion des ministères, équipes internes de l'église et affectation de leurs responsables.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-primary btn-sm fw-bold">
                <i class="fa-solid fa-arrow-left me-1"></i> Tableau de bord
            </a>
            <a href="../tresorerie/budget.php" class="btn btn-light btn-sm border fw-bold">
                <i class="fa-solid fa-arrow-right me-1"></i> Aller aux budgets
            </a>
        </div>
    </div>

    <?php if($message): ?> <div class="alert alert-success small mb-3"><i class="fa-solid fa-circle-check me-2"></i><?= $message ?></div> <?php endif; ?>
    <?php if($erreur): ?> <div class="alert alert-danger small mb-3"><i class="fa-solid fa-circle-exclamation me-2"></i><?= $erreur ?></div> <?php endif; ?>

    <div class="row g-4">
        <!-- FORMULAIRE D'AJOUT -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0"><i class="fa-solid fa-plus text-success me-2"></i>Nouveau département / comité</h6>
                </div>
                <div class="card-body pt-0">
                    <form method="POST">
                        <?php if (function_exists('generer_token_csrf')): ?>
                            <input type="hidden" name="csrf_token" value="<?= generer_token_csrf(); ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">Nom du comité *</label>
                            <input type="text" name="nom" class="form-control" placeholder="Ex: Département de la Jeunesse, Comité des Diacres" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="small fw-bold text-muted">Rattaché à (groupe parent)</label>
                            <select name="parent_id" class="form-select">
                                <option value="">-- Aucun (Groupe principal) --</option>
                                <?php foreach($tous_comites as $tc): ?>
                                    <option value="<?= $tc['id'] ?>"><?= htmlspecialchars($tc['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text small" style="font-size:0.75rem;">Permet d'associer un sous-comité à une entité parente.</div>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">Membre responsable / Leader</label>
                            <select name="responsable_id" class="form-select">
                                <option value="">-- Non défini / À pourvoir --</option>
                                <?php foreach($membres as $m): ?>
                                    <option value="<?= $m['id'] ?>">
                                        <?= htmlspecialchars($m['nom'] . ' ' . $m['prenoms']) ?> (<?= htmlspecialchars($m['matricule']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">Ordre d'affichage (priorité)</label>
                            <input type="number" name="ordre_affichage" class="form-control" value="0">
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">Missions / Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Description brève des activités et responsabilités de ce groupe..."></textarea>
                        </div>

                        <button type="submit" name="ajouter_comite" class="btn btn-primary w-100 fw-bold">Créer le comité</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- TABLEAU D'AFFICHAGE AVEC ACTIONS -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0"><i class="fa-solid fa-list-check text-primary me-2"></i>Registre des départements & Comités</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($comites_affichage)): ?>
                        <p class="text-center py-5 text-muted small m-0">Aucun comité créé pour le moment. Utilisez le formulaire de gauche.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th class="ps-3">Nom du comité</th>
                                        <th>Rattaché à</th>
                                        <th>Responsable officiel</th>
                                        <th class="text-center">Ordre</th>
                                        <th class="text-end pe-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($comites_affichage as $comite): ?>
                                        <tr>
                                            <td class="ps-3">
                                                <?php if ($comite['parent_id']): ?>
                                                    <span class="text-muted me-2">—</span>
                                                    <span class="fw-normal text-secondary"><?= htmlspecialchars($comite['nom']) ?></span>
                                                <?php else: ?>
                                                    <span class="fw-bold text-dark"><i class="fa-solid fa-users text-muted small me-1"></i> <?= htmlspecialchars($comite['nom']) ?></span>
                                                <?php endif; ?>
                                                
                                                <?php if(!empty($comite['description'])): ?>
                                                    <div class="text-muted small truncated-text" style="font-size: 0.8rem;" title="<?= htmlspecialchars($comite['description']) ?>">
                                                        <?= htmlspecialchars(substr($comite['description'], 0, 60)) ?><?= strlen($comite['description']) > 60 ? '...' : '' ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($comite['nom_parent']): ?>
                                                    <span class="badge bg-light text-secondary border"><?= htmlspecialchars($comite['nom_parent']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted small"><em>Groupe racine</em></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($comite['nom_responsable']): ?>
                                                    <div class="fw-semibold text-dark"><i class="fa-solid fa-user-tie text-muted me-1 small"></i> <?= htmlspecialchars($comite['nom_responsable']) ?></div>
                                                <?php else: ?>
                                                    <span class="text-danger small fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i> Vacant</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center text-muted small">
                                                <?= $comite['ordre_affichage'] ?>
                                            </td>
                                            <!-- BOUTONS ACTIONS ENRICHIS -->
                                            <td class="text-end pe-3">
                                                <div class="d-flex justify-content-end gap-1">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-light border text-primary btn-edit" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#modalModifier"
                                                            data-id="<?= $comite['id'] ?>"
                                                            data-nom="<?= htmlspecialchars($comite['nom']) ?>"
                                                            data-parent="<?= $comite['parent_id'] ?>"
                                                            data-responsable="<?= $comite['responsable_id'] ?>"
                                                            data-ordre="<?= $comite['ordre_affichage'] ?>"
                                                            data-description="<?= htmlspecialchars($comite['description']) ?>"
                                                            title="Modifier">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </button>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-light border text-danger btn-delete" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#modalSupprimer"
                                                            data-id="<?= $comite['id'] ?>"
                                                            data-nom="<?= htmlspecialchars($comite['nom']) ?>"
                                                            title="Supprimer">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                </div>
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
</div>

<!-- ==========================================
    3. MODAL DE MODIFICATION
========================================== -->
<div class="modal fade" id="modalModifier" tabindex="-1" aria-labelledby="modalModifierLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold" id="modalModifierLabel"><i class="fa-solid fa-pen-to-square text-primary me-2"></i>Modifier le comité</h5>
                <button type="button" class="btn-close" data-bs-submit="modal" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <?php if (function_exists('generer_token_csrf')): ?>
                        <input type="hidden" name="csrf_token" value="<?= generer_token_csrf(); ?>">
                    <?php endif; ?>
                    <input type="hidden" name="comite_id" id="edit_id">

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Nom du comité *</label>
                        <input type="text" name="nom" id="edit_nom" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Rattaché à (groupe parent)</label>
                        <select name="parent_id" id="edit_parent" class="form-select">
                            <option value="">-- Aucun (Groupe principal) --</option>
                            <?php foreach($tous_comites as $tc): ?>
                                <option value="<?= $tc['id'] ?>"><?= htmlspecialchars($tc['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Membre responsable / Leader</label>
                        <select name="responsable_id" id="edit_responsable" class="form-select">
                            <option value="">-- Non défini / À pourvoir --</option>
                            <?php foreach($membres as $m): ?>
                                <option value="<?= $m['id'] ?>">
                                    <?= htmlspecialchars($m['nom'] . ' ' . $m['prenoms']) ?> (<?= htmlspecialchars($m['matricule']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Ordre d'affichage</label>
                        <input type="number" name="ordre_affichage" id="edit_ordre" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Missions / Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="modifier_comite" class="btn btn-sm btn-primary fw-bold">Sauvegarder les modifications</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================
    4. MODAL DE CONFIRMATION DE SUPPRESSION
========================================== -->
<div class="modal fade" id="modalSupprimer" tabindex="-1" aria-labelledby="modalSupprimerLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0 py-2">
                <h6 class="modal-title fw-bold" id="modalSupprimerLabel"><i class="fa-solid fa-triangle-exclamation me-2"></i>Confirmation</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body text-center py-4">
                    <?php if (function_exists('generer_token_csrf')): ?>
                        <input type="hidden" name="csrf_token" value="<?= generer_token_csrf(); ?>">
                    <?php endif; ?>
                    <input type="hidden" name="comite_id" id="delete_id">
                    <p class="mb-2 small text-muted text-uppercase fw-bold">Attention</p>
                    <p class="m-0 small">Êtes-vous sûr de vouloir supprimer définitivement le comité <br><strong id="delete_nom" class="text-dark"></strong> ?</p>
                </div>
                <div class="modal-footer border-0 justify-content-center pt-0">
                    <button type="button" class="btn btn-xs btn-light border" data-bs-dismiss="modal">Non, Annuler</button>
                    <button type="submit" name="supprimer_comite" class="btn btn-xs btn-danger fw-bold">Oui, Supprimer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================
    5. JAVASCRIPT D'INTERACTION DES MODALS
========================================== -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    
    // Événement au clic du bouton Modifier : Remplissage de la boîte modale
    const editButtons = document.querySelectorAll('.btn-edit');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.getAttribute('data-id');
            document.getElementById('edit_nom').value = this.getAttribute('data-nom');
            document.getElementById('edit_parent').value = this.getAttribute('data-parent') || "";
            document.getElementById('edit_responsable').value = this.getAttribute('data-responsable') || "";
            document.getElementById('edit_ordre').value = this.getAttribute('data-ordre');
            document.getElementById('edit_description').value = this.getAttribute('data-description');
        });
    });

    // Événement au clic du bouton Supprimer : Injecter le nom et ID pour confirmation
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('delete_id').value = this.getAttribute('data-id');
            document.getElementById('delete_nom').textContent = this.getAttribute('data-nom');
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>