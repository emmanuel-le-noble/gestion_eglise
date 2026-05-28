<?php
// eglise_db/includes/session.php

// 1. Démarrage sécurisé de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Vérifie si l'utilisateur est connecté (Ton ancienne fonction)
 */
function est_connecte() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirige si non connecté (Ton ancienne fonction remise en place)
 */
function require_login() {
    if (!est_connecte()) {
        header("Location: /gestion_eglise/auth/login.php");
        exit();
    }
}

/**
 * Sécurise une page en fonction du module auquel elle appartient.
 * 
 * @param PDO $pdo L'instance de connexion à la base de données
 * @param string $nom_module Le nom du module à vérifier (ex: 'gouvernance', 'finances')
 */
function securiser_par_module($pdo, $nom_module) {
    // 1. Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role_id'])) {
        header("Location: ../auth/login.php?erreur=connexion_requise");
        exit();
    }

    // 2. Vérifier si le module possède au moins une restriction configurée en BDD
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM permissions_modules WHERE nom_module = ?");
    $stmt->execute([$nom_module]);
    $module_est_restreint = $stmt->fetchColumn() > 0;

    if ($module_est_restreint) {
        // 3. Vérifier si le rôle de l'utilisateur a reçu le droit pour ce module
        $check = $pdo->prepare("SELECT COUNT(*) FROM permissions_modules WHERE nom_module = ? AND role_id = ?");
        $check->execute([$nom_module, $_SESSION['user_role_id']]);
        $a_le_droit = $check->fetchColumn() > 0;

        if (!$a_le_droit) {
            // Redirection si accès refusé
            header("Location: ../auth/login.php?erreur=connexion_requise");
            exit();
        }
    }
}