<?php
// eglise_db/evenements/index.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'communication');

$page_title = "Galerie des événements"; 
require_once '../includes/header.php'; 

$total_ev = $pdo->query("SELECT COUNT(*) FROM evenements")->fetchColumn();
$total_photos = $pdo->query("SELECT COUNT(*) FROM evenement_photos")->fetchColumn();

$sql = "SELECT e.*, 
        (SELECT nom_fichier FROM evenement_photos WHERE evenement_id = e.id ORDER BY id ASC LIMIT 1) as couverture, 
        (SELECT COUNT(*) FROM evenement_photos WHERE evenement_id = e.id) as nb_photos 
        FROM evenements e 
        ORDER BY e.date_evenement DESC";
$evenements = $pdo->query($sql)->fetchAll();
?>


<?php if (isset($_GET['status']) && $_GET['status'] === 'deleted'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i>L'événement et toutes ses photos ont été supprimés avec succès.
        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
    </div>
<?php endif; ?>

<div class="container mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold text-dark m-0">Activités & événements</h2>
            <p class="text-muted m-0">Gérez l'historique visuel de la communauté (<?= $total_ev ?> événements, <?= $total_photos ?> photos)</p>
        </div>
        <div class="d-flex gap-2">
            <a href="rapports.php" class="btn btn-outline-primary btn-sm d-flex align-items-center">
                <i class="fa-solid fa-calendar me-2"></i>Rapport d'activités
            </a>
            <a href="ajouter.php" class="btn btn-primary shadow-sm d-flex align-items-center">
                <i class="fa-solid fa-plus me-2"></i>Nouvel événement
            </a>
        </div>
    </div>

    <div class="row g-4">
        <?php if(empty($evenements)): ?>
            <div class="col-12 text-center py-5">
                <div class="text-muted">
                    <i class="fa-solid fa-calendar-xmark fa-3x mb-3"></i>
                    <p>Aucun événement enregistré pour le moment.</p>
                    <a href="ajouter.php" class="btn btn-outline-primary btn-sm">Commencer maintenant</a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach($evenements as $ev): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100 overflow-hidden">
                        <div class="position-relative">
                            <?php if($ev['couverture']): ?>
                                <img src="../assets/uploads/evenements/<?= $ev['couverture'] ?>" 
                                     class="card-img-top" 
                                     style="height: 220px; object-fit: cover;" 
                                     alt="<?= htmlspecialchars($ev['titre']) ?>">
                            <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center" style="height: 220px;">
                                    <i class="fa-solid fa-image fa-3x text-secondary opacity-25"></i>
                                </div>
                            <?php endif; ?>
                            
                            <span class="position-absolute top-0 end-0 m-3 badge bg-dark bg-opacity-75 px-3 py-2">
                                <?= htmlspecialchars($ev['type_evenement']) ?>
                            </span>
                        </div>

                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title fw-bold text-dark mb-0"><?= htmlspecialchars($ev['titre']) ?></h5>
                            </div>
                            
                            <div class="small text-muted mb-3">
                                <i class="fa-solid fa-calendar-day me-2"></i><?= date('d/m/Y', strtotime($ev['date_evenement'])) ?>
                                <span class="mx-2">|</span>
                                <i class="fa-solid fa-location-dot me-2"></i><?= htmlspecialchars($ev['lieu'] ?: 'Non spécifié') ?>
                            </div>

                            <p class="card-text text-secondary small">
                                <?php 
                                if (!empty($ev['description'])) {
                                    echo htmlspecialchars(mb_strimwidth($ev['description'], 0, 80, "..."));
                                } else {
                                    echo "Aucune description disponible.";
                                }
                                ?>
                            </p>
                        </div>

                        <div class="card-footer bg-white border-0 pb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small text-primary fw-semibold">
                                    <i class="fa-solid fa-camera-retro me-1"></i> <?= $ev['nb_photos'] ?> photo(s)
                                </span>
                                <div class="btn-group">
                                    <a href="voir.php?id=<?= $ev['id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-eye me-1"></i> Galerie
                                    </a>
                                    <a href="modifier.php?id=<?= $ev['id'] ?>" class="btn btn-sm btn-light border" title="Modifier">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
    .card { transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out; }
    .card:hover { transform: translateY(-5px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
    .card-img-top { transition: opacity 0.3s; }
    .card:hover .card-img-top { opacity: 0.9; }
</style>

<?php require_once '../includes/footer.php'; ?>