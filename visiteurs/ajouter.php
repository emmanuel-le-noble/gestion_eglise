<?php
require_once "../config/database.php";
require_once '../includes/helpers.php';
$page_title = "Nouveau Visiteur"; 
require_once '../includes/header.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "INSERT INTO visiteurs (nom_prenoms, telephone, quartier, invite_par, date_visite, observations, utilisateur_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['nom_prenoms'], $_POST['telephone'], $_POST['quartier'], 
        $_POST['invite_par'], $_POST['date_visite'], $_POST['observations'], $_SESSION['user_id']
    ]);
    echo "<div class='alert alert-success'>Visiteur enregistré avec succès !</div>";
}
?>

<div class="container mt-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-warning text-dark fw-bold">
            <i class="fa-solid fa-user-plus me-2"></i> Enregistrer un nouveau visiteur
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="small fw-bold">Nom et Prénoms</label>
                        <input type="text" name="nom_prenoms" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold">Téléphone</label>
                        <input type="text" name="telephone" class="form-control" placeholder="Ex: +228 00 00 00 00">
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold">Quartier / Domicile</label>
                        <input type="text" name="quartier" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold">Invité par (Membre)</label>
                        <input type="text" name="invite_par" class="form-control" placeholder="Qui a invité ce visiteur ?">
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold">Date de la visite</label>
                        <input type="date" name="date_visite" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-12">
                        <label class="small fw-bold">Premières impressions / Observations</label>
                        <textarea name="observations" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="col-12 d-flex justify-content-between">
                        <a href="index.php" class="btn btn-light btn-sm border">Retour</a>
                        <button type="submit" class="btn btn-warning px-4 fw-bold">Enregistrer le visiteur</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>