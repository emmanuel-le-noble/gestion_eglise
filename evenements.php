<?php
// eglise_db/evenements.php (Page publique d'affichage de l'agenda avec photos liées)
require_once "config/database.php";

$mois_actuel = date('m');
$annee_actuelle = date('Y');

$evenements_du_mois = [];
$evenements_futurs = [];

try {
    // Requête optimisée : on récupère l'événement ET le nom de sa première photo associée (si elle existe)
    $sql = "SELECT e.*, ep.nom_fichier 
            FROM evenements e 
            LEFT JOIN evenement_photos ep ON e.id = ep.evenement_id
            WHERE e.date_evenement >= DATE_FORMAT(NOW() ,'%Y-%m-01') 
            GROUP BY e.id
            ORDER BY e.date_evenement ASC";
            
    $stmt = $pdo->query($sql);
    $tous_les_evenements = $stmt->fetchAll();

    // Tri des événements en deux groupes distincts pour l'affichage
    foreach ($tous_les_evenements as $ev) {
        $date_ev = strtotime($ev['date_evenement']);
        if (date('m', $date_ev) == $mois_actuel && date('Y', $date_ev) == $annee_actuelle) {
            $evenements_du_mois[] = $ev;
        } else {
            $evenements_futurs[] = $ev;
        }
    }
} catch (PDOException $e) {
    // Reste silencieux en production ou affiche une alerte si besoin
}

$mois_fr = [
    '01' => 'Janvier', '02' => 'Février', '03' => 'Mars', '04' => 'Avril', '05' => 'Mai', '06' => 'Juin',
    '07' => 'Juillet', '08' => 'Août', '09' => 'Septembre', '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
];

$page_title = "Notre Agenda";
// Inclusion de l'en-tête public
require_once 'includes/public_header.php'; 
?>

<!-- Section En-tête -->
<header class="bg-dark text-white text-center py-5 mb-5 position-relative" style="background: linear-gradient(rgba(0, 0, 0, 0.75), rgba(0, 0, 0, 0.9)), url('assets/img/events-bg.jpg') no-repeat center center / cover;">
    <div class="container py-4">
        <h1 class="display-5 fw-bold mb-2">Agenda & Événements Spéciaux</h1>
        <p class="lead mx-auto text-light opacity-75 mb-0" style="max-width: 650px;">
            Retrouvez ici les dates de nos séminaires, conférences, célébrations spéciales et programmes d'impact communautaire.
        </p>
    </div>
</header>

