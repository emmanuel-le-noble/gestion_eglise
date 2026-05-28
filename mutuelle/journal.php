<?php
// eglise_db/mutuelle/journal.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'mutuelle');

try {
    // Sélection des 50 dernières opérations de caisse avec jointures adaptées
    $sql = "SELECT o.*, m.nom, m.prenoms 
            FROM mutuelle_operations o 
            JOIN mutuelle_comptes mc ON o.compte_id = mc.id 
            JOIN membres m ON mc.membre_id = m.id 
            ORDER BY o.date_op DESC, o.id DESC LIMIT 50";
    $operations = $pdo->query($sql)->fetchAll();

    // Intégration du journal des logs à l'ouverture de la page d'audit
    if (function_exists('enregistrer_log')) {
        enregistrer_log(
            $pdo, 
            'Consultation Journal', 
            "Visualisation des 50 derniers mouvements de flux de la caisse mutuelle."
        );
    }
} catch (PDOException $e) {
    if (function_exists('enregistrer_log')) {
        enregistrer_log($pdo, 'Erreur Critique', "Échec de lecture du journal de caisse. Erreur : " . $e->getMessage());
    }
    echo "Erreur : " . $e->getMessage();
    exit;
}

$page_title = "Journal de caisse de la mutuelle"; 
require_once '../includes/header.php'; 
?>

<div class="container mt-4">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark m-0"><i class="fa-solid fa-book text-primary me-2"></i>Journal de la mutuelle</h4>
            <p class="text-muted small mb-0">Suivi en temps réel des flux, commissions de déblocage et frais de tenue.</p>
        </div>
        <div class="btn-group shadow-sm">
            <a href="registre_journal.php" class="btn btn-outline-dark btn-sm fw-semibold">
                <i class="fa-solid fa-print me-1"></i> Imprimer
            </a>
            <a href="cotisation.php" class="btn btn-primary btn-sm fw-semibold">
                <i class="fa-solid fa-plus me-1"></i> Nouveau versement
            </a>
            <a href="export_mutuelle.php" class="btn btn-success btn-sm fw-semibold">
                <i class="fa-solid fa-file-excel me-1"></i> Excel
            </a>
            <a href="index.php" class="btn btn-light btn-sm border fw-semibold">Retour</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase font-monospace text-secondary">
                    <tr>
                        <th class="ps-3" style="width: 15%;">Date</th>
                        <th style="width: 25%;">Membre Adhérent</th>
                        <th style="width: 20%;">Type d'Opération</th>
                        <th class="text-end" style="width: 15%;">Montant</th>
                        <th style="width: 25%;">Commentaire / Libellé</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($operations)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted small">Aucun flux enregistré dans le journal de caisse.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($operations as $op): ?>
                        <tr>
                            <td class="ps-3 small text-muted">
                                <i class="fa-regular fa-calendar me-1"></i><?= date('d/m/Y', strtotime($op['date_op'])) ?>
                            </td>
                            <td class="fw-semibold text-dark">
                                <?= htmlspecialchars($op['nom'] . ' ' . $op['prenoms']) ?>
                            </td>
                            <td>
                                <?php 
                                    // Association dynamique des couleurs de badges (Bootstrap)
                                    $badge_classes = [
                                        'DEPOT'           => 'bg-success-subtle text-success border border-success-subtle', 
                                        'RETRAIT'         => 'bg-danger-subtle text-danger border border-danger-subtle', 
                                        'PRET'            => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle', 
                                        'REMBOURSEMENT'   => 'bg-info-subtle text-info-emphasis border border-info-subtle', 
                                        'FRAIS_TENUE'     => 'bg-purple-subtle text-purple border border-purple-subtle', 
                                        'COMMISSION_PRET' => 'bg-primary-subtle text-primary border border-primary-subtle'
                                    ];

                                    $type = $op['type_operation'];
                                    $class = isset($badge_classes[$type]) ? $badge_classes[$type] : 'bg-light text-muted border';
                                    
                                    // Libellés clairs pour l'utilisateur
                                    $labels = [
                                        'DEPOT'           => 'ÉPARGNE TONTINE',
                                        'RETRAIT'         => 'RETRAIT ÉPARGNE',
                                        'PRET'            => 'OCTROI DE PRÊT',
                                        'REMBOURSEMENT'   => 'REMB. PRÊT (60%)',
                                        'FRAIS_TENUE'     => 'FRAIS DE TENUE',
                                        'COMMISSION_PRET' => 'COMMISSION PRÊT'
                                    ];
                                    $label = isset($labels[$type]) ? $labels[$type] : $type;
                                ?>
                                <span class="badge <?= $class ?> uppercase-track text-uppercase fw-bold shadow-xs" style="font-size: 0.7rem; letter-spacing: 0.3px;">
                                    <?= htmlspecialchars($label) ?>
                                </span>
                            </td>
                            <td class="text-end fw-bold <?php 
                                if (in_array($type, ['DEPOT', 'REMBOURSEMENT', 'FRAIS_TENUE', 'COMMISSION_PRET'])) {
                                    echo 'text-success';
                                } else {
                                    echo 'text-danger';
                                }
                             ?>">
                                <?= in_array($type, ['DEPOT', 'REMBOURSEMENT', 'FRAIS_TENUE', 'COMMISSION_PRET']) ? '+' : '-' ?>
                                <?= number_format($op['montant'], 0, ',', ' ') ?> F
                            </td>
                            <td class="small text-muted italic-comment">
                                <?= htmlspecialchars($op['commentaire'] ?? '') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.bg-purple-subtle { background-color: #f3e5f5 !important; }
.text-purple { color: #7b1fa2 !important; }
.border-purple-subtle { border-color: #e1bee7 !important; }
.shadow-xs { box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
.italic-comment { font-style: italic; font-size: 0.85rem; }
</style>

<?php require_once '../includes/footer.php'; ?>