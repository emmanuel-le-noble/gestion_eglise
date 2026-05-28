<?php
// eglise_db/cultes.php (Page publique de présentation des cultes)
require_once "config/database.php";

$page_title = "Nos Cultes";
// Inclusion de l'en-tête public
require_once 'includes/public_header.php'; 
?>

<!-- Section En-tête de la page -->
<header class="bg-dark text-white text-center py-5 mb-5 position-relative" style="background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.9)), url('assets/img/cultes-bg.jpg') no-repeat center center / cover;">
    <div class="container py-4">
        <h1 class="display-5 fw-bold mb-2">Nos Rendez-vous Spirituels</h1>
        <p class="lead mx-auto text-light opacity-75 mb-0" style="max-width: 650px;">
            Découvrez les horaires, le déroulement et l'esprit de nos différentes réunions hebdomadaires. Vous êtes le bienvenu !
        </p>
    </div>
</header>

<div class="container my-5">
    
    <!-- Message d'accueil / Introduction -->
    <div class="row align-items-center mb-5 py-3">
        <div class="col-lg-6 mb-4 mb-lg-0">
            <span class="text-primary fw-bold text-uppercase small" style="letter-spacing: 1px;">Adoration & Enseignement</span>
            <h2 class="fw-bold mt-2 mb-4">Des moments pour grandir ensemble dans la foi</h2>
            <p class="text-muted" style="line-height: 1.7;">
                Que vous soyez un chrétien de longue date, un nouveau croyant ou simplement curieux de découvrir la foi chrétienne, nos portes vous sont grandes ouvertes. 
            </p>
            <p class="text-muted" style="line-height: 1.7;">
                Chacune de nos rencontres est structurée pour favoriser une communion authentique avec Dieu à travers des chants vivants, des prières sincères et une prédication ancrée dans les Écritures, applicable à votre quotidien.
            </p>
        </div>
        <div class="col-lg-6 ps-lg-5">
            <div class="p-4 bg-light rounded border-start border-primary border-4 shadow-sm">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-circle-info text-primary me-2"></i>Informations pratiques</h5>
                <ul class="list-unstyled mb-0 small text-muted">
                    <li class="mb-3 d-flex align-items-start">
                        <i class="fa-solid fa-location-dot text-primary mt-1 me-3"></i>
                        <span><strong>Adresse du Temple :</strong> Lomé, Togo (Entrée libre et gratuite pour tous).</span>
                    </li>
                    <li class="mb-3 d-flex align-items-start">
                        <i class="fa-solid fa-baby-carriage text-primary mt-1 me-3"></i>
                        <span><strong>Accueil des enfants :</strong> Une garderie et une école du dimanche (ECODIM) sont disponibles pour vos enfants pendant le culte dominical.</span>
                    </li>
                    <li class="d-flex align-items-start">
                        <i class="fa-solid fa-square-parking text-primary mt-1 me-3"></i>
                        <span><strong>Parking :</strong> Un espace de stationnement sécurisé est accessible dès l'entrée du site.</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Section 2 : Les Cultes en détail (Alternance Gauche/Droite) -->
    <div class="row g-5 my-4">
        
        <!-- 1. Le Culte Dominical -->
        <div class="col-12">
            <div class="card border-0 shadow-sm overflow-hidden bg-white">
                <div class="row g-0">
                    <div class="col-lg-1 bg-primary bg-opacity-10 d-none d-lg-flex align-items-center justify-content-center text-primary py-4" style="min-width: 100px;">
                        <i class="fa-solid fa-sun fa-3x"></i>
                    </div>
                    <div class="col-lg p-4 p-md-5">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                            <div>
                                <span class="badge bg-primary mb-2 px-2.5 py-1.5 fw-semibold">Célébration Majeure</span>
                                <h3 class="fw-bold text-dark mb-0">Le Grand Culte de Dimanche</h3>
                            </div>
                            <span class="fs-5 fw-bold text-primary bg-primary bg-opacity-10 px-3 py-2 rounded">
                                <i class="fa-regular fa-clock me-2"></i>09h00 - 11h30
                            </span>
                        </div>
                        <p class="text-muted small" style="line-height: 1.6;">
                            C'est le rendez-vous central de notre semaine où toute la famille spirituelle se rassemble. Le culte s'articule autour de moments de louange contemporaine et traditionnelle, de l'expression de notre reconnaissance, de la sainte cène et d'un message biblique inspirant partagé par les pasteurs pour guider votre semaine.
                        </p>
                        <div class="mt-4 pt-3 border-top d-flex gap-3 text-muted small flex-wrap">
                            <span><i class="fa-solid fa-users text-primary me-2"></i>Tout public</span>
                            <span><i class="fa-solid fa-child text-primary me-2"></i> Garderie & Ecodim incluses</span>
                            <span><i class="fa-solid fa-radio text-primary me-2"></i> Diffusé en direct</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Réunion de Prière -->
        <div class="col-12">
            <div class="card border-0 shadow-sm overflow-hidden bg-white">
                <div class="row g-0">
                    <div class="col-lg-1 bg-success bg-opacity-10 d-none d-lg-flex align-items-center justify-content-center text-success py-4" style="min-width: 100px;">
                        <i class="fa-solid fa-hands-praying fa-3x"></i>
                    </div>
                    <div class="col-lg p-4 p-md-5">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                            <div>
                                <span class="badge bg-success mb-2 px-2.5 py-1.5 fw-semibold">Intercession</span>
                                <h3 class="fw-bold text-dark mb-0">Prière & Combat Spirituel</h3>
                            </div>
                            <span class="fs-5 fw-bold text-success bg-success bg-opacity-10 px-3 py-2 rounded">
                                <i class="fa-regular fa-clock me-2"></i>Mercredi — 18h30
                            </span>
                        </div>
                        <p class="text-muted small" style="line-height: 1.6;">
                            Une église qui progresse est une église qui prie. Ce rendez-vous du milieu de semaine est axé sur l'intercession fervente pour les malades, les familles, les requêtes individuelles déposées auprès du secrétariat, ainsi que pour les projets et l'impact spirituel de la communauté dans notre nation.
                        </p>
                        <div class="mt-4 pt-3 border-top d-flex gap-3 text-muted small flex-wrap">
                            <span><i class="fa-solid fa-heart-pulse text-success me-2"></i>Soutien & Délivrance</span>
                            <span><i class="fa-solid fa-pen-to-square text-success me-2"></i>Dépôt de requêtes sur place</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Étude Biblique -->
        <div class="col-12">
            <div class="card border-0 shadow-sm overflow-hidden bg-white">
                <div class="row g-0">
                    <div class="col-lg-1 bg-info bg-opacity-10 d-none d-lg-flex align-items-center justify-content-center text-info py-4" style="min-width: 100px;">
                        <i class="fa-solid fa-book-open fa-3x"></i>
                    </div>
                    <div class="col-lg p-4 p-md-5">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                            <div>
                                <span class="badge bg-info mb-2 px-2.5 py-1.5 fw-semibold text-dark">Enseignement</span>
                                <h3 class="fw-bold text-dark mb-0">Étude Biblique Méthodique</h3>
                            </div>
                            <span class="fs-5 fw-bold text-info bg-info bg-opacity-10 px-3 py-2 rounded text-dark">
                                <i class="fa-regular fa-clock me-2"></i>Vendredi — 18h00
                            </span>
                        </div>
                        <p class="text-muted small" style="line-height: 1.6;">
                            Pour ne pas être ballotté par des doctrines confuses, l'étude biblique offre un espace interactif pour approfondir les textes sacrés. Nous y étudions des livres entiers de la Bible, verset par verset, ou des thématiques doctrinales précises (les finances, le mariage, la foi) avec un temps ouvert pour poser vos questions à la fin.
                        </p>
                        <div class="mt-4 pt-3 border-top d-flex gap-3 text-muted small flex-wrap">
                            <span><i class="fa-solid fa-graduation-cap text-info me-2"></i>Connaissance Doctrinale</span>
                            <span><i class="fa-solid fa-comments text-info me-2"></i>Questions & Réponses libres</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Section FAQ : Nouveau visiteur ? -->
    <div class="bg-light rounded p-4 p-md-5 my-5 border shadow-sm">
        <div class="text-center mb-4">
            <h4 class="fw-bold mb-1">Première fois parmi nous ?</h4>
            <p class="text-muted small">Voici les réponses aux questions fréquentes pour venir l'esprit tranquille.</p>
        </div>
        
        <div class="row g-4 mt-2">
            <div class="col-md-6">
                <h6 class="fw-bold text-dark"><i class="fa-solid fa-shirt text-primary me-2"></i> Comment dois-je m'habiller ?</h6>
                <p class="text-muted small mb-0">Venez comme vous êtes. Il n'y a pas de code vestimentaire imposé, la simplicité et le respect mutuel sont nos seuls repères.</p>
            </div>
            <div class="col-md-6">
                <h6 class="fw-bold text-dark"><i class="fa-solid fa-hand-holding-dollar text-primary me-2"></i> Va-t-on me demander de l'argent ?</h6>
                <p class="text-muted small mb-0">Absolument pas. Pendant le culte, une offrande libre est collectée pour soutenir les projets de l'église, mais elle est strictement réservée aux membres volontaires. Les visiteurs sont nos invités.</p>
            </div>
        </div>
    </div>
</div>

<!-- Styles spécifiques locaux -->
<style>
    .card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,.08) !important;
    }
</style>

<?php 
// Inclusion du pied de page public
require_once 'includes/public_footer.php'; 
?>