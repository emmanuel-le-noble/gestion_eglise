<?php
// eglise_db/membres/supprimer.php
require_once "../config/database.php";
require_once "../includes/session.php";
require_once '../includes/helpers.php';

// Vérification de la connexion et des droits d'accès
require_login();
securiser_par_module($pdo, 'membres');

// 1. Sécurisation stricte de la méthode de requête (POST vivement recommandé)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Si la requête n'est pas en POST (ex: accès direct par URL en GET), on bloque par sécurité
    $_SESSION['flash_error'] = "Méthode de requête non autorisée. Utilisez le formulaire de suppression.";
    header("Location: index.php");
    exit();
}

// Validation du jeton CSRF
$token_recu = $_POST['csrf_token'] ?? '';
if (function_exists('verifier_token_csrf') && !verifier_token_csrf($token_recu)) {
    $_SESSION['flash_error'] = "Action non autorisée (Échec de la sécurité CSRF).";
    header("Location: index.php");
    exit();
}

// 2. Récupération sécurisée de l'ID en POST uniquement
$id = isset($_POST['id']) ? (int)$_POST['id'] : null;

if ($id) {
    try {
        // 3. Utilisation d'une transaction globale
        $pdo->beginTransaction();

        // 4. GESTION DES ENFANTS RATTACHÉS (Évite les fiches orphelines)
        // On récupère d'abord les ID des fiches membres des enfants avant de supprimer la liaison
        $stmt_get_enfants = $pdo->prepare("SELECT enfant_membre_id FROM enfants WHERE membre_id = ?");
        $stmt_get_enfants->execute([$id]);
        $enfants_lies = $stmt_get_enfants->fetchAll(PDO::FETCH_ASSOC);

        // Supprimer les liaisons de parenté dans la table enfants
        $stmt_del_liaisons = $pdo->prepare("DELETE FROM enfants WHERE membre_id = ?");
        $stmt_del_liaisons->execute([$id]);

        // Optionnel mais recommandé : Nettoyer les fiches autonomes des enfants devenus orphelins
        if (!empty($enfants_lies)) {
            $stmt_del_membre_enfant = $pdo->prepare("DELETE FROM membres WHERE id = ? AND qualite = 'Enfant'");
            foreach ($enfants_lies as $enfant) {
                if (!empty($enfant['enfant_membre_id'])) {
                    // Supprime la fiche de l'enfant dans 'membres' s'il a le statut 'Enfant'
                    $stmt_del_membre_enfant->execute([$enfant['enfant_membre_id']]);
                }
            }
        }

        // 5. Récupération des infos du membre (pour la photo et les logs) avant suppression
        $stmt_membre_info = $pdo->prepare("SELECT nom, prenoms, photo FROM membres WHERE id = ?");
        $stmt_membre_info->execute([$id]);
        $membre = $stmt_membre_info->fetch();

        if ($membre) {
            // Suppression physique de la photo sur le serveur
            if (!empty($membre['photo']) && !in_array($membre['photo'], ['default.png', 'default_avatar.png'])) {
                $photo_path = __DIR__ . "/../assets/uploads/membres/" . $membre['photo'];
                if (file_exists($photo_path)) {
                    @unlink($photo_path);
                }
            }

            // 6. Suppression définitive du membre parent
            $stmt_delete_parent = $pdo->prepare("DELETE FROM membres WHERE id = ?");
            $stmt_delete_parent->execute([$id]);

            // --- INTÉGRATION DU JOURNAL DES LOGS ---
            if (function_exists('enregistrer_log')) {
                $id_operateur = $_SESSION['user_id'] ?? null;
                $nom_complet = $membre['nom'] . ' ' . $membre['prenoms'];
                $details_log = "Suppression définitive du membre : $nom_complet (ID: #$id) et de ses dépendances enfants.";
                enregistrer_log($pdo, "Suppression", $details_log, $id_operateur);
            }

            // Validation de toutes les opérations
            $pdo->commit();
            $_SESSION['flash_message'] = "Le membre a été supprimé avec succès.";
        } else {
            $pdo->rollBack();
            $_SESSION['flash_error'] = "Membre introuvable en base de données.";
        }

    } catch (PDOException $e) {
        // En cas de problème, la base de données revient à son état initial
        $pdo->rollBack();
        $_SESSION['flash_error'] = "Erreur lors de la suppression : " . $e->getMessage();
    }
} else {
    $_SESSION['flash_error'] = "Identifiant de membre invalide.";
}

// 7. Redirection vers l'index
header("Location: index.php");
exit();