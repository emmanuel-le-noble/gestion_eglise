<?php
// eglise_db/mutuelle/profil_compte.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'mutuelle');

$compte_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    // Récupération des informations du compte mutuelle ET du profil du membre
    $sql = "SELECT mc.*, m.matricule, m.nom, m.prenoms, m.telephone1, m.groupe_action, m.statut_membre 
            FROM mutuelle_comptes mc 
            JOIN membres m ON mc.membre_id = m.id 
            WHERE mc.id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $compte_id]);
    $compte = $stmt->fetch();

    if (!$compte) {
        $page_title = "Compte introuvable";
        require_once '../includes/header.php';
        echo "<div class='container mt-4'><div class='alert alert-danger'><i class='fa-solid fa-triangle-exclamation me-2'></i>Compte mutuelle introuvable.</div></div>";
        require_once '../includes/footer.php';
        exit;
    }

    // Journalisation de la consultation de la fiche financière de l'adhérent
    if (function_exists('enregistrer_log')) {
        $nom_complet = trim($compte['nom'] . ' ' . $compte['prenoms']);
        enregistrer_log(
            $pdo, 
            'Consultation Profil Mutuelle', 
            "Fiche financière consultée pour l'adhérent : $nom_complet (ID Compte: $compte_id)."
        );
    }

    // Récupération de l'historique complet des engagements et prêts
    $prets = $pdo->prepare("SELECT *, (montant_prete) as total_du FROM mutuelle_prets WHERE compte_id = :id ORDER BY date_pret DESC");
    $prets->execute(['id' => $compte_id]);
    $liste_prets = $prets->fetchAll();

} catch (PDOException $e) {
    if (function_exists('enregistrer_log')) {
        enregistrer_log($pdo, 'Erreur Critique', "Échec lors de la consultation du compte mutuelle ID $compte_id. Erreur : " . $e->getMessage());
    }
    die("Erreur de base de données : Échec de la récupération des données.");
}

$page_title = "Fiche compte adhérent"; 
require_once '../includes/header.php'; 
?>

<div class="container mt-4">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Compte Mutuelle N° <?= htmlspecialchars($compte['matricule_mutuelle'] ?? $compte['id']) ?></h3>
            <p class="text-muted small mb-0">Détails financiers et suivi automatique des remboursements tontine.</p>
        </div>
        <div class="btn-group shadow-sm">
            <a href="fiche_mensuelle.php?compte_id=<?= $compte_id ?>" class="btn btn-outline-primary btn-sm fw-semibold">
                <i class="fa-solid fa-file-invoice me-1"></i> Fiche mensuelle
            </a>
            <a href="membres_mutuelle.php" class="btn btn-light btn-sm border fw-semibold">
                <i class="fa-solid fa-arrow-left me-1"></i> Liste des membres
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Volet Profil Membre -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center pt-4">
                    <div class="rounded-circle bg-danger bg-opacity-10 text-danger mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fa-solid fa-user-tie fa-2x"></i>
                    </div>
                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($compte['nom'] . ' ' . $compte['prenoms']) ?></h5>
                    <span class="badge bg-light text-dark border mb-3"><?= htmlspecialchars($compte['groupe_action'] ?? 'Aucun groupe') ?></span>
                    
                    <hr class="opacity-25">
                    
                    <div class="text-start small px-2">
                        <div class="mb-2 d-flex justify-content-between">
                            <span class="text-muted">Matricule Église :</span>
                            <span class="fw-bold text-secondary"><?= htmlspecialchars($compte['matricule']) ?></span>
                        </div>
                        <div class="mb-2 d-flex justify-content-between">
                            <span class="text-muted">Téléphone :</span>
                            <span class="fw-bold"><?= htmlspecialchars($compte['telephone1'] ?? 'Non renseigné') ?></span>
                        </div>
                        <div class="mb-2 d-flex justify-content-between">
                            <span class="text-muted">Statut Église :</span>
                            <span class="badge bg-success-subtle text-success fw-bold"><?= htmlspecialchars($compte['statut_membre'] ?? 'Fidèle') ?></span>
                        </div>
                        <div class="mb-2 d-flex justify-content-between">
                            <span class="text-muted">Date Adhésion :</span>
                            <span class="fw-bold"><?= date('d/m/Y', strtotime($compte['date_adhesion'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Volet Financier & Historique -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm bg-success text-white mb-4">
                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-white-50 text-uppercase fw-bold small"><i class="fa-solid fa-piggy-bank me-2"></i>Solde Global Épargne / Tontine</small>
                        <h2 class="fw-bold m-0 mt-1"><?= number_format($compte['solde_tontine'], 0, ',', ' ') ?> F CFA</h2>
                    </div>
                    <div class="opacity-50 display-6 d-none d-sm-block">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-clock-rotate-left me-2 text-muted"></i>Historique des engagements / prêts</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-uppercase font-monospace text-secondary">
                                <tr>
                                    <th class="ps-3">Date Prêt</th>
                                    <th>Total Dû (+ Int.)</th>
                                    <th>Remboursé</th>
                                    <th>Reste dû</th>
                                    <th class="text-center">Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($liste_prets)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted small">Aucun emprunt enregistré pour cet adhérent.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($liste_prets as $p): 
                                        // Ajustement de la formule comptable pour intégrer les intérêts (commission)
                                        $reste_a_payer = $p['total_du'] - $p['montant_rembourse'];
                                        
                                        // Attribution dynamique et sécurisée des badges de statuts
                                        $badge = 'bg-success';
                                        $label_statut = 'Soldé';
                                        
                                        if ($p['statut'] === 'RETARD') {
                                            $badge = 'bg-danger';
                                            $label_statut = 'En retard';
                                        } elseif ($p['statut'] === 'EN_COURS') {
                                            $badge = 'bg-warning text-dark';
                                            $label_statut = 'En cours';
                                        }
                                    ?>
                                        <tr>
                                            <td class="ps-3 small text-muted"><?= date('d/m/Y', strtotime($p['date_pret'])) ?></td>
                                            <td class="fw-bold small">
                                                <?= number_format($p['total_du'], 0, ',', ' ') ?> F
                                                <div class="text-muted font-monospace text-xs fw-normal" style="font-size: 0.7rem;">Cap: <?= number_format($p['montant_prete'], 0, ',', ' ') ?> F</div>
                                            </td>
                                            <td class="text-success small fw-semibold"><?= number_format($p['montant_rembourse'], 0, ',', ' ') ?> F</td>
                                            <td class="small fw-bold <?= $reste_a_payer > 0 ? 'text-danger' : 'text-muted' ?>">
                                                <?= number_format(max(0, $reste_a_payer), 0, ',', ' ') ?> F
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?= $badge ?> fw-bold shadow-xs px-2 py-1" style="font-size: 0.7rem;">
                                                    <?= $label_statut ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.shadow-xs { box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
</style>

<?php require_once '../includes/footer.php'; ?>