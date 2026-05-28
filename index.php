<?php
// eglise_db/index.php (Page principale publique des visiteurs)
require_once "config/database.php";

// Récupération dynamique des activités du mois en cours
$mois_actuel = date('m');
$annee_actuelle = date('Y');

try {
    $sql = "SELECT * FROM evenements WHERE MONTH(date_evenement) = ? AND YEAR(date_evenement) = ? ORDER BY date_evenement ASC LIMIT 6";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$mois_actuel, $annee_actuelle]);
    $agenda_du_mois = $stmt->fetchAll();
} catch (PDOException $e) {
    // Repli sécurisé si la table ou la base rencontre un problème
    $agenda_du_mois = [];
}

$mois_fr = [
    '01' => 'Janvier', '02' => 'Février', '03' => 'Mars', '04' => 'Avril', '05' => 'Mai', '06' => 'Juin',
    '07' => 'Juillet', '08' => 'Août', '09' => 'Septembre', '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
];

$page_title = "Bienvenue";
// Inclusion de l'en-tête public
require_once 'includes/public_header.php'; 
?>

<!-- Section Hero (Bannière d'accueil) -->
<header id="accueil" class="bg-dark text-white text-center py-5 mb-5 position-relative style-hero" style="background: linear-gradient(rgba(0, 0, 0, 0.65), rgba(0, 0, 0, 0.85)), url('assets/img/hero-bg.jpg') no-repeat center center / cover;">
    <div class="container py-5">
        <h1 class="display-4 fw-bold mb-3">Communauté Spirituelle & Fraternelle</h1>
        <p class="lead mx-auto text-light opacity-75 mb-4" style="max-width: 700px;">
            Un espace d'adoration, d'enseignement et d'entraide mutuelle pour l'édification et l'épanouissement de notre corps communautaire.
        </p>
        <div class="d-flex justify-content-center gap-3">
            <a href="#cultes" class="btn btn-primary btn-lg px-4 shadow">Nos Réunions</a>
            <a href="#evenements" class="btn btn-outline-light btn-lg px-4">Agenda du mois</a>
        </div>
    </div>
</header>

<!-- Section 1 : Rendez-vous Hebdomadaires -->
<section id="cultes" class="container my-5 py-3">
    <div class="text-center mb-5">
        <h2 class="fw-bold position-relative d-inline-block pb-3">
            Nos Rendez-vous Hebdomadaires
            <span class="position-absolute bottom-0 start-50 translate-middle-x bg-primary rounded" style="width: 60px; height: 4px;"></span>
        </h2>
    </div>
    
    <div class="row g-4">
        <!-- Culte Dominical -->
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm text-center p-3 border-top border-primary border-4 hover-effect">
                <div class="card-body">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle mx-auto d-flex align-items-center justify-content-center mb-4" style="width: 70px; height: 70px;">
                        <i class="fa-solid fa-sun fa-2xl"></i>
                    </div>
                    <h4 class="card-title fw-bold mb-3">Culte Dominical</h4>
                    <p class="card-text text-muted small">
                        Célébration majeure de notre communauté. Louange inspirée, adoration fervente, sainte cène et partage approfondi de la Parole de Dieu.
                    </p>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0 pb-3">
                    <span class="badge bg-light text-dark border p-2 fw-semibold w-100">
                        <i class="fa-regular fa-clock text-primary me-2"></i>Chaque Dimanche — 09h00
                    </span>
                </div>
            </div>
        </div>

        <!-- Prière & Intercession -->
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm text-center p-3 border-top border-success border-4 hover-effect">
                <div class="card-body">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle mx-auto d-flex align-items-center justify-content-center mb-4" style="width: 70px; height: 70px;">
                        <i class="fa-solid fa-hands-praying fa-2xl"></i>
                    </div>
                    <h4 class="card-title fw-bold mb-3">Prière & Intercession</h4>
                    <p class="card-text text-muted small">
                        Rassemblement communautaire entièrement dédié à la prière fervente, au combat spirituel, au soutien mutuel et à l'intercession pour l'Église.
                    </p>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0 pb-3">
                    <span class="badge bg-light text-dark border p-2 fw-semibold w-100">
                        <i class="fa-regular fa-clock text-success me-2"></i>Chaque Mercredi — 18h30
                    </span>
                </div>
            </div>
        </div>

        <!-- Étude Biblique -->
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm text-center p-3 border-top border-info border-4 hover-effect">
                <div class="card-body">
                    <div class="bg-info bg-opacity-10 text-info rounded-circle mx-auto d-flex align-items-center justify-content-center mb-4" style="width: 70px; height: 70px;">
                        <i class="fa-solid fa-book-open fa-2xl"></i>
                    </div>
                    <h4 class="card-title fw-bold mb-3">Étude Biblique</h4>
                    <p class="card-text text-muted small">
                        Moments interactifs et structurés d'analyse méthodique des Saintes Écritures pour fortifier la connaissance doctrinale et grandir dans la foi.
                    </p>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0 pb-3">
                    <span class="badge bg-light text-dark border p-2 fw-semibold w-100">
                        <i class="fa-regular fa-clock text-info me-2"></i>Chaque Vendredi — 18h00
                    </span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section 2 : Agenda Mensuel Dynamique -->
<section id="evenements" class="bg-light border-top border-bottom py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold position-relative d-inline-block pb-3">
                Agenda de <?= $mois_fr[$mois_actuel] ?? 'ce mois' ?> <?= $annee_actuelle ?>
                <span class="position-absolute bottom-0 start-50 translate-middle-x bg-danger rounded" style="width: 60px; height: 4px;"></span>
            </h2>
        </div>
        
        <div class="row g-4">
            <?php if(empty($agenda_du_mois)): ?>
                <!-- Cas où aucun événement n'est planifié -->
                <div class="col-12 text-center py-4">
                    <div class="bg-white rounded p-5 shadow-sm mx-auto" style="max-width: 600px;">
                        <i class="fa-regular fa-calendar-xmark fa-3x text-muted mb-3"></i>
                        <h5 class="text-dark fw-bold mb-2">Aucun événement particulier</h5>
                        <p class="text-muted small mb-0">Aucune réunion extraordinaire ou célébration spéciale n'est planifiée pour ce mois-ci. Rejoignez-nous lors de nos cultes hebdomadaires !</p>
                    </div>
                </div>
            <?php else: ?>
                <!-- Boucle d'affichage des événements -->
                <?php foreach($agenda_du_mois as $ev): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm bg-white hover-effect overflow-hidden">
                            <div class="card-body p-4 position-relative">
                                <!-- Badge du type d'événement -->
                                <span class="badge bg-danger bg-opacity-10 text-danger mb-3 fw-semibold px-2.5 py-1.5 small">
                                    <i class="fa-solid fa-bookmark me-1.5 small"></i><?= htmlspecialchars($ev['type_evenement'] ?? 'Événement') ?>
                                </span>
                                
                                <!-- Titre optionnel ou type par défaut -->
                                <h4 class="card-title fw-bold text-dark mb-2">
                                    <?= htmlspecialchars($ev['titre'] ?? $ev['type_evenement']) ?>
                                </h4>
                                
                                <p class="card-text text-muted small mb-4" style="line-height: 1.6;">
                                    <?= htmlspecialchars($ev['description'] ?? 'Aucune description détaillée disponible pour cette activité.') ?>
                                </p>
                            </div>
                            
                            <!-- Méta-informations de l'événement -->
                            <div class="card-footer bg-light border-0 px-4 py-3 border-top d-flex flex-wrap justify-content-between gap-2 text-muted small">
                                <span>
                                    <i class="fa-solid fa-calendar-day text-danger me-2"></i>Le <?= date('d/m/Y', strtotime($ev['date_evenement'])) ?>
                                </span>
                                <span class="text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($ev['lieu'] ?? 'Temple Principal') ?>">
                                    <i class="fa-solid fa-location-dot text-danger me-2"></i><?= htmlspecialchars($ev['lieu'] ?? 'Temple Principal') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Styles locaux additionnels pour peaufiner l'interface utilisateur -->
<style>
    .hover-effect {
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    .hover-effect:hover {
        transform: translateY(-4px);
        box-shadow: 0 1rem 3rem rgba(0,0,0,.08) !important;
    }
    .style-hero {
        border-bottom: 5px solid #0d6efd; /* Rappel de la couleur de marque */
    }
</style>

<?php 
// Inclusion du pied de page public
require_once 'includes/public_footer.php'; 
?>