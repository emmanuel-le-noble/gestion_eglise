<?php
// eglise_db/mutuelle/adhesion.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'mutuelle');

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $membre_id = $_POST['membre_id'];
    $date_adhesion = $_POST['date_adhesion'];
    $mise_journaliere = (float)$_POST['mise_journaliere'];

    if ($mise_journaliere >= 0) {
        try {
            // Insertion incluant la mise journalière (les frais mensuels se calculent tout seuls en BD)
            $sql = "INSERT INTO mutuelle_comptes (membre_id, date_adhesion, `mise_journaliere`) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$membre_id, $date_adhesion, $mise_journaliere]);
            $message = "<div class='alert alert-success shadow-sm'><i class='fa-solid fa-circle-check me-2'></i>Le membre a été inscrit à la mutuelle avec succès !</div>";
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'><i class='fa-solid fa-circle-exclamation me-2'></i>Erreur : Ce membre est peut-être déjà inscrit ou les données sont invalides.</div>";
        }
    } else {
        $message = "<div class='alert alert-warning'><i class='fa-solid fa-triangle-exclamation me-2'></i>La mise journalière doit être un montant positif ou égal à zéro.</div>";
    }
}

// Récupérer les membres qui ne sont PAS ENCORE dans la mutuelle
$sql_membres = "SELECT id, matricule, nom, prenoms 
                FROM membres 
                WHERE id NOT IN (SELECT membre_id FROM mutuelle_comptes) 
                ORDER BY nom ASC";
$membres_disponibles = $pdo->query($sql_membres)->fetchAll();

$page_title = "Adhésion à la mutuelle"; 
require_once '../includes/header.php'; 
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-primary"><i class="fa-solid fa-user-plus me-2"></i>Nouvelle adhésion à la mutuelle</h5>
                </div>
                <div class="card-body p-4">
                    <?= $message ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="small fw-bold mb-2">Sélectionner le fidèle</label>
                            <select name="membre_id" class="form-select select2" required>
                                <option value="">-- Choisir un membre --</option>
                                <?php foreach($membres_disponibles as $m): ?>
                                    <option value="<?= $m['id'] ?>">
                                        <?= $m['matricule'] ?> - <?= htmlspecialchars($m['nom']) ?> <?= htmlspecialchars($m['prenoms']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text text-muted small">Seuls les membres non inscrits à la mutuelle apparaissent ici.</div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="small fw-bold mb-2">Mise journalière fixe (FCFA)</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="50" name="mise_journaliere" id="mise_journaliere" class="form-control" placeholder="Ex: 500" required>
                                    <span class="input-group-text bg-light text-muted fw-bold">F</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted mb-2">Tenue mensuelle estimée</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" id="frais_estimation" class="form-control bg-light" readonly value="0">
                                    <span class="input-group-text bg-light text-muted small">F / mois</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-text text-danger-emphasis small bg-light p-2 rounded border border-light-subtle">
                                    <i class="fa-solid fa-circle-info me-1"></i> Les frais de tenue de compte mensuels s'élèvent à la moitié de la mise journalière.
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="small fw-bold mb-2">Date d'adhésion</label>
                            <input type="date" name="date_adhesion" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-sm fw-bold shadow-sm py-2">
                                <i class="fa-solid fa-circle-check me-1"></i> Confirmer l'adhésion
                            </button>
                            <a href="membres_mutuelle.php" class="btn btn-light btn-sm text-muted text-decoration-none border">
                                <i class="fa-solid fa-list me-1"></i> Voir la liste des adhérents
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="text-center mt-3">
                <a href="index.php" class="btn btn-outline-dark btn-sm">
                    <i class="fa fa-arrow-left me-1"></i> Tableau de bord mutuelle
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputMise = document.getElementById('mise_journaliere');
    const inputFrais = document.getElementById('frais_estimation');

    if(inputMise && inputFrais) {
        inputMise.addEventListener('input', function() {
            const mise = parseFloat(this.value) || 0;
            // Division par 2 en temps réel (Règle de gestion)
            const frais = mise / 2; 
            inputFrais.value = frais % 1 === 0 ? frais : frais.toFixed(2);
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>