<?php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'communication');

$page_title = "Modifier l'événement";
require_once '../includes/header.php';

// Initialisation de la variable pour les messages d'alerte
$message = "";

// Vérification de la présence de l'ID de l'événement
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='alert alert-danger container mt-4'><i class='fa-solid fa-circle-exclamation me-2'></i>ID de l'événement manquant.</div>";
    require_once '../includes/footer.php';
    exit;
}

$evenement_id = $_GET['id'];

// --- ACTION : SUPPRESSION D'UNE PHOTO INDIVIDUELLE ---
if (isset($_GET['delete_photo']) && !empty($_GET['delete_photo'])) {
    $photo_id_to_delete = $_GET['delete_photo'];
    
    try {
        // 1. Récupérer le nom du fichier pour pouvoir le supprimer du disque
        $stmt_find = $pdo->prepare("SELECT nom_fichier FROM evenement_photos WHERE id = ? AND evenement_id = ?");
        $stmt_find->execute([$photo_id_to_delete, $evenement_id]);
        $photo_to_delete = $stmt_find->fetch();

        if ($photo_to_delete) {
            $file_path = "../assets/uploads/evenements/" . $photo_to_delete['nom_fichier'];
            
            // Supprimer le fichier physique s'il existe
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            // Supprimer la ligne en base de données
            $stmt_del = $pdo->prepare("DELETE FROM evenement_photos WHERE id = ?");
            $stmt_del->execute([$photo_id_to_delete]);
            
            $message = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                            <i class='fa-solid fa-circle-check me-2'></i>La photo a été retirée avec succès.
                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                        </div>";
        }
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                        <i class='fa-solid fa-circle-exclamation me-2'></i>Erreur lors de la suppression de la photo : " . htmlspecialchars($e->getMessage()) . "
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                    </div>";
    }
}

// 1. Récupération des données existantes de l'événement
try {
    $stmt = $pdo->prepare("SELECT * FROM evenements WHERE id = ?");
    $stmt->execute([$evenement_id]);
    $evenement = $stmt->fetch();

    if (!$evenement) {
        echo "<div class='alert alert-danger container mt-4'><i class='fa-solid fa-circle-exclamation me-2'></i>Événement introuvable.</div>";
        require_once '../includes/footer.php';
        exit;
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger container mt-4'><i class='fa-solid fa-circle-exclamation me-2'></i>Erreur de base de données : " . $e->getMessage() . "</div>";
    require_once '../includes/footer.php';
    exit;
}

// 2. Traitement de la modification générale (Soumission du formulaire)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = $_POST['titre'];
    $type = $_POST['type_evenement'];
    $date = $_POST['date_evenement'];
    $lieu = $_POST['lieu'];
    $desc = $_POST['description'];

    try {
        $pdo->beginTransaction();

        // Mise à jour de l'événement principal
        $stmt = $pdo->prepare("UPDATE evenements SET titre = ?, type_evenement = ?, date_evenement = ?, lieu = ?, description = ? WHERE id = ?");
        $stmt->execute([$titre, $type, $date, $lieu, $desc, $evenement_id]);

        // Gestion Multi-Upload des nouvelles photos (si ajoutées)
        if (!empty($_FILES['photos']['name'][0]) && $_FILES['photos']['error'][0] !== UPLOAD_ERR_NO_FILE) {
            $upload_dir = "../assets/uploads/evenements/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['photos']['error'][$key] !== UPLOAD_ERR_OK) continue;

                $ext = strtolower(pathinfo($_FILES['photos']['name'][$key], PATHINFO_EXTENSION));
                $nom_photo = "EVT_" . $evenement_id . "_" . uniqid() . "." . $ext;
                
                if (move_uploaded_file($tmp_name, $upload_dir . $nom_photo)) {
                    $pdo->prepare("INSERT INTO evenement_photos (evenement_id, nom_fichier) VALUES (?, ?)")
                        ->execute([$evenement_id, $nom_photo]);
                }
            }
        }

        $pdo->commit();
        $message = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                        <i class='fa-solid fa-circle-check me-2'></i>Événement mis à jour avec succès !
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                    </div>";
        
        // Rafraîchir les données locales pour l'affichage à jour dans le formulaire
        $evenement['titre'] = $titre;
        $evenement['type_evenement'] = $type;
        $evenement['date_evenement'] = $date;
        $evenement['lieu'] = $lieu;
        $evenement['description'] = $desc;

    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                        <i class='fa-solid fa-circle-exclamation me-2'></i>Erreur lors de la modification : " . htmlspecialchars($e->getMessage()) . "
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                    </div>";
    }
}

