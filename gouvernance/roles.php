<?php
// eglise_db/gouvernance/roles.php
require_once "../config/database.php";
require_once "../includes/session.php";
// Toutes les pages du dossier gouvernance contiendront cette ligne :
securiser_par_module($pdo, 'gouvernance');

$message = "";
$erreur = "";

// ==========================================
// 1. TRAITEMENT DE L'AJOUT D'UN RÔLE (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_role'])) {
    $nom_role = trim($_POST['nom_role']);

    if (!empty($nom_role)) {
        try {
            // Vérifier si le rôle existe déjà pour éviter les doublons
            $verif = $pdo->prepare("SELECT id FROM roles WHERE nom_role = ?");
            $verif->execute([$nom_role]);

            if ($verif->fetch()) {
                $erreur = "Le rôle <b>" . htmlspecialchars($nom_role) . "</b> existe déjà.";
            } else {
                // Insertion selon ta structure exacte
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

// ==========================================
// 2. CHARGEMENT DES RÔLES EXISTANTS
// ==========================================
$roles = $pdo->query("SELECT * FROM roles ORDER BY nom_role ASC")->fetchAll();

$page_title = "Configuration des rôles";
require_once '../includes/header.php';
?>

<div class="container mt-4">
    <!-- En-tête opérationnel -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark m-0"><i class="fa-solid fa-shield-halved text-danger me-2"></i>Rôles & permissions</h3>
            <p class="text-muted small mb-0">Définissez les profils d'accès avant de créer les comptes utilisateurs.</p>
        </div>
        
        <div class="d-flex gap-2">
            <!-- Bouton pour basculer vers la création de comptes -->
            <a href="utilisateurs.php" class="btn btn-primary btn-sm fw-bold px-3 py-2 shadow-sm">
                <i class="fa-solid fa-user-plus me-1"></i> Créer un utilisateur
            </a>
            <!-- Bouton Retour -->
            <a href="index.php" class="btn btn-outline-danger btn-sm fw-bold px-3 py-2 shadow-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Tableau de bord
            </a>
        </div>
    </div>

    <?php if($message): ?> <div class="alert alert-success small mb-3"><i class="fa-solid fa-circle-check me-2"></i><?= $message ?></div> <?php endif; ?>
    <?php if($erreur): ?> <div class="alert alert-danger small mb-3"><i class="fa-solid fa-circle-exclamation me-2"></i><?= $erreur ?></div> <?php endif; ?>

    <div class="row g-4">
        <!-- Formulaire d'ajout (Gauche) -->
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

        <!-- Liste des rôles configurés (Droite) -->
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
                                    <th>Libellé / Nom du profil</th>
                                    <th class="text-end pe-3">Indicateur visuel</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($roles)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted small">Aucun rôle configuré pour le moment.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($roles as $r): ?>
                                        <tr>
                                            <td class="ps-3"><code class="text-dark fw-bold">#<?= $r['id'] ?></code></td>
                                            <td>
                                                <span class="fw-bold text-dark"><?= htmlspecialchars($r['nom_role']) ?></span>
                                            </td>
                                            <td class="text-end pe-3">
                                                <?php 
                                                // Attribution d'une couleur de badge par défaut selon le titre
                                                $badge_color = "bg-secondary";
                                                if(strpos(strtolower($r['nom_role']), 'admin') !== false) $badge_color = "bg-danger";
                                                elseif(strpos(strtolower($r['nom_role']), 'trésorier') !== false || strpos(strtolower($r['nom_role']), 'tresorier') !== false) $badge_color = "bg-success";
                                                elseif(strpos(strtolower($r['nom_role']), 'pasteur') !== false) $badge_color = "bg-primary";
                                                elseif(strpos(strtolower($r['nom_role']), 'secrétaire') !== false || strpos(strtolower($r['nom_role']), 'secretaire') !== false) $badge_color = "bg-info text-dark";
                                                ?>
                                                <span class="badge <?= $badge_color ?> px-2 py-1">Profil <?= htmlspecialchars($r['nom_role']) ?></span>
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

<?php require_once '../includes/footer.php'; ?>