<div class="container my-5">
    
    <!-- SECTION 1 : AU PROGRAMME CE MOIS-CI -->
    <div class="mb-5">
        <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-4">
            <h2 class="fw-bold text-dark mb-0">
                <i class="fa-solid fa-calendar-check text-danger me-3"></i>Au programme en <?= $mois_fr[$mois_actuel] ?? 'ce mois' ?>
            </h2>
            <span class="badge bg-danger px-3 py-2 rounded-pill fw-semibold"><?= count($evenements_du_mois) ?> Activité(s)</span>
        </div>

        <?php if (empty($evenements_du_mois)): ?>
            <div class="bg-light rounded p-5 text-center border">
                <i class="fa-regular fa-calendar-minus fa-3x text-muted mb-3"></i>
                <h5 class="text-secondary fw-bold">Aucune activité extraordinaire prévue</h5>
                <p class="text-muted small mb-0" style="max-width: 500px; margin: 0 auto;">
                    Il n'y a pas d'événement spécial ou de séminaire planifié pour le reste de ce mois-ci. Nos cultes et réunions de semaine restent actifs !
                </p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($evenements_du_mois as $ev): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm bg-white card-event overflow-hidden">
                            
                            <!-- Zone Image de l'événement -->
                            <div class="position-relative bg-dark" style="height: 200px; overflow: hidden;">
                                <?php 
                                // Utilisation de la colonne 'nom_fichier' issue de ta jointure SQL
                                $image_src = (!empty($ev['nom_fichier'])) ? '/gestion_eglise/uploads/evenements/' . htmlspecialchars($ev['nom_fichier']) : 'assets/img/events/default-event.jpg';
                                ?>
                                <img src="<?= $image_src ?>" class="w-100 h-100 object-fit-cover opacity-85" alt="<?= htmlspecialchars($ev['titre']) ?>">
                                
                                <!-- Petite boîte de date stylisée en surimpression sur l'image -->
                                <div class="position-absolute text-center bg-white rounded p-2 shadow-sm" style="min-width: 60px; right: 15px; top: 15px;">
                                    <span class="d-block fs-4 fw-bold text-dark lh-1"><?= date('d', strtotime($ev['date_evenement'])) ?></span>
                                    <span class="text-uppercase text-muted fw-bold" style="font-size: 10px;"><?= substr($mois_fr[date('m', strtotime($ev['date_evenement']))], 0, 4) ?>.</span>
                                </div>
                            </div>

                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <span class="badge bg-danger bg-opacity-10 text-danger fw-bold px-2.5 py-1.5 small">
                                        <i class="fa-solid fa-tag me-1.5 small"></i><?= htmlspecialchars($ev['type_evenement']) ?>
                                    </span>
                                </div>

                                <h4 class="card-title fw-bold text-dark mb-2"><?= htmlspecialchars($ev['titre']) ?></h4>
                                <p class="card-text text-muted small mb-0" style="line-height: 1.6;">
                                    <?= htmlspecialchars($ev['description'] ?? 'Rejoignez-nous pour ce moment exceptionnel de communion et d\'édification spirituelle. Entrée libre pour tous.') ?>
                                </p>
                            </div>

                            <div class="card-footer bg-light border-0 px-4 py-3 border-top d-flex justify-content-between align-items-center text-muted small">
                                <span>
                                    <i class="fa-solid fa-location-dot text-danger me-2"></i><?= htmlspecialchars($ev['lieu'] ?? 'Au temple') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- SECTION 2 : ÉVÉNEMENTS PROCHAINES DATES (Mois suivants) -->
    <div class="mt-5 pt-4">
        <div class="border-bottom pb-3 mb-4">
            <h3 class="fw-bold text-secondary mb-0">
                <i class="fa-solid fa-calendar-days text-primary me-3"></i>Prochainement dans l'année
            </h3>
        </div>

        <?php if (empty($evenements_futurs)): ?>
            <p class="text-muted small italic">Les dates des mois suivants sont en cours de validation par le conseil pastoral et seront publiées très bientôt.</p>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($evenements_futurs as $ev): ?>
                    <div class="col-12">
                        <div class="p-3 bg-white rounded border shadow-sm d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 card-event">
                            <div class="d-flex align-items-center gap-3">
                                <!-- Boîte de date linéaire -->
                                <div class="text-center bg-primary bg-opacity-10 text-primary rounded p-3 fw-bold border border-primary border-opacity-25 flex-shrink-0" style="width: 80px;">
                                    <span class="fs-3 d-block lh-1"><?= date('d', strtotime($ev['date_evenement'])) ?></span>
                                    <span class="small text-uppercase" style="font-size: 11px;"><?= substr($mois_fr[date('m', strtotime($ev['date_evenement']))], 0, 3) ?></span>
                                </div>
                                <div>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary mb-1 small fw-semibold"><?= htmlspecialchars($ev['type_evenement']) ?></span>
                                    <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($ev['titre']) ?></h5>
                                    <p class="text-muted small mb-0 text-truncate d-none d-md-block" style="max-width: 600px;">
                                        <?= htmlspecialchars($ev['description'] ?? 'Pas de description supplémentaire pour le moment.') ?>
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Informations complémentaires à droite -->
                            <div class="d-flex flex-row flex-md-column align-items-md-end gap-3 gap-md-1 border-top border-md-top-0 pt-2 pt-md-0 justify-content-between text-muted small">
                                <div class="text-truncate" style="max-width: 180px;"><i class="fa-solid fa-location-dot me-2 text-primary"></i><?= htmlspecialchars($ev['lieu'] ?? 'Au temple') ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Styles spécifiques locaux -->
<style>
    .card-event {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .card-event:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,.06) !important;
    }
</style>

<?php 
// Inclusion du pied de page public
require_once 'includes/public_footer.php'; 
?>