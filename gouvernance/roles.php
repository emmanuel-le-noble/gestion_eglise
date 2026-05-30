<?php
// eglise_db/gouvernance/roles.php
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

    // --- AJOUT D'UN RÔLE ---
    if (isset($_POST['ajouter_role'])) {
        $nom_role = trim($_POST['nom_role']);

        if (!empty($nom_role)) {
            try {
                // Vérifier si le rôle existe déjà pour éviter les doublons
                $verif = $pdo->prepare("SELECT id FROM roles WHERE nom_role = ?");
                $verif->execute([$nom_role]);

                if ($verif->fetch()) {
                    $erreur = "Le rôle <b>" . htmlspecialchars($nom_role) . "</b> existe déjà.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO roles (nom_role) VALUES (?)");
                    $stmt->execute([$nom_role]);
                    $message = "Le rôle <b>" . htmlspecialchars($nom_role) . "</b> a été configuré avec succès !";
                }
            } catch (PDOException $e) {
                $erreur = "Erreur lors de l'ajout du rôle : " . $e->getMessage();
            }
        } else {
            $erreur = "Le nom du rôle ne peut pas être vide.";
        }
    }

    // --- MODIFICATION D'UN RÔLE ---
    if (isset($_POST['modifier_role'])) {
        $id_role = (int)$_POST['id_role'];
        $nom_role = trim($_POST['nom_role']);

        if (!empty($nom_role)) {
            try {
                // Vérifier si le nom existe déjà sur un AUTRE rôle (doublon potentiel)
                $verif = $pdo->prepare("SELECT id FROM roles WHERE nom_role = ? AND id != ?");
                $verif->execute([$nom_role, $id_role]);

                if ($verif->fetch()) {
                    $erreur = "Impossible de renommer : le rôle <b>" . htmlspecialchars($nom_role) . "</b> existe déjà.";
                } else {
                    $stmt = $pdo->prepare("UPDATE roles SET nom_role = ? WHERE id = ?");
                    $stmt->execute([$nom_role, $id_role]);
                    $message = "Le rôle a été modifié en <b>" . htmlspecialchars($nom_role) . "</b> avec succès !";
                }
            } catch (PDOException $e) {
                $erreur = "Erreur lors de la modification du rôle : " . $e->getMessage();
            }
        } else {
            $erreur = "Le nom du rôle ne peut pas être vide.";
        }
    }

    // --- SUPPRESSION D'UN RÔLE ---
    if (isset($_POST['supprimer_role'])) {
        $id_role = (int)$_POST['id_role'];

        try {
            // Optionnel mais recommandé : Vérifier si des utilisateurs sont encore liés à ce rôle
            $verif_utilisateurs = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE role_id = ?");
            // Remarque : Adaptez le nom de la colonne (ex: role_id ou id_role) selon votre schéma réel d'utilisateurs
            $verif_utilisateurs->execute([$id_role]);
            $nb_utilisateurs = $verif_utilisateurs->fetchColumn();

            if ($nb_utilisateurs > 0) {
                $erreur = "Impossible de supprimer ce rôle car il est actuellement attribué à <b>" . $nb_utilisateurs . "</b> utilisateur(s).";
            } else {
                $stmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
                $stmt->execute([$id_role]);
                $message = "Le rôle a été supprimé avec succès.";
            }
        } catch (PDOException $e) {
            $erreur = "Erreur lors de la suppression du rôle. Il est possible qu'il soit lié à d'autres tables.";
        }
    }
}

// ==========================================
// 2. CHARGEMENT DES RÔLES EXISTANTS
// ==========================================
$roles = $pdo->query("SELECT * FROM roles ORDER BY nom_role ASC")->fetchAll();

