<?php
// eglise_db/gouvernance/utilisateurs.php
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

    // --- CRÉATION D'UN COMPTE ---
    if (isset($_POST['creer_compte'])) {
        $nom = trim($_POST['nom']);
        $email = trim($_POST['email']);
        $passwd = $_POST['passwd'];
        $role_id = (int)$_POST['role_id'];
        $statut = $_POST['statut'] ?? 'actif';

        if (!empty($nom) && !empty($email) && !empty($passwd) && $role_id > 0) {
            
            // Vérifier si l'email existe déjà
            $verif = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
            $verif->execute([$email]);
            
            if ($verif->fetch()) {
                $erreur = "Cette adresse email est déjà associée à un compte.";
            } else {
                try {
                    // Hachage sécurisé du mot de passe
                    $password_hashed = password_hash($passwd, PASSWORD_BCRYPT);
                    
                    $sql = "INSERT INTO utilisateurs (nom, email, passwd, role_id, statut, date_creation) 
                            VALUES (?, ?, ?, ?, ?, NOW())";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$nom, $email, $password_hashed, $role_id, $statut]);
                    
                    $message = "Le compte utilisateur pour <b>" . htmlspecialchars($nom) . "</b> a été créé avec succès !";
                } catch (PDOException $e) {
                    $erreur = "Erreur lors de la création du compte : " . $e->getMessage();
                }
            }
        } else {
            $erreur = "Tous les champs marqués d'un astérisque (*) sont obligatoires.";
        }
    }

    // --- CHANGEMENT DE STATUT (CONGÉ / ACTIVATION) ---
    if (isset($_POST['changer_statut'])) {
        $user_id = (int)$_POST['user_id'];
        $nouveau_statut = $_POST['nouveau_statut'];

        if (in_array($nouveau_statut, ['actif', 'en_conge', 'inactif'])) {
            try {
                // Empêcher l'utilisateur connecté de se désactiver lui-même par accident
                if ($user_id === (int)($_SESSION['user_id'] ?? 0) && $nouveau_statut !== 'actif') {
                    $erreur = "Sécurité : Vous ne pouvez pas suspendre votre propre compte actif.";
                } else {
                    $stmt = $pdo->prepare("UPDATE utilisateurs SET statut = ? WHERE id = ?");
                    $stmt->execute([$nouveau_statut, $user_id]);
                    $message = "Le statut de l'utilisateur a été mis à jour.";
                }
            } catch (PDOException $e) {
                $erreur = "Erreur lors de la mise à jour du statut.";
            }
        }
    }

    // --- SUPPRESSION D'UN UTILISATEUR ---
    if (isset($_POST['supprimer_utilisateur'])) {
        $user_id = (int)$_POST['user_id'];

        try {
            // Empêcher l'auto-suppression
            if ($user_id === (int)($_SESSION['user_id'] ?? 0)) {
                $erreur = "Action impossible : Vous ne pouvez pas supprimer le compte avec lequel vous êtes actuellement connecté.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
                $stmt->execute([$user_id]);
                $message = "Le compte utilisateur a été définitivement supprimé du système.";
            }
        } catch (PDOException $e) {
            $erreur = "Impossible de supprimer cet utilisateur (il est probable que des enregistrements ou des lois y soient associés).";
        }
    }
}

// ==========================================
// 2. RÉCUPÉRATION DES DONNÉES
// ==========================================
$roles_disponibles = $pdo->query("SELECT id, nom_role FROM roles ORDER BY nom_role ASC")->fetchAll();

$sql_users = "SELECT u.*, r.nom_role FROM utilisateurs u INNER JOIN roles r ON u.role_id = r.id ORDER BY u.nom ASC";
$utilisateurs = $pdo->query($sql_users)->fetchAll();

