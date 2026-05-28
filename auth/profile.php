<?php
// eglise_db/auth/profile.php
require_once "../config/database.php";
require_once "../includes/session.php";

// On s'assure que l'utilisateur est bien connecté
require_login();

$message = "";
$erreur = "";
$user_id = $_SESSION['user_id'];

try {
    // 1. Récupérer les infos fraîches de l'utilisateur avec une jointure pour avoir le nom du rôle
    $sql = "SELECT u.id, u.nom, u.email, u.date_creation, r.nom_role 
            FROM utilisateurs u
            JOIN roles r ON u.role_id = r.id 
            WHERE u.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        die("Utilisateur introuvable.");
    }

    // 2. Traitement du changement de mot de passe
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ancien_mdp = $_POST['ancien_mot_de_passe'];
        $nouveau_mdp = $_POST['nouveau_mot_de_passe'];
        $confirme_mdp = $_POST['confirme_mot_de_passe'];

        if (!empty($ancien_mdp) && !empty($nouveau_mdp) && !empty($confirme_mdp)) {
            
            // Étape A : On récupère le mot de passe actuel hashé depuis le champ `passwd`
            $stmt_pass = $pdo->prepare("SELECT passwd FROM utilisateurs WHERE id = ?");
            $stmt_pass->execute([$user_id]);
            $hash_actuel = $stmt_pass->fetchColumn();

            // Étape B : On vérifie si l'ancien mot de passe correspond au hash
            if (password_verify($ancien_mdp, $hash_actuel)) {
                
                // Étape C : On valide la correspondance du nouveau mot de passe
                if ($nouveau_mdp === $confirme_mdp) {
                    
                    // Étape D : Sécurité — Cryptage BCrypt
                    $nouveau_hash = password_hash($nouveau_mdp, PASSWORD_BCRYPT);
                    
                    // Mise à jour sur le bon champ `passwd`
                    $update = $pdo->prepare("UPDATE utilisateurs SET passwd = ? WHERE id = ?");
                    $update->execute([$nouveau_hash, $user_id]);
                    
                    $message = "Votre mot de passe a été modifié avec succès !";
                } else {
                    $erreur = "Le nouveau mot de passe et sa confirmation ne correspondent pas.";
                }
            } else {
                $erreur = "L'ancien mot de passe est incorrect.";
            }
        } else {
            $erreur = "Veuillez remplir tous les champs pour modifier le mot de passe.";
        }
    }

    $page_title = "Mon profil";
    require_once '../includes/header.php';

} catch (PDOException $e) {
    $erreur = "Erreur système : " . $e->getMessage();
}
?>

<div class="container mt-4">
    <div class="row g-4">
        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-4">
                    <div class="rounded-circle bg-dark text-warning mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                        <i class="fa-solid fa-user-gear fa-2x"></i>
                    </div>
                    <h5 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($user['nom']) ?></h5>
                    <span class="badge bg-primary px-3 py-1 small text-uppercase"><?= htmlspecialchars($user['nom_role']) ?></span>
                    
                    <hr class="opacity-25 my-4">
                    
                    <div class="text-start small px-2">
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted"><i class="fa-solid fa-envelope me-2"></i>Email (Identifiant) :</span>
                            <span class="fw-bold text-dark"><?= htmlspecialchars($user['email']) ?></span>
                        </div>
                        <div class="mb-0 d-flex justify-content-between align-items-center">
                            <span class="text-muted"><i class="fa-solid fa-calendar-day me-2"></i>Inscrit le :</span>
                            <span class="fw-semibold"><?= date('d/m/Y à H:i', strtotime($user['date_creation'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-lock text-muted me-2"></i>Sécurité du compte</h6>
                </div>
                <div class="card-body p-4 pt-2">
                    <?php if($message): ?> 
                        <div class="alert alert-success border-0 shadow-sm small mb-3"><i class="fa-solid fa-check-circle me-2"></i><?= $message ?></div> 
                    <?php endif; ?>
                    
                    <?php if($erreur): ?> 
                        <div class="alert alert-danger border-0 shadow-sm small mb-3"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= $erreur ?></div> 
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Mot de passe actuel</label>
                            <input type="password" name="ancien_mot_de_passe" class="form-control" placeholder="Entrez votre mot de passe actuel" required>
                        </div>
                        
                        <hr class="opacity-25 my-3">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Nouveau mot de passe</label>
                            <input type="password" name="nouveau_mot_de_passe" class="form-control border-primary border-opacity-25" placeholder="Minimum 6 caractères" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">Confirmer le nouveau mot de passe</label>
                            <input type="password" name="confirme_mot_de_passe" class="form-control border-primary border-opacity-25" placeholder="Répétez le nouveau mot de passe" required>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-dark px-4 shadow-sm">
                                <i class="fa-solid fa-key me-2"></i>Mettre à jour le mot de passe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>