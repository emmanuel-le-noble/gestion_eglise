<?php
// eglise_db/includes/public_header.php

// Détection de la page active pour le menu de navigation
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO & Réseaux Sociaux -->
    <title><?= htmlspecialchars($page_title ?? 'Bienvenue') ?> - Church Manager</title>
    <meta name="description" content="Bienvenue sur la plateforme de notre communauté. Suivez nos cultes, événements et restez connectés.">
    <meta property="og:title" content="<?= htmlspecialchars($page_title ?? 'Bienvenue') ?> - Church Manager">
    <meta property="og:description" content="Retrouvez toutes les informations pratiques, l'agenda de la communauté et nos prochains cultes.">
    <meta property="og:type" content="website">

    <!-- Favicon (À adapter avec ton propre fichier .ico ou .png) -->
    <link rel="shortcut icon" href="../assets/img/favicon.ico" type="image/x-icon">

    <!-- Polices & Icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Design personnalisé -->
    <link rel="stylesheet" href="/gestion_eglise/assets/css/public_style.css">

    <style>
        /* Quelques ajustements au cas où le CSS public ne couvre pas tout */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .navbar-brand span {
            color: #0d6efd; /* Teinte Bootstrap par défaut, à adapter à ton thème */
            font-weight: 700;
        }
        .nav-link.active {
            font-weight: 600;
            color: #0d6efd !important;
        }
    </style>
</head>
<body>

    <!-- Barre de navigation Responsive Bootstrap 5 -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <!-- Logo -->
            <a href="index.php" class="navbar-brand fw-bold fs-4 text-dark">Church <span>Manager</span></a>
            
            <!-- Bouton Menu Burger pour Mobile -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#publicNavbar" aria-controls="publicNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Liens de navigation -->
            <div class="collapse navbar-collapse" id="publicNavbar">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-2">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a href="cultes.php" class="nav-link <?= $current_page == 'cultes.php' ? 'active' : '' ?>">Nos Cultes</a>
                    </li>
                    <li class="nav-item">
                        <a href="evenements.php" class="nav-link <?= $current_page == 'evenements.php' ? 'active' : '' ?>">Événements</a>
                    </li>
                    <!-- Bouton Espace Interne mobile-friendly -->
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <a href="auth/login.php" class="btn btn-dark shadow-sm btn-sm px-3 py-2 w-100 text-nowrap">
                            <i class="fa-solid fa-lock me-1"></i> Connexion Espace Interne
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Conteneur principal ouvert (pense à fermer cette div et body dans ton public_footer.php) -->
    <main class="py-4">