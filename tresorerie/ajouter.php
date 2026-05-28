<?php
// eglise_db/tresorerie/ajouter.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'tresorerie');

$page_title = "Nouvelle opération financière"; 
require_once '../includes/header.php'; 

$message = "";
$erreur = "";
$annee_courante = date('Y');

// --- 1. CHARGEMENT DYNAMIQUE DES LIGNES BUDGÉTAIRES DE L'ANNÉE ---
try {
    $sql_lignes = "SELECT l.id, l.libelle, b.annee  FROM lignes_budget l JOIN budgets b ON l.budget_id = b.id WHERE b.annee = ? ORDER BY l.libelle ASC";
    $stmt_lignes = $pdo->prepare($sql_lignes);
    $stmt_lignes->execute([$annee_courante]);
    $lignes_disponibles = $stmt_lignes->fetchAll();
} catch (PDOException $e) {
    $erreur = "Erreur de chargement du budget : " . $e->getMessage();
}

// Récupérer la liste des membres
$membres = $pdo->query("SELECT id, matricule, nom, prenoms FROM membres ORDER BY nom ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type_mouvement'];
    $ligne_budget_id = !empty($_POST['ligne_budget_id']) ? (int)$_POST['ligne_budget_id'] : null;
    $categorie = $_POST['categorie']; // Valeur textuelle issue du JS (Ex: Dîme, Loyer)
    $montant = $_POST['montant'];
    $date_op = $_POST['date_operation'];
    
    // On permet l'id membre pour Dîme, Don et Action de grâce
    $cat_nominatives = ['Dîme', 'Don', 'Action de grâce'];
    $membre_id = (!empty($_POST['membre_id']) && in_array($categorie, $cat_nominatives)) ? $_POST['membre_id'] : null;
    $libelle = trim($_POST['libelle']);
    $user_id = $_SESSION['user_id'];

    // --- GESTION DE LA PIÈCE JUSTIFICATIVE ---
    $nom_fichier = null;
    if (!empty($_FILES['piece_justificative']['name'])) {
        $upload_dir = "../assets/uploads/pieces/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $extension = pathinfo($_FILES['piece_justificative']['name'], PATHINFO_EXTENSION);
        $nom_fichier = "PIECE_" . time() . "_" . uniqid() . "." . $extension;
        move_uploaded_file($_FILES['piece_justificative']['tmp_name'], $upload_dir . $nom_fichier);
    }

    if (!empty($type) && !empty($montant) && $ligne_budget_id !== null) {
        try {
            // --- AJOUT DE LIGNE_BUDGET_ID DANS LA REQUÊTE ---
            $sql = "INSERT INTO tresorerie (type_mouvement, ligne_budget_id, categorie, montant, date_operation, membre_id, libelle, utilisateur_id, piece_justificative) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$type, $ligne_budget_id, $categorie, $montant, $date_op, $membre_id, $libelle, $user_id, $nom_fichier]);
            
            $message = "Opération enregistrée et liée à la ligne budgétaire avec succès !";
        } catch (PDOException $e) {
            $erreur = "Erreur : " . $e->getMessage();
        }
    } else {
        $erreur = "Veuillez sélectionner une ligne budgétaire valide et remplir les montants.";
    }
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between">
                    <h5 class="mb-0 text-primary fw-bold"><i class="fa-solid fa-money-bill-transfer me-2"></i>Saisir une opération</h5>
                    <a href="index.php" class="btn btn-light btn-sm border">Retour au journal</a>
                </div>
                <div class="card-body p-4">
                    <?php if($message): ?> <div class="alert alert-success border-0 shadow-sm"><i class="fa-solid fa-check-circle me-2"></i><?= $message ?></div> <?php endif; ?>
                    <?php if($erreur): ?> <div class="alert alert-danger border-0 shadow-sm"><?= $erreur ?></div> <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small fw-bold">Type de mouvement</label>
                                <select name="type_mouvement" id="type_mvmt" class="form-select border-2" required>
                                    <option value="ENTREE">ENTRÉE (Recette)</option>
                                    <option value="SORTIE">SORTIE (Dépense)</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="small fw-bold">Nature / Rubrique</label>
                                <select name="categorie" id="categorie" class="form-select border-2" required>
                                    </select>
                            </div>

                            <div class="col-12">
                                <label class="small fw-bold text-dark">Imputation ligne budgétaire (plan prévisionnel <?= $annee_courante ?>)</label>
                                <select name="ligne_budget_id" id="ligne_budget_id" class="form-select border-primary border-opacity-50" required>
                                    <option value="">-- Assigner cette opération à une ligne budgétaire --</option>
                                    <?php foreach($lignes_disponibles as $l): ?>
                                        <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['libelle']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Permet de lier cette dépense ou recette aux objectifs annuels.</small>
                            </div>

                            <div class="col-md-6">
                                <label class="small fw-bold text-primary">Montant (FCFA)</label>
                                <input type="number" name="montant" class="form-control form-control-lg fw-bold text-primary border-primary border-opacity-25" placeholder="Ex: 5000" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="small fw-bold">Date de l'opération</label>
                                <input type="date" name="date_operation" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <div class="col-12" id="zone_membre" style="display:none;">
                                <div class="p-3 bg-light rounded shadow-sm border-start border-primary border-4">
                                    <label class="small fw-bold text-primary"><i class="fa-solid fa-user me-2"></i>Attribuer au fidèle</label>
                                    <select name="membre_id" class="form-select select2">
                                        <option value="">-- Sélectionner le fidèle --</option>
                                        <?php foreach($membres as $m): ?>
                                            <option value="<?= $m['id'] ?>"><?= $m['matricule'] ?> - <?= htmlspecialchars($m['nom'] . ' ' . $m['prenoms']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="small fw-bold">Libellé / description</label>
                                <textarea name="libelle" class="form-control" rows="2" placeholder="Ex: Offrandes du culte de moisson..."></textarea>
                            </div>

                            <div class="col-12">
                                <label class="small fw-bold">Pièce justificative (Image ou PDF)</label>
                                <input type="file" name="piece_justificative" class="form-control" accept="image/*,.pdf">
                                <small class="text-muted">Scan du reçu, ticket, ou facture.</small>
                            </div>

                            <div class="col-12 text-end mt-4">
                                <button type="submit" class="btn btn-primary px-5 shadow-sm">
                                    <i class="fa-solid fa-floppy-disk me-2"></i>Valider l'opération
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