<?php
// eglise_db/gouvernance/lois.php
require_once "../config/database.php";
require_once "../includes/session.php";

// Toutes les pages du dossier gouvernance contiendront cette ligne :
securiser_par_module($pdo, 'gouvernance');

$message = "";
$erreur = "";

// Catégorie filtrée par défaut (toutes si vide)
$categorie_filtre = $_GET['categorie'] ?? '';
$categories_disponibles = ['Constitution', 'Statuts', 'Règlement Intérieur', 'Résolution', 'Charte'];

// Dossier de destination des fichiers (assurez-vous de le créer sur votre serveur avec les bons droits d'écriture)
$dossier_upload = "../uploads/documents_legaux/";

// ==========================================
// 1. TRAITEMENT DES FORMULAIRES (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $utilisateur_id = $_SESSION['user_id'] ?? null;

    // --- AJOUT D'UN TEXTE ---
    if (isset($_POST['ajouter_loi'])) {
        $titre = trim($_POST['titre']);
        $contenu = trim($_POST['contenu']);
        $categorie = $_POST['categorie'];
        $version = trim($_POST['version']) ?: '1.0';
        $date_adoption = !empty($_POST['date_adoption']) ? $_POST['date_adoption'] : null;
        $fichier_chemin = null;

        if (!empty($titre) && !empty($contenu)) {
            // Gestion de l'upload de fichier
            if (isset($_FILES['document_joint']) && $_FILES['document_joint']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['document_joint']['tmp_name'];
                $file_name = $_FILES['document_joint']['name'];
                $file_size = $_FILES['document_joint']['size'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                $extensions_autorisees = ['pdf', 'doc', 'docx'];

                if (in_array($file_ext, $extensions_autorisees)) {
                    if ($file_size <= 5 * 1024 * 1024) { // Limite à 5 Mo
                        if (!is_dir($dossier_upload)) {
                            mkdir($dossier_upload, 0755, true);
                        }
                        $nouveau_nom = uniqid('doc_', true) . '.' . $file_ext;
                        $fichier_chemin = $dossier_upload . $nouveau_nom;
                        
                        if (!move_uploaded_file($file_tmp, $fichier_chemin)) {
                            $erreur = "Erreur lors du déplacement du fichier.";
                            $fichier_chemin = null;
                        }
                    } else {
                        $erreur = "Le fichier est trop volumineux (maximum 5 Mo).";
                    }
                } else {
                    $erreur = "Seuls les fichiers PDF, DOC et DOCX sont autorisés.";
                }
            }

            if (empty($erreur)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO lois_eglise (titre, contenu, categorie, version, date_adoption, utilisateur_id, fichier_joint) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$titre, $contenu, $categorie, $version, $date_adoption, $utilisateur_id, $fichier_chemin]);
                    $message = "Le texte de loi / article a été enregistré avec succès !";
                } catch (PDOException $e) {
                    $erreur = "Erreur lors de l'enregistrement : " . $e->getMessage();
                }
            }
        } else {
            $erreur = "Le titre et le contenu du texte sont obligatoires.";
        }
    }

    // --- MODIFICATION D'UN TEXTE ---
    if (isset($_POST['modifier_loi'])) {
        $id_loi = (int)$_POST['id_loi'];
        $titre = trim($_POST['titre']);
        $contenu = trim($_POST['contenu']);
        $categorie = $_POST['categorie'];
        $version = trim($_POST['version']) ?: '1.0';
        $date_adoption = !empty($_POST['date_adoption']) ? $_POST['date_adoption'] : null;

        if (!empty($titre) && !empty($contenu)) {
            try {
                // Récupération de l'ancien fichier si besoin
                $stmt_file = $pdo->prepare("SELECT fichier_joint FROM lois_eglise WHERE id = ?");
                $stmt_file->execute([$id_loi]);
                $ancien_fichier = $stmt_file->fetchColumn();
                $fichier_chemin = $ancien_fichier;

                // Vérification si un nouveau fichier est téléversé
                if (isset($_FILES['document_joint']) && $_FILES['document_joint']['error'] === UPLOAD_ERR_OK) {
                    $file_tmp = $_FILES['document_joint']['tmp_name'];
                    $file_name = $_FILES['document_joint']['name'];
                    $file_size = $_FILES['document_joint']['size'];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $extensions_autorisees = ['pdf', 'doc', 'docx'];

                    if (in_array($file_ext, $extensions_autorisees) && $file_size <= 5 * 1024 * 1024) {
                        $nouveau_nom = uniqid('doc_', true) . '.' . $file_ext;
                        $nouveau_chemin = $dossier_upload . $nouveau_nom;

                        if (move_uploaded_file($file_tmp, $nouveau_chemin)) {
                            // Supprimer l'ancien fichier physique s'il existe
                            if ($ancien_fichier && file_exists($ancien_fichier)) {
                                unlink($ancien_fichier);
                            }
                            $fichier_chemin = $nouveau_chemin;
                        }
                    }
                }

                $stmt = $pdo->prepare("UPDATE lois_eglise SET titre = ?, contenu = ?, categorie = ?, version = ?, date_adoption = ?, fichier_joint = ? WHERE id = ?");
                $stmt->execute([$titre, $contenu, $categorie, $version, $date_adoption, $fichier_chemin, $id_loi]);
                $message = "Le texte législatif a été mis à jour avec succès !";
            } catch (PDOException $e) {
                $erreur = "Erreur lors de la modification : " . $e->getMessage();
            }
        } else {
            $erreur = "Le titre et le contenu ne peuvent pas être vides.";
        }
    }

    // --- SUPPRESSION D'UN TEXTE ---
    if (isset($_POST['supprimer_loi'])) {
        $supprimer_id = (int)$_POST['id_loi'];
        try {
            // Supprimer d'abord le fichier lié
            $stmt_file = $pdo->prepare("SELECT fichier_joint FROM lois_eglise WHERE id = ?");
            $stmt_file->execute([$supprimer_id]);
            $fichier = $stmt_file->fetchColumn();
            if ($fichier && file_exists($fichier)) {
                unlink($fichier);
            }

            $stmt = $pdo->prepare("DELETE FROM lois_eglise WHERE id = ?");
            $stmt->execute([$supprimer_id]);
            $message = "Texte législatif supprimé du registre permanent.";
        } catch (PDOException $e) {
            $erreur = "Impossible de supprimer ce texte.";
        }
    }
}

