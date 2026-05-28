
<?php
// On récupère le chemin actuel pour gérer dynamiquement la classe 'active'
$current_uri = $_SERVER['PHP_SELF'];
?>
<aside class="sidebar">
    <div class="sidebar-brand gap-2">
        <img src="/gestion_eglise/assets/images/tez.jpeg" alt="logo" width="30%" style="border-radius: 50px;">
        <a href="/gestion_eglise/dashboard/index.php"><span>TEP Manager</span></a>
    </div>
    <ul class="sidebar-menu">
        <div style="display: flex; flex-wrap: wrap; align-content: space-between; height: 100%;">
            <div>
                <!-- Dashboard -->
                <li class="<?= (strpos($current_uri, '/dashboard/') !== false) ? 'active' : ''; ?>">
                    <a href="/gestion_eglise/dashboard/index.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
                </li>
                
                <!-- Membres -->
                <li class="<?= (strpos($current_uri, '/membres/') !== false) ? 'active' : ''; ?>">
                    <a href="/gestion_eglise/membres/index.php"><i class="fa-solid fa-users"></i> Membres</a>
                </li>

                <!-- visiteurs -->
                <li class="<?= (strpos($current_uri, '/visiteurs/') !== false) ? 'active' : ''; ?>">
                    <a href="/gestion_eglise/visiteurs/index.php"><i class="fa-solid fa-users-rays"></i> visiteurs</a>
                </li>

                <!-- Cultes -->
                <li class="<?= (strpos($current_uri, '/cultes/') !== false) ? 'active' : ''; ?>">
                    <a href="/gestion_eglise/cultes/index.php"><i class="fa-solid fa-church"></i> Cultes & Réunions</a>
                </li>
                
                <!-- Evenement -->
                <li class="<?= (strpos($current_uri, '/evenements/') !== false) ? 'active' : ''; ?>">
                    <a href="/gestion_eglise/evenements/index.php"><i class="fa-solid fa-calendar"></i> Activités & Événements</a>
                </li>

                <!-- Trésorerie -->
                <li class="<?= (strpos($current_uri, '/tresorerie/') !== false) ? 'active' : ''; ?>">
                    <a href="/gestion_eglise/tresorerie/index.php"><i class="fa-solid fa-wallet"></i> Trésorerie</a>
                </li>
                
                <!-- Mutuelle -->
                <li class="<?= (strpos($current_uri, '/mutuelle/') !== false) ? 'active' : ''; ?>">
                    <a href="/gestion_eglise/mutuelle/index.php"><i class="fa-solid fa-handshake-angle"></i> Mutuelle</a>
                </li>

                <!-- finance -->
                <li class="<?= (strpos($current_uri, '/finance/') !== false) ? 'active' : ''; ?>">
                    <a href="/gestion_eglise/finance/rapport_global.php"><i class="fa-solid fa-chart-line"></i> Finances</a>
                </li>

                <!-- administration -->
                <li class="<?= (strpos($current_uri, '/gouvernance/') !== false) ? 'active' : ''; ?>">
                    <a href="/gestion_eglise/gouvernance/index.php"><i class="fa-solid fa-sitemap"></i> Adminitration</a>
                </li>
            </div>

            <div>
                <!-- profile -->
                <li class="<?= (strpos($current_uri, '/auth/') !== false) ? 'active' : ''; ?>">
                    <a href="/gestion_eglise/auth/profile.php"><i class="fa-solid fa-user me-2"></i> Compte utilisateur</a>
                </li>
                
                <!-- Bouton Déconnexion calé en bas -->
                <li style="margin-top: auto;">
                    <a href="/gestion_eglise/auth/logout.php" class="text-danger"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
                </li>
            </div>
        </div>
        
        
        
    </ul>
</aside>