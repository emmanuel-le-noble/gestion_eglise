<?php
// eglise_db/mutuelle/supprimer_membre.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'mutuelle');

// Vérification de la présence de l'ID du compte dans l'URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: membres_mutuelle.php");
    exit();
}

$compte_id = (int)$_GET['id'];

try {
    $pdo->beginTransaction();

    // 1. Supprimer l'historique des opérations financières liées à ce compte
    $stmt_ops = $pdo->prepare("DELETE FROM mutuelle_operations WHERE compte_id = ?");
    $stmt_ops->execute([$compte_id]);

    // 2. Supprimer les fiches de prêts associées à ce compte
    $stmt_prets = $pdo->prepare("DELETE FROM mutuelle_prets WHERE compte_id = ?");
    $stmt_prets->execute([$compte_id]);

    // 3. Enfin, supprimer le compte de la mutuelle lui-même
    // Note : Cela ne supprime pas le membre de la table générale 'membres', 
    // seulement son affiliation/compte au module mutuelle.
    $stmt_compte = $pdo->prepare("DELETE FROM mutuelle_comptes WHERE id = ?");
    $stmt_compte->execute([$compte_id]);

    $pdo->commit();
    
    // Redirection avec un message de succès (si ton système gère les sessions flash)
    // Sinon, la simple redirection rafraîchit la liste mise à jour.
    header("Location: membres_mutuelle.php?status=deleted");
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    // En cas d'erreur (ex: contrainte de clé étrangère non gérée), on arrête et on affiche l'erreur
    die("Erreur lors de la suppression du membre : " . $e->getMessage());
}