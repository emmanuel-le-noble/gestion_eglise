<?php
// eglise_db/gouvernance/lois.php
require_once "../config/database.php";
require_once "../includes/session.php";
// Toutes les pages du dossier gouvernance contiendront cette ligne :
securiser_par_module($pdo, 'gouvernance');

$message = "";
$erreur = "";

// Categorie filtrée par défaut (toutes si vide)
$categorie_filtre = $_GET['categorie'] ?? '';

// ==========================================
// 1. TRAITEMENT DU FORMULAIRE (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_loi'])) {
    $titre = trim($_POST['titre']);
    $contenu = trim($_POST['contenu']);
    $categorie = $_POST['categorie'];
    $version = trim($_POST['version']) ?: '1.0';
    $date_adoption = !empty($_POST['date_adoption']) ? $_POST['date_adoption'] : null;
    $utilisateur_id = $_SESSION['user_id'] ?? null;

    if (!empty($titre) && !empty($contenu)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO lois_eglise (titre, contenu, categorie, version, date_adoption, utilisateur_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$titre, $contenu, $categorie, $version, $date_adoption, $utilisateur_id]);
            $message = "Le texte de loi / article a été enregistré avec succès !";
        } catch (PDOException $e) {
            $erreur = "Erreur lors de l'enregistrement : " . $e->getMessage();
        }
    } else {
        $erreur = "Le titre et le contenu du texte sont obligatoires.";
    }
}

// Supprimer un article (optionnel mais utile)
if (isset($_GET['supprimer_id'])) {
    $supprimer_id = (int)$_GET['supprimer_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM lois_eglise WHERE id = ?");
        $stmt->execute([$supprimer_id]);
        $message = "Texte législatif supprimé.";
    } catch (PDOException $e) {
        $erreur = "Impossible de supprimer ce texte.";
    }
}

// ==========================================
// 2. RECUPÉRATION ET FILTRE DES TEXTES
// ==========================================
$categories_disponibles = ['Constitution', 'Statuts', 'Règlement Intérieur', 'Résolution', 'Charte'];

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
unset($stmt);

$page_title = "Lois, statuts & règlements";
require_once '../includes/header.php';
?>

<div class="container mt-4">
    <!-- En-tête de page avec actions de navigation et filtres -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark m-0"><i class="fa-solid fa-gavel text-secondary me-2"></i>Textes législatifs & règlements</h3>
            <p class="text-muted small mb-0">Registre officiel de la constitution, des règles internes et chartes de l'Église.</p>
        </div>
        
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <!-- BOUTON RETOUR À L'INDEX GOUVERNANCE -->
            <a href="index.php" class="btn btn-outline-dark btn-sm fw-bold px-3 py-2 shadow-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Tableau de bord
            </a>

            <!-- Filtre de catégorie -->
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
        <!-- Formulaire d'ajout -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0"><i class="fa-solid fa-pen-to-square text-success me-2"></i>Consigner un article / acte</h6>
                </div>
                <div class="card-body pt-0">
                    <form method="POST">
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
                            <label class="small fw-bold text-muted">Contenu du texte légal *</label>
                            <textarea name="contenu" class="form-control fw-normal" rows="8" placeholder="Écrivez ici le corps complet du texte ou de l'article de loi..." required></textarea>
                        </div>

                        <button type="submit" name="ajouter_loi" class="btn btn-dark w-100 fw-bold">Publier au registre</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Liste des textes -->
        <div class="col-md-8">
            <?php if(empty($lois)): ?>
                <div class="card border-0 shadow-sm text-center p-5">
                    <i class="fa-solid fa-book text-muted fa-3x mb-3"></i>
                    <h5 class="fw-bold text-muted">Aucun texte de loi enregistré</h5>
                    <p class="text-muted small m-0">Le registre législatif de votre église est actuellement vide ou aucune donnée ne correspond au filtre sélectionné.</p>
                </div>
            <?php else: ?>
                <?php 
                $current_cat = "";
                foreach($lois as $loi): 
                    // Séparateur visuel par catégorie si on affiche "Tout"
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
                                    <a href="?supprimer_id=<?= $loi['id'] ?>&categorie=<?= $categorie_filtre ?>" class="text-danger small ms-1" onclick="return confirm('Supprimer cet article du registre officiel ?')" title="Supprimer l'article">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </a>
                                </div>
                            </div>
                            
                            <p class="text-dark bg-white rounded py-2 border-0 m-0 text-justify lh-base" style="white-space: pre-line; font-size: 0.95rem;">
                                <?= htmlspecialchars($loi['contenu']) ?>
                            </p>
                            
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

<?php require_once '../includes/footer.php'; ?>