$page_title = "Gestion des comptes utilisateurs";
require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark m-0"><i class="fa-solid fa-users-gear text-info me-2"></i>Comptes utilisateurs</h3>
            <p class="text-muted small mb-0">Gestion des accès au système, attribution des rôles et contrôle de présence.</p>
        </div>
        
        <div>
            <a href="index.php" class="btn btn-outline-info btn-sm px-3 py-2 shadow-sm fw-bold">
                <i class="fa-solid fa-arrow-left me-1"></i> Tableau de bord
            </a>
        </div>
    </div>

    <?php if($message): ?> <div class="alert alert-success small mb-3"><i class="fa-solid fa-circle-check me-2"></i><?= $message ?></div> <?php endif; ?>
    <?php if($erreur): ?> <div class="alert alert-danger small mb-3"><i class="fa-solid fa-circle-exclamation me-2"></i><?= $erreur ?></div> <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0"><i class="fa-solid fa-user-plus text-success me-2"></i>Créer un accès utilisateur</h6>
                </div>
                <div class="card-body pt-0">
                    <form method="POST" autocomplete="off">
                        
                        <div class="mb-3">
                            <label class="small fw-bold text-muted">Nom complet *</label>
                            <input type="text" name="nom" class="form-control" placeholder="Ex: Pasteur Jean Koffi" required>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">Adresse email *</label>
                            <input type="email" name="email" class="form-control" placeholder="Ex: jean.koffi@eglise.com" required autocomplete="username">
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">Mot de passe temporaire *</label>
                            <input type="password" name="passwd" class="form-control" placeholder="Minimum 6 caractères" required autocomplete="new-password">
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">Rôle & niveau d'accès *</label>
                            <select name="role_id" class="form-select" required>
                                <option value="">-- Sélectionner un rôle --</option>
                                <?php foreach($roles_disponibles as $role): ?>
                                    <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['nom_role']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">Statut initial</label>
                            <select name="statut" class="form-select">
                                <option value="actif">Actif (accès immédiat)</option>
                                <option value="en_conge">En congé (désactivé temporairement)</option>
                                <option value="inactif">Inactif (bloqué permanent)</option>
                            </select>
                        </div>

                        <button type="submit" name="creer_compte" class="btn btn-info w-100 shadow-sm fw-bold text-white">Générer les accès</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0"><i class="fa-solid fa-list text-secondary me-2"></i>Registre des utilisateurs</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th class="ps-3" style="width: 35%;">Nom / Affichage</th>
                                    <th style="width: 25%;">Email (Identifiant)</th>
                                    <th style="width: 15%;">Rôle</th>
                                    <th style="width: 15%;">Statut actuel</th>
                                    <th style="width: 10%;" class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($utilisateurs)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted small">Aucun utilisateur enregistré.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($utilisateurs as $user): ?>
                                        <tr>
                                            <td class="ps-3">
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($user['nom']) ?></div>
                                                <small class="text-muted" style="font-size:0.7rem;">
                                                    Inscrit le : <?= date('d/m/Y', strtotime($user['date_creation'])) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="small text-secondary"><?= htmlspecialchars($user['email']) ?></span>
                                            </td>
                                            <td>
                                                <?php 
                                                $badge_color = "bg-secondary";
                                                $normalized_role = strtolower($user['nom_role']);
                                                if(strpos($normalized_role, 'admin') !== false) $badge_color = "bg-danger";
                                                elseif(strpos($normalized_role, 'trésorier') !== false || strpos($normalized_role, 'tresorier') !== false) $badge_color = "bg-success";
                                                elseif(strpos($normalized_role, 'pasteur') !== false) $badge_color = "bg-primary";
                                                elseif(strpos($normalized_role, 'secrétaire') !== false || strpos($normalized_role, 'secretaire') !== false) $badge_color = "bg-info text-dark";
                                                ?>
                                                <span class="badge <?= $badge_color ?>" style="font-size:0.75rem;"><?= htmlspecialchars($user['nom_role']) ?></span>
                                            </td>
                                            <td>
                                                <?php if($user['statut'] === 'actif'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2">Actif</span>
                                                <?php elseif($user['statut'] === 'en_conge'): ?>
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2"><i class="fa-solid fa-umbrella-beach me-1"></i>En congé</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2">Inactif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-3">
                                                <div class="d-flex justify-content-end gap-1">
                                                    <?php if($user['statut'] === 'actif'): ?>
                                                        <form method="POST" class="d-inline m-0">
                                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                            <input type="hidden" name="nouveau_statut" value="en_conge">
                                                            <button type="submit" name="changer_statut" class="btn btn-xs btn-light border text-warning" title="Désactiver temporairement (congé)">
                                                                <i class="fa-solid fa-plane-departure"></i>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" class="d-inline m-0">
                                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                            <input type="hidden" name="nouveau_statut" value="actif">
                                                            <button type="submit" name="changer_statut" class="btn btn-xs btn-light border text-success" title="Réactiver le compte">
                                                                <i class="fa-solid fa-user-check"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>

                                                    <button type="button" class="btn btn-xs btn-light border text-danger btn-delete" 
                                                            data-bs-toggle="modal" data-bs-target="#modalSupprimerUser"
                                                            data-id="<?= $user['id'] ?>"
                                                            data-nom="<?= htmlspecialchars($user['nom']) ?>"
                                                            title="Supprimer définitivement">
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

<div class="modal fade" id="modalSupprimerUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0 py-2">
                <h6 class="modal-title fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i>Révocation définitive</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body text-center py-4">
                    <input type="hidden" name="user_id" id="delete_id">
                    <p class="m-0 small">Êtes-vous sûr de vouloir supprimer définitivement les accès système de :<br><strong id="delete_nom" class="text-dark"></strong> ?</p>
                    <small class="text-muted d-block mt-2">Cette opération effacera définitivement ses identifiants du registre.</small>
                </div>
                <div class="modal-footer border-0 justify-content-center pt-0">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="supprimer_utilisateur" class="btn btn-sm btn-danger fw-bold">Supprimer définitivement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
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