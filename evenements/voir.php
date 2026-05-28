<?php
// eglise_db/evenements/voir.php
require_once "../config/database.php";
securiser_par_module($pdo, 'communication');

$page_title = "Détails de l'événement"; 
require_once '../includes/header.php'; 

// Sécurisation de l'ID par un transtypage en entier (int)
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$id) {
    header("Location: index.php");
    exit;
}

// 1. Récupération de l'événement
$stmt = $pdo->prepare("SELECT * FROM evenements WHERE id = ?");
$stmt->execute([$id]);
$evenement = $stmt->fetch();

if (!$evenement) {
    echo "<div class='container mt-5'><div class='alert alert-danger'><i class='fa-solid fa-circle-exclamation me-2'></i>Événement introuvable.</div></div>";
    require_once '../includes/footer.php';
    exit;
}

// 2. Récupération de toutes les photos
$stmt_photos = $pdo->prepare("SELECT * FROM evenement_photos WHERE evenement_id = ?");
$stmt_photos->execute([$id]);
$photos = $stmt_photos->fetchAll();
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="btn btn-primary btn-sm">Retour aux événements</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($evenement['titre']) ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Colonne Gauche : Infos de l'événement -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px; z-index: 1000;">
                <div class="card-body p-4">
                    <span class="badge bg-primary mb-3"><?= htmlspecialchars($evenement['type_evenement']) ?></span>
                    <h3 class="fw-bold text-dark mb-3"><?= htmlspecialchars($evenement['titre']) ?></h3>
                    
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2 text-muted">
                            <i class="fa-solid fa-calendar-day me-2 text-primary"></i> 
                            <strong>Date :</strong> <?= date('d/m/Y', strtotime($evenement['date_evenement'])) ?>
                        </li>
                        <li class="mb-2 text-muted">
                            <i class="fa-solid fa-location-dot me-2 text-primary"></i> 
                            <strong>Lieu :</strong> <?= htmlspecialchars($evenement['lieu'] ?: 'Non spécifié') ?>
                        </li>
                        <li class="mb-2 text-muted">
                            <i class="fa-solid fa-images me-2 text-primary"></i> 
                            <strong>Photos :</strong> <?= count($photos) ?> fichiers
                        </li>
                    </ul>

                    <hr>

                    <h6 class="fw-bold small text-uppercase text-muted mb-2">Description</h6>
                    <p class="text-secondary small mb-4">
                        <?= nl2br(htmlspecialchars($evenement['description'] ?: 'Aucune description fournie.')) ?>
                    </p>

                    <div class="d-grid gap-2">
                        <a href="modifier.php?id=<?= $id ?>" class="btn btn-outline-dark btn-sm">
                            <i class="fa-solid fa-pen-to-square me-2"></i>Modifier les infos
                        </a>
                        <button onclick="confirmDeletion(<?= $id ?>)" class="btn btn-outline-danger btn-sm">
                            <i class="fa-solid fa-trash me-2"></i>Supprimer l'album
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne Droite : Galerie Photos -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-camera-retro me-2"></i>Galerie Photos</h5>
                </div>
                <div class="card-body p-3">
                    <?php if (empty($photos)): ?>
                        <div class="text-center py-5">
                            <i class="fa-solid fa-images fa-3x text-secondary opacity-25 mb-3"></i>
                            <p class="text-muted">Aucune photo n'a été ajoutée à cet album.</p>
                            <a href="modifier.php?id=<?= $id ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fa-solid fa-plus me-1"></i>Ajouter des photos
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row g-2">
                            <?php foreach ($photos as $p): ?>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="photo-item position-relative overflow-hidden rounded shadow-sm">
                                        <img src="../assets/uploads/evenements/<?= $p['nom_fichier'] ?>" class="img-fluid w-100" style="height: 150px; object-fit: cover;" alt="Photo de l'événement">
                                        
                                        <div class="photo-overlay d-flex align-items-center justify-content-center">
                                            <a href="../assets/uploads/evenements/<?= $p['nom_fichier'] ?>" target="_blank" class="btn btn-sm btn-light mx-1" title="Agrandir l'image">
                                                <i class="fa-solid fa-expand"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Styles pour l'effet de survol de la galerie */
    .photo-item .photo-overlay {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.4);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .photo-item:hover .photo-overlay { opacity: 1; }
    .photo-item img { transition: transform 0.5s ease; }
    .photo-item:hover img { transform: scale(1.08); }
</style>

<script>
    function confirmDeletion(id) {
        if (confirm("Êtes-vous sûr de vouloir supprimer cet événement et TOUTES les photos associées ? Cette action est irréversible.")) {
            window.location.href = "supprimer.php?id=" + id;
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>