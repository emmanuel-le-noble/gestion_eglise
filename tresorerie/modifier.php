<?php
// eglise_db/tresorerie/modifier.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'tresorerie');


$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = "";
$erreur = "";

// Récupérer la liste des membres pour le select
$membres = $pdo->query("SELECT id, matricule, nom, prenoms FROM membres ORDER BY nom ASC")->fetchAll();

try {
    $stmt = $pdo->prepare("SELECT * FROM tresorerie WHERE id = ?");
    $stmt->execute([$id]);
    $op = $stmt->fetch();

    if (!$op) {
        die("Opération introuvable.");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $type = $_POST['type_mouvement'];
        $categorie = $_POST['categorie'];
        $montant = $_POST['montant'];
        $date_op = $_POST['date_operation'];
        $cat_nominatives = ['Dîme', 'Don', 'Action de grâce'];
        $membre_id = (!empty($_POST['membre_id']) && in_array($categorie, $cat_nominatives)) ? $_POST['membre_id'] : null;
        $libelle = trim($_POST['libelle']);
        
        $nom_fichier = $op['piece_justificative']; // Garde l'ancienne par défaut

        // Si une nouvelle pièce jointe est envoyée
        if (!empty($_FILES['piece_justificative']['name'])) {
            $upload_dir = "../assets/uploads/pieces/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            // Supprimer l'ancienne pièce physique du serveur si elle existe
            if ($op['piece_justificative'] && file_exists($upload_dir . $op['piece_justificative'])) {
                unlink($upload_dir . $op['piece_justificative']);
            }

            $extension = pathinfo($_FILES['piece_justificative']['name'], PATHINFO_EXTENSION);
            $nom_fichier = "PIECE_" . time() . "_" . uniqid() . "." . $extension;
            move_uploaded_file($_FILES['piece_justificative']['tmp_name'], $upload_dir . $nom_fichier);
        }

        if (!empty($type) && !empty($montant)) {
            $sql = "UPDATE tresorerie SET type_mouvement = ?, categorie = ?, montant = ?, date_operation = ?, membre_id = ?, libelle = ?, piece_justificative = ? WHERE id = ?";
            $updateStmt = $pdo->prepare($sql);
            $updateStmt->execute([$type, $categorie, $montant, $date_op, $membre_id, $libelle, $nom_fichier, $id]);
            
            $message = "Opération mise à jour avec succès !";
            // Recharger les données fraîches
            $stmt->execute([$id]);
            $op = $stmt->fetch();
        }
    }

    $page_title = "Modifier l'opération N° " . $op['id'];
    require_once '../includes/header.php';

} catch (PDOException $e) {
    $erreur = "Erreur : " . $e->getMessage();
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between">
                    <h5 class="mb-0 text-warning fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i>Modifier l'opération N° <?= $op['id'] ?></h5>
                    <a href="index.php" class="btn btn-light btn-sm border">Annuler</a>
                </div>
                <div class="card-body p-4">
                    <?php if($message): ?> <div class="alert alert-success border-0 shadow-sm"><i class="fa-solid fa-check-circle me-2"></i><?= $message ?></div> <?php endif; ?>
                    <?php if($erreur): ?> <div class="alert alert-danger"><?= $erreur ?></div> <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small fw-bold">Type de mouvement</label> 
                                <select name="type_mouvement" id="type_mvmt" class="form-select border-2" required>
                                    <option value="ENTREE" <?= $op['type_mouvement'] === 'ENTREE' ? 'selected' : '' ?>>ENTRÉE (Recette)</option>
                                    <option value="SORTIE" <?= $op['type_mouvement'] === 'SORTIE' ? 'selected' : '' ?>>SORTIE (Dépense)</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="small fw-bold">Catégorie</label>
                                <select name="categorie" id="categorie" class="form-select border-2" required>
                                    <?php 
                                    $categories = ["Offrande", "Dîme", "Don", "Action de grâce", "Loyer", "Electricité/Eau", "Social", "Autre"];
                                    foreach($categories as $cat) {
                                        $selected = ($op['categorie'] === $cat) ? 'selected' : '';
                                        echo "<option value='$cat' $selected>$cat</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="small fw-bold text-primary">Montant (FCFA)</label>
                                <input type="number" name="montant" class="form-control form-control-lg fw-bold text-primary" value="<?= (int)$op['montant'] ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="small fw-bold">Date de l'opération</label>
                                <input type="date" name="date_operation" class="form-control" value="<?= $op['date_operation'] ?>" required>
                            </div>

                            <div class="col-12" id="zone_membre" style="display:none;">
                                <div class="p-3 bg-light rounded shadow-sm border-start border-primary border-4">
                                    <label class="small fw-bold text-primary"><i class="fa-solid fa-user me-2"></i>Attribuer au fidèle</label>
                                    <select name="membre_id" class="form-select">
                                        <option value="">-- Sélectionner le fidèle --</option>
                                        <?php foreach($membres as $m): ?>
                                            <option value="<?= $m['id'] ?>" <?= $op['membre_id'] == $m['id'] ? 'selected' : '' ?>><?= $m['matricule'] ?> - <?= $m['nom'] ?> <?= $m['prenoms'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="small fw-bold">Libellé / Description</label>
                                <textarea name="libelle" class="form-control" rows="2"><?= htmlspecialchars($op['libelle'] ?? '') ?></textarea>
                            </div>

                            <div class="col-12">
                                <label class="small fw-bold">Changer la pièce justificative (Optionnel)</label>
                                <input type="file" name="piece_justificative" class="form-control" accept="image/*,.pdf">
                                <?php if($op['piece_justificative']): ?>
                                    <small class="text-success d-block mt-1"><i class="fa-solid fa-paperclip me-1"></i>Fichier actuel : <?= $op['piece_justificative'] ?></small>
                                <?php endif; ?>
                            </div>

                            <div class="col-12 text-end mt-4">
                                <button type="submit" class="btn btn-warning px-5 shadow-sm fw-bold">
                                    <i class="fa-solid fa-pen-to-square me-2"></i>Enregistrer les modifications
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('type_mvmt');
        const catSelect = document.getElementById('categorie');
        const zoneMembre = document.getElementById('zone_membre');

        // Définition des options d'origine
        const options = {
            'ENTREE': ['Offrande', 'Dîme', 'Don', 'Action de grâce', 'Autre'],
            'SORTIE': ['Loyer', 'Electricité/Eau', 'Social', 'Maintenance', 'Achat Matériel', 'Evangélisation', 'Autre']
        };

        function updateCategories() {
            const selectedType = typeSelect.value;
            const currentOptions = options[selectedType];
            
            // Vider le menu actuel
            catSelect.innerHTML = '';

            // Remplir avec les nouvelles options
            currentOptions.forEach(function(cat) {
                const option = document.createElement('option');
                option.value = cat;
                option.textContent = cat;
                catSelect.appendChild(option);
            });

            // Relancer la vérification pour la zone membre
            toggleMembre();
        }

        function toggleMembre() {
            const categoriesNominatives = ['Dîme', 'Don', 'Action de grâce'];
            if (typeSelect.value === 'ENTREE' && categoriesNominatives.includes(catSelect.value)) {
                zoneMembre.style.display = 'block';
            } else {
                zoneMembre.style.display = 'none';
            }
        }

        // Écouteurs d'événements
        typeSelect.addEventListener('change', updateCategories);
        catSelect.addEventListener('change', toggleMembre);

        // Initialisation au chargement de la page
        updateCategories();
    });
</script>

<?php require_once '../includes/footer.php'; ?>