$page_title = "Configuration des rôles";
require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark m-0"><i class="fa-solid fa-shield-halved text-danger me-2"></i>Rôles & permissions</h3>
            <p class="text-muted small mb-0">Définissez les profils d'accès avant de créer les comptes utilisateurs.</p>
        </div>
        
        <div class="d-flex gap-2">
            <a href="utilisateurs.php" class="btn btn-primary btn-sm fw-bold px-3 py-2 shadow-sm">
                <i class="fa-solid fa-user-plus me-1"></i> Créer un utilisateur
            </a>
            <a href="index.php" class="btn btn-outline-danger btn-sm fw-bold px-3 py-2 shadow-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Tableau de bord
            </a>
        </div>
    </div>

    <?php if($message): ?> <div class="alert alert-success small mb-3"><i class="fa-solid fa-circle-check me-2"></i><?= $message ?></div> <?php endif; ?>
    <?php if($erreur): ?> <div class="alert alert-danger small mb-3"><i class="fa-solid fa-circle-exclamation me-2"></i><?= $erreur ?></div> <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0"><i class="fa-solid fa-plus text-success me-2"></i>Ajouter un nouveau rôle</h6>
                </div>
                <div class="card-body pt-0">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="small fw-bold text-muted mb-1">Nom du rôle *</label>
                            <input type="text" name="nom_role" class="form-control" placeholder="Ex: Admin, Trésorier, Secrétaire..." required>
                            <div class="form-text" style="font-size:0.75rem;">
                                Attention à l'orthographe exacte (ex: <code>Admin</code>) si vos fonctions de sécurité se basent sur des chaînes de caractères précises.
                            </div>
                        </div>

                        <button type="submit" name="ajouter_role" class="btn btn-success w-100 fw-bold shadow-sm">Enregistrer le rôle</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0"><i class="fa-solid fa-table-list text-secondary me-2"></i>Rôles enregistrés dans le système</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th class="ps-3" style="width: 15%;">ID Rôle</th>
                                    <th style="width: 45%;">Libellé / Nom du profil</th>
                                    <th style="width: 25%;" class="text-center">Indicateur visuel</th>
                                    <th style="width: 15%;" class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($roles)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted small">Aucun rôle configuré pour le moment.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($roles as $r): ?>
                                        <tr>
                                            <td class="ps-3"><code class="text-dark fw-bold">#<?= $r['id'] ?></code></td>
                                            <td>
                                                <span class="fw-bold text-dark"><?= htmlspecialchars($r['nom_role']) ?></span>
                                            </td>
                                            <td class="text-center">
                                                <?php 
                                                // Attribution d'une couleur de badge par défaut selon le titre
                                                $badge_color = "bg-secondary";
                                                $normalized_name = strtolower($r['nom_role']);
                                                if(strpos($normalized_name, 'admin') !== false) $badge_color = "bg-danger";
                                                elseif(strpos($normalized_name, 'trésorier') !== false || strpos($normalized_name, 'tresorier') !== false) $badge_color = "bg-success";
                                                elseif(strpos($normalized_name, 'pasteur') !== false) $badge_color = "bg-primary";
                                                elseif(strpos($normalized_name, 'secrétaire') !== false || strpos($normalized_name, 'secretaire') !== false) $badge_color = "bg-info text-dark";
                                                ?>
                                                <span class="badge <?= $badge_color ?> px-2 py-1">Profil <?= htmlspecialchars($r['nom_role']) ?></span>
                                            </td>
                                            <td class="text-end pe-3">
                                                <div class="d-flex justify-content-end gap-1">
                                                    <button type="button" class="btn btn-xs btn-light border text-primary btn-edit" 
                                                            data-bs-toggle="modal" data-bs-target="#modalModifierRole"
                                                            data-id="<?= $r['id'] ?>"
                                                            data-nom="<?= htmlspecialchars($r['nom_role']) ?>"
                                                            title="Modifier">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-xs btn-light border text-danger btn-delete" 
                                                            data-bs-toggle="modal" data-bs-target="#modalSupprimerRole"
                                                            data-id="<?= $r['id'] ?>"
                                                            data-nom="<?= htmlspecialchars($r['nom_role']) ?>"
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
        </div>
    </div>
</div>

<div class="modal fade" id="modalModifierRole" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square text-primary me-2"></i>Modifier le rôle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body text-start">
                    <input type="hidden" name="id_role" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">Nom du rôle *</label>
                        <input type="text" name="nom_role" id="edit_nom" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="modifier_role" class="btn btn-sm btn-primary fw-bold">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSupprimerRole" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0 py-2">
                <h6 class="modal-title fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i>Retrait du profil</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body text-center py-4">
                    <input type="hidden" name="id_role" id="delete_id">
                    <p class="m-0 small">Êtes-vous sûr de vouloir supprimer définitivement ce profil ?<br><strong id="delete_nom" class="text-dark"></strong></p>
                    <small class="text-muted d-block mt-2">Note : L'action échouera si des utilisateurs possèdent encore ce rôle.</small>
                </div>
                <div class="modal-footer border-0 justify-content-center pt-0">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="supprimer_role" class="btn btn-sm btn-danger fw-bold">Supprimer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Événement d'injection pour la modification
    const editButtons = document.querySelectorAll('.btn-edit');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.getAttribute('data-id');
            document.getElementById('edit_nom').value = this.getAttribute('data-nom');
        });
    });

    // Événement d'injection pour la suppression
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