// ==========================================
// 2. RÉCUPÉRATION ET FILTRE DES TEXTES
// ==========================================
if (in_array($categorie_filtre, $categories_disponibles)) {
    $sql_lois = "SELECT l.*, u.nom AS nom_admin 
                 FROM lois_eglise l 
                 LEFT JOIN utilisateurs u ON l.utilisateur_id = u.id 
                 WHERE l.categorie = ? 
                 ORDER BY l.id ASC";
    $stmt = $pdo->prepare($sql_lois);
    $stmt->execute([$categorie_filtre]);
    $lois = $stmt->fetchAll();
} else {
    $sql_lois = "SELECT l.*, u.nom AS nom_admin 
                 FROM lois_eglise l 
                 LEFT JOIN utilisateurs u ON l.utilisateur_id = u.id 
                 ORDER BY l.categorie ASC, l.id ASC";
    $lois = $pdo->query($sql_lois)->fetchAll();
}

$page_title = "Lois, statuts & règlements";
require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark m-0"><i class="fa-solid fa-gavel text-secondary me-2"></i>Textes législatifs & règlements</h3>
            <p class="text-muted small mb-0">Registre officiel de la constitution, des règles internes et chartes de l'Église.</p>
        </div>
        
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="index.php" class="btn btn-outline-dark btn-sm fw-bold px-3 py-2 shadow-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Tableau de bord
            </a>

            <div class="bg-white p-2 rounded border shadow-sm d-flex align-items-center gap-2">
                <span class="small fw-bold text-muted ps-1"><i class="fa-solid fa-filter me-1"></i> Filtrer :</span>
                <form method="GET" class="m-0">
                    <select name="categorie" class="form-select form-select-sm fw-bold border-0 bg-light" onchange="this.form.submit()">
                        <option value="">Tout afficher</option>
                        <?php foreach($categories_disponibles as $cat): ?>
                            <option value="<?= $cat ?>" <?= $categorie_filtre === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>
    </div>

    <?php if($message): ?> <div class="alert alert-success small mb-3"><i class="fa-solid fa-circle-check me-2"></i><?= $message ?></div> <?php endif; ?>
    <?php if($erreur): ?> <div class="alert alert-danger small mb-3"><i class="fa-solid fa-circle-exclamation me-2"></i><?= $erreur ?></div> <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0"><i class="fa-solid fa-pen-to-square text-success me-2"></i>Consigner un article / acte</h6>
                </div>
                <div class="card-body pt-0">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="small fw-bold text-muted">Catégorie du document *</label>
                            <select name="categorie" class="form-select" required>
                                <?php foreach($categories_disponibles as $cat): ?>
                                    <option value="<?= $cat ?>"><?= $cat ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">Titre de l'article / de la section *</label>
                            <input type="text" name="titre" class="form-control" placeholder="Ex: Article 4 : Des devoirs des membres" required>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="small fw-bold text-muted">Version</label>
                                <input type="text" name="version" class="form-control" value="1.0" placeholder="Ex: 1.2">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="small fw-bold text-muted">Date d'adoption</label>
                                <input type="date" name="date_adoption" class="form-control" value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">Document officiel joint (PDF, Word)</label>
                            <input type="file" name="document_joint" class="form-control form-control-sm" accept=".pdf,.doc,.docx">
                            <div class="form-text text-muted style-size" style="font-size: 0.7rem;">Formats autorisés : PDF, DOC, DOCX. Max 5 Mo.</div>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">Contenu du texte légal *</label>
                            <textarea name="contenu" class="form-control fw-normal" rows="6" placeholder="Écrivez ici le corps complet du texte..." required></textarea>
                        </div>

                        <button type="submit" name="ajouter_loi" class="btn btn-dark w-100 fw-bold">Publier au registre</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <?php if(empty($lois)): ?>
                <div class="card border-0 shadow-sm text-center p-5">
                    <i class="fa-solid fa-book text-muted fa-3x mb-3"></i>
                    <h5 class="fw-bold text-muted">Aucun texte de loi enregistré</h5>
                    <p class="text-muted small m-0">Le registre législatif de votre église est actuellement vide.</p>
                </div>
            <?php else: ?>
                <?php 
                $current_cat = "";
                foreach($lois as $loi): 
                    if ($categorie_filtre === "" && $current_cat !== $loi['categorie']): 
                        $current_cat = $loi['categorie'];
                        echo "<h5 class='fw-bold text-uppercase text-secondary mt-3 mb-2 small letter-spacing-1'><i class='fa-solid fa-bookmark me-2 text-primary'></i> " . htmlspecialchars($current_cat) . "</h5>";
                    endif;
                ?>
                    <div class="card border-0 shadow-sm mb-3 border-start border-4 <?= $loi['categorie'] === 'Constitution' ? 'border-danger' : 'border-dark' ?>">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="fw-bold text-dark m-0"><?= htmlspecialchars($loi['titre']) ?></h5>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-light text-dark border small" style="font-size:0.75rem;">v<?= htmlspecialchars($loi['version']) ?></span>
                                    
                                    <button type="button" class="btn btn-sm btn-link text-primary p-0 btn-edit" 
                                            data-bs-toggle="modal" data-bs-target="#modalModifierLoi"
                                            data-id="<?= $loi['id'] ?>"
                                            data-titre="<?= htmlspecialchars($loi['titre']) ?>"
                                            data-categorie="<?= htmlspecialchars($loi['categorie']) ?>"
                                            data-version="<?= htmlspecialchars($loi['version']) ?>"
                                            data-date="<?= $loi['date_adoption'] ?>"
                                            data-contenu="<?= htmlspecialchars($loi['contenu']) ?>"
                                            title="Modifier l'article">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-1 btn-delete"
                                            data-bs-toggle="modal" data-bs-target="#modalSupprimerLoi"
                                            data-id="<?= $loi['id'] ?>"
                                            data-titre="<?= htmlspecialchars($loi['titre']) ?>"
                                            title="Supprimer">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <p class="text-dark bg-white rounded py-2 border-0 m-0 text-justify lh-base" style="white-space: pre-line; font-size: 0.95rem;">
                                <?= htmlspecialchars($loi['contenu']) ?>
                            </p>

                            <?php if(!empty($loi['fichier_joint'])): ?>
                                <div class="mt-2 p-2 bg-light rounded d-inline-flex align-items-center border">
                                    <i class="fa-solid fa-file-arrow-down text-danger me-2"></i>
                                    <a href="<?= htmlspecialchars($loi['fichier_joint']) ?>" class="small fw-bold text-decoration-none" target="_blank">
                                        Télécharger la pièce officielle liée
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-3 pt-2 border-top border-light d-flex justify-content-between align-items-center text-muted" style="font-size:0.75rem;">
                                <span>
                                    <i class="fa-regular fa-calendar-check me-1"></i> Adopté le : 
                                    <b><?= $loi['date_adoption'] ? date('d/m/Y', strtotime($loi['date_adoption'])) : 'Inconnue' ?></b>
                                </span>
                                <span>
                                    <i class="fa-solid fa-user-pen me-1"></i> Consigné par : <b><?= htmlspecialchars($loi['nom_admin'] ?? 'Système') ?></b>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="modalModifierLoi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square text-primary me-2"></i>Modifier l'élément du registre</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body text-start">
                    <input type="hidden" name="id_loi" id="edit_id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted">Catégorie *</label>
                            <select name="categorie" id="edit_categorie" class="form-select" required>
                                <?php foreach($categories_disponibles as $cat): ?>
                                    <option value="<?= $cat ?>"><?= $cat ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted">Titre de la section / de l'article *</label>
                            <input type="text" name="titre" id="edit_titre" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted">Version</label>
                            <input type="text" name="version" id="edit_version" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted">Date d'adoption</label>
                            <input type="date" name="date_adoption" id="edit_date" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="small fw-bold text-muted">Remplacer ou ajouter un document officiel (PDF, Word)</label>
                            <input type="file" name="document_joint" class="form-control form-control-sm" accept=".pdf,.doc,.docx">
                        </div>
                        <div class="col-md-12">
                            <label class="small fw-bold text-muted">Corps du texte *</label>
                            <textarea name="contenu" id="edit_contenu" class="form-control" rows="8" required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="modifier_loi" class="btn btn-sm btn-primary fw-bold">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSupprimerLoi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0 py-2">
                <h6 class="modal-title fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i>Confirmation de retrait</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body text-center py-4">
                    <input type="hidden" name="id_loi" id="delete_id">
                    <p class="m-0 small">Êtes-vous sûr de vouloir supprimer définitivement cet article du registre ?<br><strong id="delete_titre" class="text-dark"></strong></p>
                </div>
                <div class="modal-footer border-0 justify-content-center pt-0">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="supprimer_loi" class="btn btn-sm btn-danger fw-bold">Supprimer définitivement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Remplissage de la modal d'édition
    const editButtons = document.querySelectorAll('.btn-edit');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.getAttribute('data-id');
            document.getElementById('edit_titre').value = this.getAttribute('data-titre');
            document.getElementById('edit_categorie').value = this.getAttribute('data-categorie');
            document.getElementById('edit_version').value = this.getAttribute('data-version');
            document.getElementById('edit_date').value = this.getAttribute('data-date');
            document.getElementById('edit_contenu').value = this.getAttribute('data-contenu');
        });
    });

    // Remplissage de la modal de suppression
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('delete_id').value = this.getAttribute('data-id');
            document.getElementById('delete_titre').textContent = this.getAttribute('data-titre');
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>