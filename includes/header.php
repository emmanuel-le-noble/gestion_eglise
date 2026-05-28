
<?php
// gestion_eglise/includes/header.php
require_once "../config/database.php"; // Ajuste le nom si c'est db.php ou database.php
require_once "../includes/session.php";

// On assume que ta fonction s'appelle require_login() 
require_login();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Le titre devient dynamique grâce à la variable $page_title -->
    <title><?= isset($page_title) ? $page_title . " | Church Manager" : "Accueil | Church Manager" ?></title>
    
    <!-- Polices et Icônes -->
    <link rel="icon" href="/gestion_eglise/assets/images/tez.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap 5.3.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- CSS Personnalisé -->
    <link id="light-theme" rel="stylesheet" href="/gestion_eglise/assets/css/style.css">
    <link id="dark-theme" rel="stylesheet" href="/gestion_eglise/assets/css/liquid_glace_style.css" disabled>

    <!-- css de transition -->
     <style>
        /* Transition douce */
        body {
            transition: background-color 0.5s ease, color 0.5s ease;
        }

    </style>

    <!-- bibliothèques js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    
    <?php include __DIR__ . "/sidebar.php"; // Ajuste le chemin selon où se trouvent sidebar et topbar ?>
    
    <div class="main">
        <?php include __DIR__ . "/topbar.php"; ?>
        
        <!-- On ouvre la balise container ici pour englober le contenu de toutes les pages de travail -->
    <div class="container-fluid pt-4">