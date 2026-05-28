<?php
// eglise_db/cultes/index.php
require_once "../config/database.php";
require_once '../includes/helpers.php';

$page_title = "Gestion des cultes"; 
require_once '../includes/header.php'; 

// Récupération de l'historique des cultes (du plus récent au plus ancien)
try {
    $query = "SELECT * FROM cultes ORDER BY date_culte DESC, id DESC";
    $cultes = $pdo->query($query)->fetchAll();
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0 text-dark">Rapports de cultes</h4>
            <p class="text-muted small mb-0">Suivi de la fréquentation et des messages</p>
        </div>
        <div class="d-flex gap-2">
            <a href="statistiques.php" class="btn btn-outline-primary shadow-sm">
                <i class="fa-solid fa-chart-line me-1"></i> Analyser la croissance
            </a>
            <a href="ajouter.php" class="btn btn-primary shadow-sm">
                <i class="fa-solid fa-plus me-1"></i> Nouveau rapport
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 ps-3">Date</th>
                            <th class="border-0">Type de culte</th>
                            <th class="border-0">Thème / Prédicateur</th>
                            <th class="border-0 text-center">Présences</th>
                            <th class="border-0 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($cultes as $c): ?>
                        <tr>
                            <td class="ps-3 fw-bold">
                                <?= date('d/m/Y', strtotime($c['date_culte'])) ?>
                            </td>
                            <td>
                                <?php 
                                    // Couleur du badge selon le type de culte
                                    $badge_class = "bg-secondary";
                                    if($c['type_culte'] == 'Culte de Dimanche') $badge_class = "bg-primary-subtle text-primary";
                                    if($c['type_culte'] == 'Étude Biblique') $badge_class = "bg-info-subtle text-info";
                                    if($c['type_culte'] == 'Veillée') $badge_class = "bg-dark text-white";
                                ?>
                                <span class="badge <?= $badge_class ?> rounded-pill px-3">
                                    <?= htmlspecialchars($c['type_culte']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="text-dark fw-semibold" style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= !empty($c['theme_message']) ? '"'.htmlspecialchars($c['theme_message']).'"' : '<i class="text-muted small">Aucun thème</i>' ?>
                                </div>
                                <small class="text-muted">Prédicateur : <?= htmlspecialchars($c['predicateur'] ?? 'Non précisé') ?></small>
                            </td>
                            <td class="text-center">
                                <div class="fw-bold fs-5 text-primary"><?= $c['total_presences'] ?></div>
                                <div style="font-size: 0.75rem;" class="text-muted">
                                    H:<?= $c['nombre_hommes'] ?> | F:<?= $c['nombre_femmes'] ?> | E:<?= $c['nombre_enfants'] ?>
                                    <?php if($c['nombre_visiteurs'] > 0): ?>
                                        <br><span class="text-warning fw-bold">+ <?= $c['nombre_visiteurs'] ?> visiteur(s)</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="modifier.php?id=<?= $c['id'] ?>" class="btn btn-light btn-sm border" title="Modifier">
                                        <i class="fa-solid fa-pen-to-square text-secondary"></i>
                                    </a>
                                    <a href="voir.php?id=<?= $c['id'] ?>" class="btn btn-light btn-sm border" title="Détails">
                                        <i class="fa-solid fa-eye text-primary"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <?php if(empty($cultes)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <img src="../assets/img/empty-data.png" alt="Vide" style="width: 80px; opacity: 0.3;">
                                <p class="text-muted mt-2">Aucun rapport de culte enregistré pour le moment.</p>
                                <a href="ajouter.php" class="btn btn-primary btn-sm">Enregistrer le premier culte</a>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
/* Style optionnel pour améliorer l'apparence des sous-titres de présence */
.table-responsive {
    min-height: 400px;
}
.bg-primary-subtle { background-color: #e7f1ff; }
.bg-info-subtle { background-color: #cff4fc; }
</style>

<?php require_once '../includes/footer.php'; ?>