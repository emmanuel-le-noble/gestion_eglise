<?php
// eglise_db/membres/supprimer.php
require_once "../config/database.php";
require_once "../includes/session.php";
require_login();

// Toutes les pages du dossier membres contiendront cette ligne :
securiser_par_module($pdo, 'membres');

// 1. Sécurisation de l'ID passé en paramètre (conversion en entier)
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($id) {
    try {
        // 2. Utilisation d'une transaction pour s'assurer que TOUT est supprimé ou RIEN
        $pdo->beginTransaction();

        // 3. Supprimer d'abord les enfants liés à ce membre (évite les blocages de clé étrangère)
        $stmt_enfants = $pdo->prepare("DELETE FROM enfants WHERE membre_id = ?");
        $stmt_enfants->execute([$id]);

        // 4. Récupérer le nom de la photo pour la supprimer du serveur (optionnel mais propre)
        $stmt_photo = $pdo->prepare("SELECT photo FROM membres WHERE id = ?");
        $stmt_photo->execute([$id]);
        $membre = $stmt_photo->fetch();
        
        if ($membre && !empty($membre['photo']) && $membre['photo'] !== 'default.png') {
            $photo_path = __DIR__ . "/../assets/uploads/membres/" . $membre['photo'];
            if (file_exists($photo_path)) {
                unlink($photo_path); // Supprime physiquement le fichier image
            }
        }

        // 5. Supprimer le membre
        $stmt_membre = $pdo->prepare("DELETE FROM membres WHERE id = ?");
        $stmt_membre->execute([$id]);

        // Validation de la transaction
        $pdo->commit();
        
        // Optionnel : stocker un message de succès en session pour l'afficher sur l'index
        $_SESSION['flash_message'] = "Le membre a été supprimé avec succès.";

    } catch (PDOException $e) {
        // En cas d'erreur, on annule tout
        $pdo->rollBack();
        $_SESSION['flash_error'] = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// 6. Redirection systématique vers l'index
header("Location: index.php");
exit();