// Récupération actualisée des photos pour l'affichage de la galerie miniatures
$stmt_photos = $pdo->prepare("SELECT * FROM evenement_photos WHERE evenement_id = ?");
$stmt_photos->execute([$evenement_id]);
$photos_actuelles = $stmt_photos->fetchAll();
?>

<div class="container mt-4">
    <div class="col-md-8 mx-auto">
        
        <!-- Affichage des alertes de confirmation ou d'erreur -->
        <?= $message ?>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-primary"><i class="fa-solid fa-calendar-check me-2"></i>Modifier l'événement</h5>
                <a href="index.php" class="btn btn-light btn-sm border">Retour</a>
            </div>
            <div class="card-body p-4">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="small fw-bold mb-1">Titre de l'événement</label>
                            <input type="text" name="titre" class="form-control" value="<?= htmlspecialchars($evenement['titre']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold mb-1">Type</label>
                            <select name="type_evenement" class="form-select">
                                <?php
                                $types = ['Baptême', 'Mariage', 'Fête', 'Concert', 'Réveils', "Sorties d'enfants", 'Autre'];
                                foreach ($types as $t) {
                                    $selected = ($evenement['type_evenement'] === $t) ? 'selected' : '';
                                    echo "<option value=\"" . htmlspecialchars($t) . "\" $selected>$t</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold mb-1">Date</label>
                            <input type="date" name="date_evenement" class="form-control" value="<?= $evenement['date_evenement'] ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold mb-1">Lieu</label>
                            <input type="text" name="lieu" class="form-control" value="<?= htmlspecialchars($evenement['lieu']) ?>">
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold mb-1">Description</label>
                            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($evenement['description']) ?></textarea>
                        </div>
                        
                        <!-- Affichage des photos avec icône croix de suppression -->
                        <?php if (!empty($photos_actuelles)): ?>
                            <div class="col-12">
                                <label class="small fw-bold d-block mb-2">Photos actuelles (Cliquez sur la croix pour supprimer)</label>
                                <div class="d-flex flex-wrap gap-3">
                                    <?php foreach ($photos_actuelles as $photo): ?>
                                        <div class="position-relative border rounded p-1" style="width: 90px; height: 90px;">
                                            <img src="../assets/uploads/evenements/<?= $photo['nom_fichier'] ?>" class="w-100 h-100 object-fit-cover rounded" alt="Photo événement">
                                            
                                            <!-- Bouton de suppression (Croix rouge) -->
                                            <a href="modifier.php?id=<?= $evenement_id ?>&delete_photo=<?= $photo['id'] ?>" 
                                               class="position-absolute top-0 start-100 translate-middle badge rounded-circle bg-danger text-white border border-white p-1"
                                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer définitivement cette photo ?');"
                                               style="cursor: pointer; text-decoration: none; font-size: 10px; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fa-solid fa-xmark"></i>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="col-12 mt-3">
                            <label class="small fw-bold mb-1">Ajouter de nouvelles photos (Multi-sélection possible)</label>
                            <input type="file" name="photos[]" class="form-control" multiple accept="image/*">
                        </div>
                        <div class="col-12 text-end mt-4">
                            <button type="submit" class="btn btn-primary px-5">Enregistrer les modifications</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>