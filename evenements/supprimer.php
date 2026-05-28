<?php
// eglise_db/evenements/supprimer.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'communication');

// 1. Récupération et sécurisation de l'ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$id) {
    header("Location: index.php");
    exit;
}

try {
    // Début de la transaction pour s'assurer que tout est supprimé ensemble ou rien du tout
    $pdo->beginTransaction();

    // 2. Récupérer les noms de fichiers de toutes les photos associées à cet événement
    $stmt_photos = $pdo->prepare("SELECT nom_fichier FROM evenement_photos WHERE evenement_id = ?");
    $stmt_photos->execute([$id]);
    $photos = $stmt_photos->fetchAll();

    // Dossier où sont stockées vos images
    $upload_dir = "../assets/uploads/evenements/";

    // 3. Supprimer les fichiers physiques sur le disque du serveur
    foreach ($photos as $photo) {
        $file_path = $upload_dir . $photo['nom_fichier'];
        if (file_exists($file_path)) {
            unlink($file_path); // Supprime le fichier image du dossier
        }
    }

    // 4. Supprimer les entrées de la table `evenement_photos` (liaisons en BDD)
    $stmt_del_photos = $pdo->prepare("DELETE FROM evenement_photos WHERE evenement_id = ?");
    $stmt_del_photos->execute([$id]);

    // 5. Supprimer l'événement lui-même de la table `evenements`
    $stmt_del_event = $pdo->prepare("DELETE FROM evenements WHERE id = ?");
    $stmt_del_event->execute([$id]);

    // Validation définitive de toutes les suppressions
    $pdo->commit();

    // Redirection vers la liste des événements avec un petit paramètre de succès en option
    header("Location: index.php?status=deleted");
    exit;

} catch (Exception $e) {
    // En cas d'erreur, on annule tout pour ne pas corrompre la base de données
    $pdo->rollBack();
    
    // Affichage de l'erreur proprement
    $page_title = "Erreur de suppression";
    require_once '../includes/header.php';
    echo "<div class='container mt-5'>
            <div class='alert alert-danger'>
                <i class='fa-solid fa-circle-exclamation me-2'></i>
                Une erreur est survenue lors de la suppression de l'album : " . htmlspecialchars($e->getMessage()) . "
            </div>
            <a href='index.php' class='btn btn-primary'>Retour à la liste</a>
          </div>";
    require_once '../includes/footer.php';
    exit;
}