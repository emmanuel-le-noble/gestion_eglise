<?php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'communication');

$page_title = "Nouvel événement";
require_once '../includes/header.php';

// Initialisation de la variable pour les messages d'alerte
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = $_POST['titre'];
    $type = $_POST['type_evenement'];
    $date = $_POST['date_evenement'];
    $lieu = $_POST['lieu'];
    $desc = $_POST['description'];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO evenements (titre, type_evenement, date_evenement, lieu, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$titre, $type, $date, $lieu, $desc]);
        $evenement_id = $pdo->lastInsertId();

        // Gestion Multi-Upload des photos (Vérification si un fichier a bien été soumis sans erreur)
        if (!empty($_FILES['photos']['name'][0]) && $_FILES['photos']['error'][0] !== UPLOAD_ERR_NO_FILE) {
            $upload_dir = "../assets/uploads/evenements/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
                // Sécurité additionnelle : sauter le fichier si l'upload a échoué pour celui-ci
                if ($_FILES['photos']['error'][$key] !== UPLOAD_ERR_OK) continue;

                $ext = strtolower(pathinfo($_FILES['photos']['name'][$key], PATHINFO_EXTENSION));
                
                // Optionnel : Vous devriez valider les extensions ici (jpg, jpeg, png, webp)
                $nom_photo = "EVT_" . $evenement_id . "_" . uniqid() . "." . $ext;
                
                if (move_uploaded_file($tmp_name, $upload_dir . $nom_photo)) {
                    $pdo->prepare("INSERT INTO evenement_photos (evenement_id, nom_fichier) VALUES (?, ?)")
                        ->execute([$evenement_id, $nom_photo]);
                }
            }
        }

        $pdo->commit();
        $message = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                        <i class='fa-solid fa-circle-check me-2'></i>Événement et photos enregistrés avec succès !
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                    </div>";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                        <i class='fa-solid fa-circle-exclamation me-2'></i>Erreur : " . htmlspecialchars($e->getMessage()) . "
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                    </div>";
    }
}
?>

<div class="container mt-4">
    <div class="col-md-8 mx-auto">
        
        <!-- Zone d'affichage des messages Bootstrap intégrée proprement -->
        <?= $message ?>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-primary"><i class="fa-solid fa-calendar-plus me-2"></i>Créer un événement</h5>
                <a href="index.php" class="btn btn-light btn-sm border">Retour</a>
            </div>
            <div class="card-body p-4">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="small fw-bold mb-1">Titre de l'événement</label>
                            <input type="text" name="titre" class="form-control" placeholder="Ex: Grand Baptême de Pâques" required>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold mb-1">Type</label>
                            <select name="type_evenement" class="form-select">
                                <option value="Baptême">Baptême</option>
                                <option value="Mariage">Mariage</option>
                                <option value="Fête">Fête</option>
                                <option value="Concert">Concert</option>
                                <option value="Réveils">Réveils</option>
                                <option value="Sorties d'enfants">Sorties d'enfants</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold mb-1">Date</label>
                            <input type="date" name="date_evenement" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold mb-1">Lieu</label>
                            <input type="text" name="lieu" class="form-control" placeholder="Ex: Temple principal">
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold mb-1">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold mb-1">Photos (Multi-sélection possible)</label>
                            <input type="file" name="photos[]" class="form-control" multiple accept="image/*">
                        </div>
                        <div class="col-12 text-end mt-4">
                            <button type="submit" class="btn btn-primary px-5">Enregistrer l'événement</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>