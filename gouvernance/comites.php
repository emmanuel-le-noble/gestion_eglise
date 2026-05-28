<?php
// eglise_db/gouvernance/comites.php
require_once "../config/database.php";
require_once "../includes/session.php";

// Toutes les pages du dossier gouvernance contiendront cette ligne :
securiser_par_module($pdo, 'gouvernance');

$message = "";
$erreur = "";

// ==========================================
// 1. TRAITEMENT DU FORMULAIRE (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_comite'])) {
    $nom = trim($_POST['nom']);
    $description = trim($_POST['description']);
    $responsable_id = !empty($_POST['responsable_id']) ? (int)$_POST['responsable_id'] : null;
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $ordre = (int)$_POST['ordre_affichage'];

    if (!empty($nom)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO comites (nom, description, responsable_id, parent_id, ordre_affichage) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $description, $responsable_id, $parent_id, $ordre]);
            $message = "Le comité / département a été créé avec succès !";
        } catch (PDOException $e) {
            $erreur = "Erreur lors de la création du comité : " . $e->getMessage();
        }
    } else {
        $erreur = "Le nom du comité est obligatoire.";
    }
}

// ==========================================
// 2. RÉCUPÉRATION DES DONNÉES
// ==========================================

// Liste de tous les membres pour le sélecteur de responsables
$membres = $pdo->query("SELECT id, matricule, nom, prenoms FROM membres WHERE statut_membre = 'Actif' ORDER BY nom ASC, prenoms ASC")->fetchAll();

// Liste de tous les comités pour le sélecteur de parents
$tous_comites = $pdo->query("SELECT id, nom FROM comites ORDER BY nom ASC")->fetchAll();

// Liste complète avec jointure pour l'affichage des comités
$sql = "SELECT c1.*, 
               CONCAT(m.nom, ' ', m.prenoms) AS nom_responsable, 
               c2.nom AS nom_parent
        FROM comites c1
        LEFT JOIN membres m ON c1.responsable_id = m.id
        LEFT JOIN comites c2 ON c1.parent_id = c2.id
        ORDER BY c1.parent_id ASC, c1.ordre_affichage ASC, c1.nom ASC";
$comites_affichage = $pdo->query($sql)->fetchAll();

$page_title = "Gestion des comités & départements";
require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0"><i class="fa-solid fa-people-group text-primary me-2"></i>Comités & Départements</h3>
            <p class="text-muted small mb-0">Gestion des ministères, équipes internes de l'église et affectation de leurs responsables.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-primary btn-sm fw-bold">
                <i class="fa-solid fa-arrow-left me-1"></i> Tableau de bord
            </a>
            <a href="../tresorerie/budget.php" class="btn btn-light btn-sm border fw-bold">
                <i class="fa-solid fa-arrow-right me-1"></i> Aller aux budgets
            </a>
        </div>
    </div>

    <?php if($message): ?> <div class="alert alert-success small mb-3"><i class="fa-solid fa-circle-check me-2"></i><?= $message ?></div> <?php endif; ?>
    <?php if($erreur): ?> <div class="alert alert-danger small mb-3"><i class="fa-solid fa-circle-exclamation me-2"></i><?= $erreur ?></div> <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0"><i class="fa-solid fa-plus text-success me-2"></i>Nouveau département / comité</h6>
                </div>
                <div class="card-body pt-0">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="small fw-bold text-muted">Nom du comité *</label>
                            <input type="text" name="nom" class="form-control" placeholder="Ex: Département de la Jeunesse, Comité des Diacres" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="small fw-bold text-muted">Rattaché à (groupe parent)</label>
                            <select name="parent_id" class="form-select">
                                <option value="">-- Aucun (Groupe principal) --</option>
                                <?php foreach($tous_comites as $tc): ?>
                                    <option value="<?= $tc['id'] ?>"><?= htmlspecialchars($tc['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text small" style="font-size:0.75rem;">Permet d'associer un sous-comité à une entité parente.</div>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">Membre responsable / Leader</label>
                            <select name="responsable_id" class="form-select">
                                <option value="">-- Non défini / À pourvoir --</option>
                                <?php foreach($membres as $m): ?>
                                    <option value="<?= $m['id'] ?>">
                                        <?= htmlspecialchars($m['nom'] . ' ' . $m['prenoms']) ?> (<?= htmlspecialchars($m['matricule']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">Ordre d'affichage (priorité)</label>
                            <input type="number" name="ordre_affichage" class="form-control" value="0">
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">Missions / Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Description brève des activités et responsabilités de ce groupe..."></textarea>
                        </div>

                        <button type="submit" name="ajouter_comite" class="btn btn-primary w-100 fw-bold">Créer le comité</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0"><i class="fa-solid fa-list-check text-primary me-2"></i>Registre des départements & Comités</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($comites_affichage)): ?>
                        <p class="text-center py-5 text-muted small m-0">Aucun comité créé pour le moment. Utilisez le formulaire de gauche.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th class="ps-3">Nom du comité</th>
                                        <th>Rattaché à</th>
                                        <th>Responsable officiel</th>
                                        <th class="text-center">Ordre</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($comites_affichage as $comite): ?>
                                        <tr>
                                            <td class="ps-3">
                                                <?php if ($comite['parent_id']): ?>
                                                    <span class="text-muted me-2">—</span>
                                                    <span class="fw-normal text-secondary"><?= htmlspecialchars($comite['nom']) ?></span>
                                                <?php else: ?>
                                                    <span class="fw-bold text-dark"><i class="fa-solid fa-users text-muted small me-1"></i> <?= htmlspecialchars($comite['nom']) ?></span>
                                                <?php endif; ?>
                                                
                                                <?php if(!empty($comite['description'])): ?>
                                                    <div class="text-muted small truncated-text" style="font-size: 0.8rem;" title="<?= htmlspecialchars($comite['description']) ?>">
                                                        <?= htmlspecialchars(substr($comite['description'], 0, 60)) ?><?= strlen($comite['description']) > 60 ? '...' : '' ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($comite['nom_parent']): ?>
                                                    <span class="badge bg-light text-secondary border"><?= htmlspecialchars($comite['nom_parent']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted small"><em>Groupe racine</em></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($comite['nom_responsable']): ?>
                                                    <div class="fw-semibold text-dark"><i class="fa-solid fa-user-tie text-muted me-1 small"></i> <?= htmlspecialchars($comite['nom_responsable']) ?></div>
                                                <?php else: ?>
                                                    <span class="text-danger small fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i> Vacant</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center text-muted small">
                                                <?= $comite['ordre_affichage'] ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>