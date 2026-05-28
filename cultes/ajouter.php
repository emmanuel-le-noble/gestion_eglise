<?php
// eglise_db/cultes/ajouter.php
require_once "../config/database.php";
$page_title = "Rapport de culte"; 
require_once '../includes/header.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "INSERT INTO cultes (type_culte, date_culte, nombre_hommes, nombre_femmes, nombre_enfants, nombre_visiteurs, theme_message, predicateur, utilisateur_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['type_culte'], $_POST['date_culte'], $_POST['hommes'], $_POST['femmes'], 
        $_POST['enfants'], $_POST['visiteurs'], $_POST['theme'], $_POST['predicateur'], $_SESSION['user_id']
    ]);
    echo "<div class='alert alert-success'>Rapport enregistré !</div>";
}
?>

<div class="container mt-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Enregistrer un rapport de culte</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="small fw-bold">Type de réunion</label>
                        <select name="type_culte" class="form-select" required>
                            <option value="Culte de Dimanche">Culte de dimanche</option>
                            <option value="Étude Biblique">Étude biblique</option>
                            <option value="Prière de semaine">Prière de semaine</option>
                            <option value="Veillée">Veillée</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold">Date</label>
                        <input type="date" name="date_culte" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="small fw-bold text-primary">Hommes</label>
                        <input type="number" name="hommes" class="form-control" value="0">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-danger">Femmes</label>
                        <input type="number" name="femmes" class="form-control" value="0">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-success">Enfants</label>
                        <input type="number" name="enfants" class="form-control" value="0">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-warning">Visiteurs</label>
                        <input type="number" name="visiteurs" class="form-control" value="0">
                    </div>

                    <div class="col-md-12">
                        <label class="small fw-bold">Thème du message</label>
                        <input type="text" name="theme" class="form-control" placeholder="Le titre du sermon...">
                    </div>
                    <div class="col-md-12">
                        <label class="small fw-bold">Prédicateur</label>
                        <input type="text" name="predicateur" class="form-control" placeholder="Nom de l'orateur">
                    </div>
                    
                    <div class="col-12 d-flex justify-content-between">
                        <a href="index.php" class="btn btn-light btn-sm border">Retour</a>
                        <button type="submit" class="btn btn-primary px-4">Enregistrer le rapport</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>