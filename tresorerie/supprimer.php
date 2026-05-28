<?php
// eglise_db/tresorerie/supprimer.php
require_once "../config/database.php";
require_once "../includes/session.php";
require_login(); 
securiser_par_module($pdo, 'tresorerie');


$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    try {
        $pdo->beginTransaction(); // Sécurité comptable : On ouvre une transaction

        // 2. Récupérer le nom de la pièce jointe pour la nettoyer du serveur
        $stmt = $pdo->prepare("SELECT piece_justificative FROM tresorerie WHERE id = ?");
        $stmt->execute([$id]);
        $op = $stmt->fetch();

        if ($op) {
            // Supprimer le fichier physique s'il existe
            if (!empty($op['piece_justificative'])) {
                // OPTIMISATION : Utilisation de __DIR__ pour un chemin absolu ultra-fiable
                $file_path = __DIR__ . "/../assets/uploads/pieces/" . $op['piece_justificative'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            // 3. Supprimer la ligne en Base de données
            $deleteStmt = $pdo->prepare("DELETE FROM tresorerie WHERE id = ?");
            $deleteStmt->execute([$id]);
            
            $_SESSION['flash_message'] = "L'opération de trésorerie a été supprimée avec succès.";
        } else {
            $_SESSION['flash_error'] = "Opération introuvable.";
        }

        $pdo->commit(); // On valide les changements

    } catch (PDOException $e) {
        $pdo->rollBack(); // En cas de pépin, on annule tout
        // OPTIMISATION : Redirection propre plutôt qu'un die() agressif
        $_SESSION['flash_error'] = "Erreur de suppression comptable : " . $e->getMessage();
    }
} else {
    $_SESSION['flash_error'] = "Identifiant d'opération invalide.";
}

// Redirection directe et propre vers le journal de trésorerie

// L'URL de la page dont vous voulez vérifier la provenance
$page_attendue1 = "http://localhost/gestion_eglise/tresorerie/index.php";
$page_attendue2 = "http://localhost/gestion_eglise/tresorerie/journal.php";

// 1. On vérifie d'abord si le Referer existe (il n'est pas toujours présent)
if (isset($_SERVER['HTTP_REFERER'])) {
    
    // 2. On compare l'URL de provenance avec notre page attendue
    if ($_SERVER['HTTP_REFERER'] === $page_attendue1) {
        header("Location: index.php");

    } else if($_SERVER['HTTP_REFERER'] === $page_attendue2){
        header("Location: journal.php");

    }else{
        echo "Le clic provient d'une autre page : " . htmlspecialchars($_SERVER['HTTP_REFERER']);
    }
    
} else {
    echo "Impossible de déterminer la provenance (aucun Referer détecté).";
}

exit;