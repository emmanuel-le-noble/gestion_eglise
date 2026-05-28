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

    // 1. Vérification de l'existence du compte et récupération des soldes / identité
    $stmt_check = $pdo->prepare("
        SELECT mc.solde_tontine, m.nom, m.prenoms 
        FROM mutuelle_comptes mc 
        JOIN membres m ON mc.membre_id = m.id 
        WHERE mc.id = ?
    ");
    $stmt_check->execute([$compte_id]);
    $compte = $stmt_check->fetch();

    if (!$compte) {
        $pdo->rollBack();
        header("Location: membres_mutuelle.php?error=not_found");
        exit();
    }

    $nom_complet = trim($compte['nom'] . ' ' . $compte['prenoms']);

    // 2. BLOCAGE SÉCURITÉ : Interdire la suppression si l'adhérent possède encore de l'épargne
    if ((float)$compte['solde_tontine'] > 0) {
        $pdo->rollBack();
        header("Location: membres_mutuelle.php?error=has_balance");
        exit();
    }

    // 3. BLOCAGE SÉCURITÉ : Interdire la suppression s'il y a un prêt en cours ou en retard
    $stmt_prets = $pdo->prepare("SELECT COUNT(id) FROM mutuelle_prets WHERE compte_id = ? AND statut IN ('EN_COURS', 'RETARD')");
    $stmt_prets->execute([$compte_id]);
    $prets_actifs = (int)$stmt_prets->fetchColumn();

    if ($prets_actifs > 0) {
        $pdo->rollBack();
        header("Location: membres_mutuelle.php?error=has_loans");
        exit();
    }

    // 4. ACTION : Clôture / Désactivation propre du compte (Soft Delete)
    // On conserve les tables mutuelle_operations et mutuelle_prets intactes pour l'équilibre de la caisse
    $stmt_del = $pdo->prepare("UPDATE mutuelle_comptes SET statut = 'DESACTIVE' WHERE id = ?");
    $stmt_del->execute([$compte_id]);

    // 5. Journalisation d'audit de l'action destructrice
    if (function_exists('enregistrer_log')) {
        enregistrer_log(
            $pdo, 
            'Révocation Compte', 
            "Le compte mutuelle de ($nom_complet) ID #$compte_id a été clôturé et désactivé par l'utilisateur ID " . $_SESSION['user_id'] . ". Historique financier préservé."
        );
    }

    $pdo->commit();
    
    header("Location: membres_mutuelle.php?status=deleted");
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (function_exists('enregistrer_log')) {
        enregistrer_log($pdo, 'Erreur Critique', "Échec de la clôture du compte mutuelle ID $compte_id. Erreur : " . $e->getMessage());
    }
    die("Erreur système lors de la clôture du compte adhérent.");
}