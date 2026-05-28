<?php
// gestion_eglise/dashboard/topbar.php
// Si la page qui appelle le header n'a pas défini de titre, on met "Gestion" par défaut
$titre_barre = isset($page_title) ? $page_title : "Tableau de bord";
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-4 py-3">
    <div class="container-fluid">
        <!-- Le titre s'adapte désormais à la page actuelle -->
        <h5 class="m-0 fw-semibold text-secondary"><?= htmlspecialchars($titre_barre) ?></h5>
        
        <div class="ms-auto gap-4 d-flex align-items-center">
            <div class="dropdown">
                <a class="btn btn-outline-dark text-decoration-none dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <!-- Passage aux icônes FontAwesome pour cohérence avec la sidebar -->
                    <i class="fa-solid fa-circle-user me-2 text-muted"></i> 
                    <strong><?= htmlspecialchars($_SESSION['user_nom'] ?? 'Utilisateur'); ?></strong>
                    <span class="text-danger"> ( <?= htmlspecialchars($_SESSION['user_role_nom'] ?? 'role utilisateur'); ?> )</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                    <li>
                        <a class="dropdown-item py-2" href="/gestion_eglise/auth/profile.php">
                            <i class="fa-solid fa-user me-2 text-muted"></i> Mon Profil
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger py-2" href="/gestion_eglise/auth/logout.php">
                            <i class="fa-solid fa-right-from-bracket me-2"></i> Déconnexion
                        </a>
                    </li>
                </ul>
            </div>
            <div>
                <a href="#" id="themeBtn" title="Cliquer pour changer de mode" style="text-decoration: none;">
                    <div class="rounded-circle bg-primary bg-opacity-25 d-flex justify-content-center align-items-center" style="height: 45px; width : 45px;"><i id="themeIcon" class="fas fa-sun fa-xl"></i></div>
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
    const btn = document.getElementById("themeBtn");
    const lightTheme = document.getElementById("light-theme");
    const darkTheme = document.getElementById("dark-theme");
    const icon = document.getElementById("themeIcon");

    function applyTheme(theme) {
        if(theme === "dark"){
            lightTheme.disabled = true;
            darkTheme.disabled = false;
            document.body.classList.remove("light");
            document.body.classList.add("dark");
            icon.className = "fas fa-sun fa-xl";
        } else {
            lightTheme.disabled = false;
            darkTheme.disabled = true;
            document.body.classList.remove("dark");
            document.body.classList.add("light");
            icon.className = "fas fa-moon fa-xl";
        }
        localStorage.setItem("theme", theme);
    }

    // Appliquer le thème sauvegardé au chargement
    const savedTheme = localStorage.getItem("theme") || "light";
    applyTheme(savedTheme);

    // Basculer au clic
    btn.addEventListener("click", () => {
        const newTheme = darkTheme.disabled ? "dark" : "light";
        applyTheme(newTheme);
    });
</script>