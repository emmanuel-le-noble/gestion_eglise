<?php
// eglise_db/gouvernance/matrice_droits.php
require_once "../config/database.php";
require_once '../includes/helpers.php';
require_once "../includes/session.php";

// Sécurité : Seul le rôle ID 1 ou l'Admin ayant accès au module peut modifier la matrice
securiser_par_module($pdo, 'gouvernance');

$message = "";
$erreur = "";

// ==========================================
// 1. DÉFINITION DES MODULES DE L'APPLICATION
// ==========================================
$modules_application = [
    'gouvernance' => [
        'titre' => 'Administration ',
        'desc'  => 'Gestion des comités, de l\'organigramme, des textes de lois, des rôles et des comptes utilisateurs.'
    ],
    'tresorerie' => [
        'titre' => 'Trésorerie & Comptabilité',
        'desc'  => 'Suivi des écritures comptables, encaissements, décaissements, gestion de la caisse et des comptes bancaires.'
    ],
    'finances' => [
        'titre' => 'Finances',
        'desc'  => 'Planification budgétaire annuelle, suivi des dîmes, des offrandes et des campagnes de levées de fonds.'
    ],
    'membres' => [
        'titre' => 'Membres',
        'desc'  => 'Fiches des fidèles, cartographie des baptêmes, mariages et suivi de la croissance spirituelle.'
    ],
    'mutuelle' => [
        'titre' => 'Mutuelle et tontine',
        'desc'  => 'Caisse de solidarité, assistance aux membres (naissances, mariages, deuils) et suivi des actions d\'entraide.'
    ],
    'communication' => [
        'titre' => 'Événements',
        'desc'  => 'Rédaction des communiqués officiels, gestion des panneaux d\'affichage numériques et événements spéciaux.'
    ]
];

// ==========================================
// 2. ENREGISTREMENT DES PERMISSIONS (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sauvegarder_droits'])) {
    try {
        $pdo->beginTransaction();

        // On nettoie les anciennes règles
        $pdo->exec("DELETE FROM permissions_modules");

        // On insère les nouvelles cases cochées
        if (isset($_POST['droits'])) {
            $stmt = $pdo->prepare("INSERT INTO permissions_modules (nom_module, role_id) VALUES (?, ?)");
            foreach ($_POST['droits'] as $nom_module => $roles_associes) {
                if (array_key_exists($nom_module, $modules_application)) {
                    foreach ($roles_associes as $role_id => $value) {
                        $stmt->execute([$nom_module, (int)$role_id]);
                    }
                }
            }
        }

        $pdo->commit();
        $message = "Les droits d'accès aux modules ont été mis à jour avec succès !";
    } catch (Exception $e) {
        $pdo->rollBack();
        $erreur = "Erreur lors de la sauvegarde : " . $e->getMessage();
    }
}

// ==========================================
// 3. RECUPÉRATION DES RÔLES ET DROITS ACTUELS
// ==========================================
$roles = $pdo->query("SELECT * FROM roles ORDER BY nom_role ASC")->fetchAll();

// On extrait les permissions existantes au format [nom_module][role_id] = true
$droits_existants = [];
$permissions = $pdo->query("SELECT nom_module, role_id FROM permissions_modules")->fetchAll();
foreach ($permissions as $p) {
    $droits_existants[$p['nom_module']][$p['role_id']] = true;
}

$page_title = "Habilitations des Modules";
require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <div>
            <h3 class="fw-bold text-dark m-0"><i class="fa-solid fa-cubes text-primary me-2"></i>Droits par Module</h3>
            <p class="text-muted small m-0">Définissez globalement quels rôles ont accès aux grands pôles de l'application.</p>
        </div>
        <a href="index.php" class="btn btn-outline-primary border fw-bold btn-sm"><i class="fa-solid fa-arrow-left me-1"></i> Retour l'administration</a>
    </div>

    <?php if($message): ?> <div class="alert alert-success small mb-3"><i class="fa-solid fa-circle-check me-2"></i><?= $message ?></div> <?php endif; ?>
    <?php if($erreur): ?> <div class="alert alert-danger small mb-3"><i class="fa-solid fa-circle-exclamation me-2"></i><?= $erreur ?></div> <?php endif; ?>

    <form method="POST">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width: 40%;">Module Système</th>
                                <?php foreach($roles as $role): ?>
                                    <th class="text-center"><?= htmlspecialchars($role['nom_role']) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($modules_application as $cle_mod => $infos): ?>
                                <tr>
                                    <td class="ps-3 py-3">
                                        <div class="fw-bold text-dark"><?= $infos['titre'] ?></div>
                                        <p class="text-muted small mb-0" style="font-size: 0.8rem;"><?= $infos['desc'] ?></p>
                                        <code class="text-primary xsmall" style="font-size: 0.7rem;">Code : <?= $cle_mod ?></code>
                                    </td>
                                    <?php foreach($roles as $role): ?>
                                        <td class="text-center bg-light-subtle">
                                            <?php 
                                            $coche = isset($droits_existants[$cle_mod][$role['id']]) ? 'checked' : '';
                                            ?>
                                            <input type="checkbox" 
                                                   class="form-check-input" 
                                                   style="width: 1.25rem; height: 1.25rem; cursor: pointer;"
                                                   name="droits[<?= $cle_mod ?>][<?= $role['id'] ?>]" 
                                                   value="1" <?= $coche ?>>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white text-end py-3">
                <button type="submit" name="sauvegarder_droits" class="btn btn-primary fw-bold shadow-sm px-4">
                    <i class="fa-solid fa-shield-check me-2"></i>Enregistrer les autorisations
                </button>
            </div>
        </div>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>