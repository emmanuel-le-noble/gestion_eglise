<?php
// eglise_db/mutuelle/membres_mutuelle.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'mutuelle');

try {
    $sql = "SELECT mc.*, m.nom, m.prenoms, m.matricule 
            FROM mutuelle_comptes mc 
            JOIN membres m ON mc.membre_id = m.id 
            ORDER BY m.nom ASC";
    $adhérents = $pdo->query($sql)->fetchAll();

    // Journalisation de l'accès à la liste confidentielle des membres
    if (function_exists('enregistrer_log')) {
        enregistrer_log(
            $pdo, 
            'Consultation Liste Adhérents', 
            "Accès au répertoire des comptes d'adhérents de la mutuelle."
        );
    }
} catch (PDOException $e) {
    if (function_exists('enregistrer_log')) {
        enregistrer_log($pdo, 'Erreur Critique', "Échec de lecture de la liste des adhérents. Erreur : " . $e->getMessage());
    }
    echo "Erreur : " . $e->getMessage();
    exit;
}

$page_title = "Membres de la mutuelle"; 
require_once '../includes/header.php'; 
?>

<div class="container mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <h4 class="fw-bold text-dark m-0"><i class="fa-solid fa-users-rectangle me-2 text-primary"></i>Adhérents à la tontine</h4>
        <div class="d-flex flex-wrap gap-2"> 
            <a href="export_membres.php" class="btn btn-success btn-sm fw-semibold">
                <i class="fa-solid fa-file-excel me-1"></i> Exporter
            </a>
            <a href="rapport_membres_mutuelle.php" class="btn btn-outline-primary btn-sm fw-semibold">
                <i class="fa fa-print me-1"></i> Imprimer la liste
            </a>
            <a href="adhesion.php" class="btn btn-primary btn-sm fw-semibold">
                <i class="fa-solid fa-plus me-1"></i> Nouvelle adhésion
            </a>
            <a href="index.php" class="btn btn-light btn-sm border fw-semibold">Retour</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase font-monospace text-secondary">
                    <tr>
                        <th class="ps-3" style="width: 15%;">Matricule</th>
                        <th style="width: 25%;">Nom & prénoms</th>
                        <th style="width: 15%;">Date adhésion</th>
                        <th class="text-end" style="width: 12%;">Épargne tontine</th>
                        <th class="text-center" style="width: 10%;">Statut</th>
                        <th class="text-center" style="width: 21%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($adhérents)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted small">Aucun adhérent trouvé dans la mutuelle.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($adhérents as $a): ?>
                        <tr>
                            <td class="ps-3 fw-bold text-muted"><?= htmlspecialchars($a['matricule']) ?></td> <!-- Correction XSS -->
                            <td class="fw-semibold text-dark"><?= htmlspecialchars($a['nom'] . ' ' . $a['prenoms']) ?></td>
                            <td class="small text-muted"><?= date('d/m/Y', strtotime($a['date_adhesion'])) ?></td>
                            <td class="text-end fw-bold text-success"><?= number_format($a['solde_tontine'], 0, ',', ' ') ?> F</td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-<?= $a['statut'] == 'ACTIF' ? 'success' : 'danger' ?>-subtle text-<?= $a['statut'] == 'ACTIF' ? 'success' : 'danger' ?> fw-bold" style="font-size: 0.75rem;">
                                    <?= htmlspecialchars($a['statut']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="profil_compte.php?id=<?= $a['id'] ?>" class="btn btn-xs btn-outline-primary p-2 border" title="Voir le compte">
                                        <i class="fa-solid fa-circle-user"></i> compte
                                    </a>
                                    <a href="fiche_mensuelle.php?compte_id=<?= $a['id'] ?>" class="btn btn-xs btn-light p-2 border" title="Fiche mensuelle">
                                        <i class="fa-solid fa-file-lines"></i> fiche
                                    </a>
                                    <a href="supprimer_membre.php?id=<?= $a['id'] ?>" 
                                       onclick="return confirm('Attention ! Êtes-vous sûr de vouloir supprimer définitivement cet adhérent et son compte de mutuelle ? Cette action est irréversible.');" 
                                       class="btn btn-xs btn-outline-danger p-2 border" 
                                       title="Supprimer l'adhérent">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </a>
                                </div>
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
.btn-xs {
    padding: 0.25rem 0.4rem;
    font-size: 0.82rem;
    line-height: 1.2;
}
</style>

<?php require_once '../includes/footer.php'; ?>