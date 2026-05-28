<?php
// eglise_db/tresorerie/voir.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'tresorerie');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    $sql = "SELECT t.*, m.matricule, m.nom, m.prenoms, u.nom as admin_nom FROM tresorerie t LEFT JOIN membres m ON t.membre_id = m.id LEFT JOIN utilisateurs u ON t.utilisateur_id = u.id WHERE t.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $op = $stmt->fetch();

    if (!$op) {
        die("Opération introuvable.");
    }
    
    $page_title = "Détail opération N° " . $op['id'];
    require_once '../includes/header.php';

} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-dark fw-bold"><i class="fa-solid fa-circle-info me-2 text-primary"></i>Opération N° <?= $op['id'] ?></h5>
                    <div>
                        <a href="modifier.php?id=<?= $op['id'] ?>" class="btn btn-light btn-sm border text-warning"><i class="fa-solid fa-pen-to-square me-1"></i>Modifier</a>
                        <a href="index.php" class="btn btn-light btn-sm border ms-1">Retour</a>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block text-uppercase small fw-bold">Type de Mouvement</small>
                            <span class="badge <?= $op['type_mouvement'] === 'ENTREE' ? 'bg-success' : 'bg-danger' ?> fs-6 mt-1">
                                <?= $op['type_mouvement'] === 'ENTREE' ? 'ENTRÉE (Recette)' : 'SORTIE (Dépense)' ?>
                            </span>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block text-uppercase small fw-bold">Catégorie</small>
                            <h5 class="fw-bold text-dark mt-1"><?= htmlspecialchars($op['categorie']) ?></h5>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted d-block text-uppercase small fw-bold">Montant</small>
                            <h4 class="fw-bold <?= $op['type_mouvement'] === 'ENTREE' ? 'text-success' : 'text-danger' ?> mt-1">
                                <?= number_format($op['montant'], 0, ',', ' ') ?> FCFA
                            </h4>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block text-uppercase small fw-bold">Date de l'opération</small>
                            <h5 class="text-dark mt-1"><?= date('d/m/Y', strtotime($op['date_operation'])) ?></h5>
                        </div>

                        <?php if($op['membre_id']): ?>
                        <div class="col-12">
                            <div class="p-3 bg-light rounded border-start border-primary border-4">
                                <small class="text-muted d-block text-uppercase small fw-bold">Fidèle associé</small>
                                <span class="fw-bold text-primary"><?= $op['matricule'] ?> - <?= htmlspecialchars($op['nom'] . ' ' . $op['prenoms']) ?></span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <small class="text-muted d-block text-uppercase small fw-bold">Libellé / Description</small>
                            <p class="text-dark bg-light p-3 rounded mt-1"><?= nl2br(htmlspecialchars($op['libelle'] ?? 'Aucune description rédigée.')) ?></p>
                        </div>

                        <div class="col-12">
                            <small class="text-muted d-block text-uppercase small fw-bold mb-2">Pièce justificative</small>
                            <?php if($op['piece_justificative']): ?>
                                <?php 
                                $ext = strtolower(pathinfo($op['piece_justificative'], PATHINFO_EXTENSION));
                                $file_path = "../uploads/pieces/" . $op['piece_justificative'];
                                ?>
                                <?php if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                    <div class="mt-2 border rounded p-2 bg-light text-center">
                                        <img src="<?= $file_path ?>" class="img-fluid rounded" style="max-height: 350px;" alt="Justificatif">
                                        <div class="mt-2">
                                            <a href="<?= $file_path ?>" target="_blank" class="btn btn-sm btn-dark"><i class="fa-solid fa-eye me-1"></i>Ouvrir en plein écran</a>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-light border d-flex align-items-center justify-content-between p-3 mt-1">
                                        <div><i class="fa-solid fa-file-pdf text-danger fa-2x me-3"></i><span class="fw-bold">Document PDF Joint</span></div>
                                        <a href="<?= $file_path ?>" target="_blank" class="btn btn-sm btn-danger"><i class="fa-solid fa-download me-1"></i>Ouvrir le PDF</a>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="text-muted small mt-1">Aucune pièce jointe pour cette opération.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white text-muted small py-3 border-0">
                    Saisie enregistrée par : <strong><?= htmlspecialchars($op['admin_nom'] ?? 'Inconnu') ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>