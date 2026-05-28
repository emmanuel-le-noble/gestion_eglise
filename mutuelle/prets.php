<?php
// eglise_db/mutuelle/prets.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'mutuelle');

try {
    // ÉTAPE 1 : Automatisation — On passe en 'RETARD' les prêts non soldés dont l'échéance est dépassée
    // Note : On compare avec la formule exacte incluant les intérêts (colonne commission) générés par MySQL
    $pdo->query("UPDATE mutuelle_prets SET statut = 'RETARD' WHERE statut = 'EN_COURS' AND date_echeance < CURRENT_DATE AND montant_rembourse < (montant_prete + commission)");

    // ÉTAPE 2 : Récupération des statistiques des prêts (Incluant les intérêts / commissions)
    $stats = $pdo->query("SELECT 
        COUNT(id) as total_prets, 
        SUM(CASE WHEN statut = 'RETARD' THEN 1 ELSE 0 END) as nb_retards, 
        SUM(montant_prete) as capital_prete, 
        SUM(commission) as total_commissions,
        SUM(montant_rembourse) as capital_rembourse, 
        SUM((montant_prete + commission) - montant_rembourse) as reste_a_recouvrer 
        FROM mutuelle_prets WHERE statut != 'SOLDE'")->fetch();

    // ÉTAPE 3 : Liste complète des prêts avec les informations du membre et les calculs associés
    $sql = "SELECT p.*, m.nom, m.prenoms, 
            (p.montant_prete + p.commission) as total_du,
            ((p.montant_prete + p.commission) - p.montant_rembourse) as reste_a_payer 
            FROM mutuelle_prets p 
            JOIN mutuelle_comptes mc ON p.compte_id = mc.id 
            JOIN membres m ON mc.membre_id = m.id 
            ORDER BY CASE WHEN p.statut = 'RETARD' THEN 1 WHEN p.statut = 'EN_COURS' THEN 2 ELSE 3 END, p.date_echeance ASC";
    $prets = $pdo->query($sql)->fetchAll();

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}

$page_title = "Suivi des prêts & alertes"; 
require_once '../includes/header.php'; 
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Gestion & suivi des prêts</h3>
            <p class="text-muted small mb-0">Contrôle des échéances, taux appliqués et suivi automatique du recouvrement.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="export_prets.php" class="btn btn-success btn-sm d-flex align-items-center">
                <i class="fa-solid fa-file-excel me-1"></i> Exporter
            </a>
            <a href="registre_prets.php" class="btn btn-outline-primary btn-sm d-flex align-items-center">
                <i class="fa fa-print me-1"></i> Imprimer
            </a>
            <a href="nouveau_pret.php" class="btn btn-primary btn-sm shadow-sm d-flex align-items-center">
                <i class="fa-solid fa-plus me-1"></i> Octroyer un prêt
            </a>
            <a href="index.php" class="btn btn-light btn-sm border d-flex align-items-center">Retour</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-white border-start border-danger border-4">
                <div class="card-body">
                    <small class="text-muted text-uppercase fw-bold small">Prêts en retard</small>
                    <h3 class="fw-bold m-0 text-danger d-flex align-items-center justify-content-between">
                        <?= (int)$stats['nb_retards'] ?>
                        <?php if($stats['nb_retards'] > 0): ?>
                            <i class="fa-solid fa-triangle-exclamation fa-xs animate-bounce text-danger"></i>
                        <?php endif; ?>
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-white border-start border-primary border-4">
                <div class="card-body">
                    <small class="text-muted text-uppercase fw-bold small">Capital prêté (Actif)</small>
                    <h4 class="fw-bold m-0 text-dark"><?= number_format($stats['capital_prete'] ?? 0, 0, ',', ' ') ?> F</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-white border-start border-success border-4">
                <div class="card-body">
                    <small class="text-muted text-uppercase fw-bold small">Déjà remboursé</small>
                    <h4 class="fw-bold m-0 text-success"><?= number_format($stats['capital_rembourse'] ?? 0, 0, ',', ' ') ?> F</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-dark text-white">
                <div class="card-body">
                    <small class="text-white-50 text-uppercase fw-bold small">Reste dehors (+ Intérêts)</small>
                    <h4 class="fw-bold m-0 text-warning"><?= number_format($stats['reste_a_recouvrer'] ?? 0, 0, ',', ' ') ?> F</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-list text-muted me-2"></i>Registre des prêts octroyés</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4">Membre</th>
                            <th>Période du Prêt</th>
                            <th>Taux & Intérêts</th>
                            <th>Total Dû</th>
                            <th>Remboursé</th>
                            <th>Reste à payer</th>
                            <th class="pe-4 text-end">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($prets)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted small">Aucun prêt enregistré pour le moment.</td></tr>
                        <?php else: ?>
                            <?php foreach ($prets as $p): 
                                $row_class = '';
                                $badge_class = 'bg-success';
                                
                                if ($p['statut'] == 'RETARD') {
                                    $row_class = 'table-danger-custom';
                                    $badge_class = 'bg-danger';
                                } elseif ($p['statut'] == 'EN_COURS') {
                                    $badge_class = 'bg-warning text-dark';
                                } elseif ($p['statut'] == 'SOLDE') {
                                    $badge_class = 'bg-success';
                                }
                                
                                // Calcul du pourcentage basé sur le total_du (Principal + Intérêts)
                                $pct = ($p['total_du'] > 0) ? ($p['montant_rembourse'] / $p['total_du']) * 100 : 0;
                            ?>
                                <tr class="<?= $row_class ?>">
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($p['nom'] . ' ' . $p['prenoms']) ?></div>
                                    </td>
                                    <td class="small">
                                        <div class="text-dark">Du <?= date('d/m/Y', strtotime($p['date_pret'])) ?></div>
                                        <div class="text-muted text-xs">Au <?= date('d/m/Y', strtotime($p['date_echeance'])) ?></div>
                                    </td>
                                    <td class="small">
                                        <span class="badge bg-light text-dark border"><?= number_format($p['taux'], 2, ',', ' ') ?> %</span>
                                        <div class="text-muted text-xs mt-1">+ <?= number_format($p['commission'], 0, ',', ' ') ?> F (Intérêts)</div>
                                    </td>
                                    <td class="fw-semibold small">
                                        <?= number_format($p['total_du'], 0, ',', ' ') ?> F
                                        <div class="text-muted text-xs fw-normal">Cap: <?= number_format($p['montant_prete'], 0, ',', ' ') ?> F</div>
                                    </td>
                                    <td class="small">
                                        <span class="text-success fw-semibold"><?= number_format($p['montant_rembourse'], 0, ',', ' ') ?> F</span>
                                        <div class="progress mt-1" style="height: 4px; max-width: 100px;">
                                            <div class="progress-bar bg-success" style="width: <?= $pct ?>%"></div>
                                        </div>
                                    </td>
                                    <td class="fw-bold text-primary small"><?= number_format($p['reste_a_payer'], 0, ',', ' ') ?> F</td>
                                    <td class="pe-4 text-end">
                                        <span class="badge <?= $badge_class ?> small px-2 py-1">
                                            <?= $p['statut'] == 'SOLDE' ? 'Soldé' : ($p['statut'] == 'RETARD' ? 'En retard' : 'En cours') ?>
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

<style>
    .table-danger-custom {
        background-color: rgba(220, 53, 69, 0.04) !important;
    }
    .table-danger-custom:hover {
        background-color: rgba(220, 53, 69, 0.08) !important;
    }
    .text-xs {
        font-size: 0.75rem;
    }
    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-4px); }
    }
    .animate-bounce { animation: bounce 1s infinite; }
</style>

<?php require_once '../includes/footer.php'; ?>