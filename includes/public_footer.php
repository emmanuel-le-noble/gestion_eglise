<?php
// eglise_db/includes/public_footer.php
?>
    </main> <!-- Fermeture sécurisée du conteneur principal ouvert dans le header -->

    <footer class="bg-dark text-white pt-5 pb-3 mt-5">
        <div class="container">
            <div class="row g-4">
                
                <!-- Colonne 1 : À propos -->
                <div class="col-md-4">
                    <h5 class="fw-bold mb-3" style="letter-spacing: 1px;">Church <span class="text-primary">Manager</span></h5>
                    <p class="text-white-50 small" style="line-height: 1.6;">
                        Une plateforme moderne et unifiée pour la croissance spirituelle, le suivi de la communauté et l'organisation rigoureuse des églises contemporaines.
                    </p>
                    <!-- Réseaux Sociaux -->
                    <div class="d-flex gap-3 mt-3">
                        <a href="#" class="text-white-50 social-icon" title="Facebook"><i class="fa-brands fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-white-50 social-icon" title="YouTube"><i class="fa-brands fa-youtube fa-lg"></i></a>
                        <a href="#" class="text-white-50 social-icon" title="Instagram"><i class="fa-brands fa-instagram fa-lg"></i></a>
                    </div>
                </div>
                
                <!-- Colonne 2 : Liens rapides -->
                <div class="col-md-4 ps-md-5">
                    <h5 class="fw-bold mb-3 text-uppercase fs-6" style="letter-spacing: 1px;">Liens Utiles</h5>
                    <ul class="list-unstyled footer-links">
                        <li class="mb-2"><a href="index.php" class="text-white-50 text-decoration-none small"><i class="fa-solid fa-chevron-right me-2 small-arrow"></i>Accueil</a></li>
                        <li class="mb-2"><a href="cultes.php" class="text-white-50 text-decoration-none small"><i class="fa-solid fa-chevron-right me-2 small-arrow"></i>Nos Cultes</a></li>
                        <li class="mb-2"><a href="evenements.php" class="text-white-50 text-decoration-none small"><i class="fa-solid fa-chevron-right me-2 small-arrow"></i>Événements</a></li>
                        <li class="mb-2"><a href="auth/login.php" class="text-white-50 text-decoration-none small"><i class="fa-solid fa-lock me-2 small-arrow"></i>Espace Interne</a></li>
                    </ul>
                </div>
                
                <!-- Colonne 3 : Secrétariat / Contact -->
                <div class="col-md-4">
                    <h5 class="fw-bold mb-3 text-uppercase fs-6" style="letter-spacing: 1px;">Secrétariat / Contact</h5>
                    <ul class="list-unstyled text-white-50 small">
                        <li class="d-flex align-items-center mb-2">
                            <i class="fa-solid fa-envelope text-primary me-3 flex-shrink-0" style="width: 15px;"></i>
                            <span>contact@tezmanagement.org</span>
                        </li>
                        <li class="d-flex align-items-center mb-2">
                            <i class="fa-solid fa-phone text-primary me-3 flex-shrink-0" style="width: 15px;"></i>
                            <span>+228 00 00 00 00</span>
                        </li>
                        <li class="d-flex align-items-center mb-2">
                            <i class="fa-solid fa-location-dot text-primary me-3 flex-shrink-0" style="width: 15px;"></i>
                            <span>Lomé, Togo</span>
                        </li>
                    </ul>
                </div>

            </div>

            <hr class="bg-white opacity-10 my-4">

            <!-- Mentions de bas de page -->
            <div class="row">
                <div class="col-12 text-center">
                    <p class="text-white-50 small mb-0">
                        &copy; <?= date('Y') ?> <strong>Church Manager</strong> - Tous droits réservés. Propulsé par un système de gestion professionnel.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Styles interactifs pour le Footer -->
    <style>
        .footer-links a, .social-icon {
            transition: all 0.3s ease;
        }
        .footer-links a:hover {
            color: #ffffff !important;
            padding-left: 5px;
        }
        .social-icon:hover {
            color: #0d6efd !important; /* Couleur primaire au survol */
            transform: translateY(-2px);
        }
        .small-arrow {
            font-size: 0.75rem;
            opacity: 0.5;
            transition: 0.3s;
        }
        .footer-links a:hover .small-arrow {
            opacity: 1;
            color: #0d6efd;
        }
    </style>

    <!-- Bootstrap 5.3.3 JS Bundle avec Popper (Indispensable pour le menu mobile et les composants interactifs) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>