<?php
// eglise_db/mutuelle/nouveau_pret.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'mutuelle');

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $compte_id = filter_input(INPUT_POST, 'compte_id', FILTER_VALIDATE_INT);
    $montant = (float)($_POST['montant'] ?? 0);
    $taux = (float)($_POST['taux'] ?? 5.00);
    $date_pret = $_POST['date_pret'] ?? date('Y-m-d');
    $user_id = $_SESSION['user_id'];

    if (!$compte_id || $montant <= 0) {
        $message = "<div class='alert alert-danger'><i class='fa-solid fa-triangle-exclamation me-2'></i>Erreur : Les informations saisies sont incorrectes. Le montant doit être supérieur à 0.</div>";
    } else {
        // Calcul de la commission des 5% collectée en espèces
        $commission = ($montant * $taux) / 100;

        try {
            $pdo->beginTransaction();

            // 1. Créer la fiche de prêt sans les dates d'échéances (Remboursement lié aux cotisations)
            $stmt = $pdo->prepare("INSERT INTO mutuelle_prets (compte_id, montant_prete, taux, commission_payee, date_pret, statut) VALUES (?, ?, ?, 'OUI', ?, 'EN_COURS')");
            $stmt->execute([$compte_id, $montant, $taux, $date_pret]);
            $pret_id = $pdo->lastInsertId();

            // 2. Flux de sortie : Enregistrer la remise de l'intégralité du prêt au membre
            $stmt_op = $pdo->prepare("INSERT INTO mutuelle_operations (compte_id, pret_id, type_operation, montant, date_op, utilisateur_id, commentaire) VALUES (?, ?, 'PRET', ?, ?, ?, 'Octroi de prêt (Remboursement automatique via tontine 60%)')");
            $stmt_op->execute([$compte_id, $pret_id, $montant, $date_pret, $user_id]);

            // 3. Flux d'entrée : Enregistrer l'encaissement des 5% de commission pour la caisse de la mutuelle
            $stmt_comm = $pdo->prepare("INSERT INTO mutuelle_operations (compte_id, pret_id, type_operation, montant, date_op, utilisateur_id, commentaire) VALUES (?, ?, 'COMMISSION_PRET', ?, ?, ?, 'Commission de prêt de 5% perçue en espèces')");
            $stmt_comm->execute([$compte_id, $pret_id, $commission, $date_pret, $user_id]);

            // Récupération des informations du membre pour le fichier de log d'audit
            $stmt_info = $pdo->prepare("SELECT m.nom, m.prenoms FROM mutuelle_comptes mc JOIN membres m ON mc.membre_id = m.id WHERE mc.id = ?");
            $stmt_info->execute([$compte_id]);
            $beneficiaire = $stmt_info->fetch();
            $nom_complet = $beneficiaire ? $beneficiaire['nom'] . ' ' . $beneficiaire['prenoms'] : "Inconnu";

            // Journalisation de la validation du prêt
            if (function_exists('enregistrer_log')) {
                enregistrer_log(
                    $pdo, 
                    'Octroi Prêt', 
                    "Prêt ID #$pret_id d'un montant de " . number_format($montant, 0, ',', ' ') . " F octroyé à ($nom_complet). Commission de " . number_format($commission, 0, ',', ' ') . " F perçue."
                );
            }

            $pdo->commit();
            $message = "<div class='alert alert-success shadow-sm'><i class='fa-solid fa-circle-check me-2'></i>Prêt débloqué avec succès ! Commission de " . number_format($commission, 0, ',', ' ') . " F perçue. Le remboursement s'appliquera automatiquement à hauteur de 60% sur les prochaines cotisations.</div>";
        } catch (Exception $e) {
            $pdo->rollBack();
            if (function_exists('enregistrer_log')) {
                enregistrer_log($pdo, 'Erreur Transactionnelle', "Échec de l'octroi du prêt pour le compte ID $compte_id. Erreur : " . $e->getMessage());
            }
            $message = "<div class='alert alert-danger'><i class='fa-solid fa-triangle-exclamation me-2'></i>Erreur système : Échec de l'opération financière.</div>";
        }
    }
}

// Récupérer la liste des membres actifs
$membres = $pdo->query("SELECT mc.id, m.nom, m.prenoms, m.matricule, mc.solde_tontine FROM mutuelle_comptes mc JOIN membres m ON mc.membre_id = m.id WHERE mc.statut = 'ACTIF' ORDER BY m.nom ASC")->fetchAll();

$page_title = "Nouveau prêt"; 
require_once '../includes/header.php'; 
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-danger"><i class="fa-solid fa-hand-holding-dollar me-2"></i>Accorder un prêt</h5>
                    <a href="index.php" class="btn btn-light btn-sm border">Retour</a>
                </div>
                <div class="card-body p-4">
                    <?= $message // Contient du HTML contrôlé et sécurisé en amont ou des messages statiques ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="small fw-bold mb-1">Membre emprunteur</label>
                            <select name="compte_id" class="form-select select2" required>
                                <option value="">-- Sélectionner le membre --</option>
                                <?php foreach($membres as $m): ?>
                                    <option value="<?= (int)$m['id'] ?>">
                                        <?= htmlspecialchars($m['matricule']) .' - '. htmlspecialchars($m['nom'] . ' ' . $m['prenoms']) ?> (Épargne : <?= number_format($m['solde_tontine'], 0, ',', ' ') ?> F)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="small fw-bold mb-1">Montant à prêter (FCFA)</label>
                                <input type="number" name="montant" id="montant" min="500" step="50" class="form-control" placeholder="Ex: 100000" required>
                            </div>
                            <div class="col-md-6">
                                <label class="small fw-bold mb-1">Taux exigé (%)</label>
                                <input type="number" name="taux" id="taux" step="0.1" class="form-control" value="5.00" readonly required tabindex="-1">
                                <div class="form-text xsmall text-muted">Taux fixe de la mutuelle.</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="p-3 bg-light rounded border border-light-subtle text-muted small">
                                <i class="fa-solid fa-money-bill-wave text-success me-1"></i> Commission à percevoir sur place (5%) : 
                                <span class="badge bg-dark fs-6 ms-1"><span id="preview_commission">0</span> F CFA</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="small fw-bold mb-1">Date du prêt / Déblocage</label>
                            <input type="date" name="date_pret" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <button type="submit" class="btn btn-danger w-100 shadow-sm fw-bold py-2">
                            <i class="fa-solid fa-check-double me-1"></i> Valider et octroyer les fonds
                        </button>
                    </form>
                </div>
            </div>

            <div class="p-3 bg-light rounded border-start border-4 border-danger small shadow-sm mt-3">
                <i class="fa-solid fa-circle-info me-2 text-danger"></i>
                <strong>Fonctionnement :</strong> Aucun calendrier de paiement requis. Le prêt se remboursera automatiquement par un prélèvement de <strong>60%</strong> lors de chaque dépôt de tontine de cet adhérent.
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const montantInput = document.getElementById('montant');
    const tauxInput = document.getElementById('taux');
    const previewCommission = document.getElementById('preview_commission');

    function calculerCommission() {
        const montant = parseFloat(montantInput.value) || 0;
        const taux = parseFloat(tauxInput.value) || 0;
        const commission = (montant * taux) / 100;
        
        previewCommission.textContent = new Intl.NumberFormat('fr-FR').format(commission);
    }

    montantInput.addEventListener('input', calculerCommission);
    calculerCommission();
});
</script>

<?php require_once '../includes/footer.php'; ?>