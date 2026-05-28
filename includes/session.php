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

/**
 * Enregistre une action utilisateur ou système dans les logs.
 *
 * @param PDO $pdo Instance de connexion à la base de données.
 * @param string $module Le module concerné (ex: 'Authentification', 'Facturation').
 * @param string $action L'action effectuée (ex: 'Connexion', 'Suppression').
 * @param mixed $details Détails optionnels (string, array, objet).
 */
function enregistrer_log(PDO $pdo, string $module, string $action, mixed $details = null): void 
{
    try {
        // 1. Gestion des détails (si c'est un tableau/objet, on sérialise en JSON)
        if ($details !== null && !is_string($details)) {
            $details = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        }

        // 2. Détermination de l'IP réelle (gestion basique des proxys)
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        // Si HTTP_X_FORWARDED_FOR contient plusieurs IP séparées par des virgules, on prend la première
        if (str_contains($ip, ',')) {
            $ip = trim(explode(',', $ip)[0]);
        }

        // 3. Préparation et exécution de la requête
        $sql = "INSERT INTO logs_systeme (utilisateur_id, utilisateur_nom, module, action, details, adresse_ip) 
                VALUES (:uid, :unom, :mod, :act, :det, :ip)";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':uid'  => $_SESSION['user_id'] ?? 0,
            ':unom' => $_SESSION['user_nom'] ?? 'Système',
            ':mod'  => $module,
            ':act'  => $action,
            ':det'  => $details,
            ':ip'   => $ip
        ]);

    } catch (PDOException $e) {
        // Optionnel : Enregistrer l'erreur de la BDD dans le log d'erreur PHP natif 
        // pour ne pas perdre la trace d'un bug de table (ex: table pleine, colonne manquante)
        error_log("Erreur d'écriture dans logs_systeme : " . $e->getMessage());
    }
}