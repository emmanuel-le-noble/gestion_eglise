<?php
// eglise_db/gouvernance/organigramme.php
require_once "../config/database.php";
require_once "../includes/session.php";

// Sécurisation de l'accès au module
securiser_par_module($pdo, 'gouvernance');

$message = "";
$message_type = "";

// ==========================================
// 1. TRAITEMENT DES FORMULAIRES (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // AJOUT / MODIFICATION D'UN POSTE
    if (isset($_POST['enregistrer_poste'])) {
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $titre_poste = trim($_POST['titre_poste']);
        $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        
        if (empty($titre_poste)) {
            $message = "Le titre du poste est obligatoire.";
            $message_type = "danger";
        } else {
            // Calcul automatique du niveau hiérarchique
            $niveau = 1;
            if ($parent_id) {
                $stmt_parent = $pdo->prepare("SELECT niveau_hierarchique FROM organigramme WHERE id = ?");
                $stmt_parent->execute([$parent_id]);
                $niveau = (int)$stmt_parent->fetchColumn() + 1;
            }

            if ($id) {
                // Mode Modification
                if ($id === $parent_id) {
                    $message = "Un poste ne peut pas être son propre supérieur hiérarchique.";
                    $message_type = "danger";
                } else {
                    $stmt = $pdo->prepare("UPDATE organigramme SET titre_poste = ?, parent_id = ?, niveau_hierarchique = ? WHERE id = ?");
                    $stmt->execute([$titre_poste, $parent_id, $niveau, $id]);
                    $message = "Poste mis à jour avec succès.";
                    $message_type = "success";
                }
            } else {
                // Mode Ajout
                $stmt = $pdo->prepare("INSERT INTO organigramme (titre_poste, parent_id, niveau_hierarchique) VALUES (?, ?, ?)");
                $stmt->execute([$titre_poste, $parent_id, $niveau]);
                $message = "Nouveau poste rattaché à l'organigramme.";
                $message_type = "success";
            }
        }
    }

    // SUPPRESSION D'UN POSTE
    if (isset($_POST['supprimer_poste'])) {
        $id_suppr = (int)$_POST['id_suppr'];
        
        $stmt_parent = $pdo->prepare("SELECT parent_id FROM organigramme WHERE id = ?");
        $stmt_parent->execute([$id_suppr]);
        $ancien_parent = $stmt_parent->fetchColumn();
        
        $stmt_rebind = $pdo->prepare("UPDATE organigramme SET parent_id = ? WHERE parent_id = ?");
        $stmt_rebind->execute([$ancien_parent ?: null, $id_suppr]);

        $stmt = $pdo->prepare("DELETE FROM organigramme WHERE id = ?");
        $stmt->execute([$id_suppr]);
        
        $message = "Poste supprimé. Les sous-postes associés ont été réajustés.";
        $message_type = "warning";
    }
}

// ==========================================
// 2. RÉCUPÉRATION DES DONNÉES
// ==========================================

$tous_les_postes = $pdo->query("SELECT id, titre_poste FROM organigramme ORDER BY titre_poste")->fetchAll(PDO::FETCH_ASSOC);
$postes_bruts = $pdo->query("SELECT id, titre_poste, parent_id, niveau_hierarchique FROM organigramme")->fetchAll(PDO::FETCH_ASSOC);

$arbre_hierarchique = [];
foreach ($postes_bruts as $poste) {
    $parent_id = $poste['parent_id'] ?? 0;
    $arbre_hierarchique[$parent_id][] = $poste;
}

function afficher_arbre_vertical($parent_id, $arbre) {
    if (!isset($arbre[$parent_id])) return;

    echo '<ul>';
    foreach ($arbre[$parent_id] as $poste) {
        $has_children = isset($arbre[$poste['id']]);
        
        echo '<li>';
        echo '  <div class="card-poste-vertical shadow-sm">';
        echo '      <div class="titre-poste-text">' . htmlspecialchars($poste['titre_poste']) . '</div>';
        echo '      <div class="actions-poste mt-2">';
        echo '          <button type="button" class="btn btn-xs btn-light text-primary border-0 btn-edit" data-json=\'' . json_encode($poste) . '\'><i class="fa-solid fa-pen-to-square"></i></button>';
        echo '          <button type="button" class="btn btn-xs btn-light text-danger border-0 btn-delete" data-id="' . $poste['id'] . '" data-titre="' . htmlspecialchars($poste['titre_poste']) . '"><i class="fa-solid fa-trash-can"></i></button>';
        echo '      </div>';
        echo '  </div>';

        if ($has_children) {
            afficher_arbre_vertical($poste['id'], $arbre);
        }
        echo '</li>';
    }
    echo '</ul>';
}

$page_title = "Structure de l'organigramme";
require_once '../includes/header.php';
?>

<div class="container-fluid mt-4 px-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0"><i class="fa-solid fa-network-wired text-danger me-2"></i>Organigramme structural</h3>
            <p class="text-muted small m-0">Gérez la hiérarchie des postes de l'église.</p>
        </div>
        <a href="index.php" class="btn btn-outline-danger btn-sm fw-bold">
            <i class="fa-solid fa-arrow-left me-1"></i> Retour admin
        </a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show small" role="alert">
            <i class="fa-solid <?= $message_type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?> me-2"></i><?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark mb-4" id="form-title"><i class="fa-solid fa-plus-circle text-success me-2"></i>Ajouter un poste hiérarchique</h5>
                    
                    <form action="" method="POST" id="form-poste" class="row g-3 align-items-end">
                        <input type="hidden" name="id" id="poste_id">
                        <input type="hidden" name="enregistrer_poste" value="1">

                        <div class="col-md-5">
                            <label class="form-label small fw-bold text-secondary">Intitulé du poste <span class="text-danger">*</span></label>
                            <input type="text" name="titre_poste" id="titre_poste" class="form-control" placeholder="Ex: Pasteur Principal, Responsable Jeunesse..." required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-secondary">Rattaché à (supérieur n+1)</label>
                            <select name="parent_id" id="parent_id" class="form-select">
                                <option value="">-- Sommet (aucun parent) --</option>
                                <?php foreach ($tous_les_postes as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['titre_poste']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success fw-bold flex-grow-1" id="btn-submit">
                                    <i class="fa-solid fa-floppy-disk me-1"></i> Enregistrer
                                </button>
                                <button type="button" class="btn btn-light text-secondary border fw-bold d-none" id="btn-annuler">
                                    Annuler
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h5 class="fw-bold text-dark m-0"><i class="fa-solid fa-tree text-secondary me-2"></i>Visualisation de la structure</h5>
                    <hr class="mb-0 mt-3 opacity-25">
                </div>
                <div class="card-body p-0">
                    <?php if (empty($arbre_hierarchique)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fa-solid fa-folder-open fa-2x mb-2 text-light-dark"></i>
                            <p class="m-0 small">Aucun poste configuré.</p>
                        </div>
                    <?php else: ?>
                        <div class="organigramme-container py-5">
                            <div class="tree">
                                <?php afficher_arbre_vertical(0, $arbre_hierarchique); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-0 py-2">
                <h6 class="modal-title fw-bold text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i>Suppression</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="supprimer_poste" value="1">
                <input type="hidden" name="id_suppr" id="id_suppr">
                <div class="modal-body py-3">
                    <p class="text-dark small m-0">Supprimer le poste <b id="suppr-titre"></b> ? Les sous-postes seront rattachés au niveau supérieur.</p>
                </div>
                <div class="modal-footer border-0 bg-light py-2 d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary btn-xs fw-bold" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger btn-xs fw-bold">Confirmer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Conteneur de l'organigramme */
.organigramme-container {
    width: 100%;
    overflow-x: auto;
    background-color: #fcfcfc;
    min-height: 400px;
}

/* Force absolue du retour à la ligne automatique */
.titre-poste-text {
    font-size: 0.85rem;
    font-weight: 600;
    line-height: 1.4;
    white-space: normal !important; 
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
}

/* Base de l'arbre */
.tree {
    display: inline-block;
    min-width: 100%;
}

.tree ul {
    padding-top: 20px; 
    position: relative;
    transition: all 0.5s;
    display: flex; 
    justify-content: center;
}

.tree li {
    text-align: center;
    list-style-type: none;
    position: relative; 
    padding: 20px 10px 0 10px;
    transition: all 0.5s;
}

/* Connecteurs horizontaux */
.tree li::before, .tree li::after{
    content: ''; position: absolute; top: 0; right: 50%;
    border-top: 2px solid #ccc;
    width: 50%; height: 20px;
}
.tree li::after{
    right: auto; left: 50%;
    border-left: 2px solid #ccc;
}

/* Nettoyage des connecteurs */
.tree li:only-child::after, .tree li:only-child::before { display: none; }
.tree li:only-child{ padding-top: 0; }
.tree li:first-child::before, .tree li:last-child::after{ border: 0 none; }
.tree li:last-child::before{ border-right: 2px solid #ccc; border-radius: 0 5px 0 0; }
.tree li:first-child::after{ border-radius: 5px 0 0 0; }

/* Connecteurs verticaux vers les enfants */
.tree ul ul::before{
    content: ''; position: absolute; top: 0; left: 50%;
    border-left: 2px solid #ccc;
    width: 0; height: 20px;
}

/* Node (Bloc de poste) */
.card-poste-vertical {
    border: 2px solid #eee;
    padding: 8px;
    /* height: 110px; */
    background: #fff;
    border-radius: 8px;
    display: inline-block;
    min-width: 130px;
    max-width: 200px;
    position: relative;
    transition: all 0.3s ease;
    z-index: 10;
}
.card-poste-vertical:hover {
    border-color: #dc3545;
    background-color: #fff;
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.08)!important;
}

.btn-xs { padding: .2rem .4rem; font-size: .75rem; }
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // ÉDITION
    document.querySelectorAll(".btn-edit").forEach(btn => {
        btn.addEventListener("click", function() {
            const data = JSON.parse(this.getAttribute("data-json"));
            document.getElementById("form-title").innerHTML = '<i class="fa-solid fa-pen-to-square text-primary me-2"></i>Modifier le poste';
            document.getElementById("poste_id").value = data.id;
            document.getElementById("titre_poste").value = data.titre_poste;
            document.getElementById("parent_id").value = data.parent_id ? data.parent_id : "";
            
            document.getElementById("btn-submit").className = "btn btn-primary fw-bold flex-grow-1";
            document.getElementById("btn-submit").innerHTML = '<i class="fa-solid fa-arrows-rotate me-1"></i> Mettre à jour';
            document.getElementById("btn-annuler").classList.remove("d-none");
            
            // Remonter vers le formulaire en douceur
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    // ANNULER
    document.getElementById("btn-annuler").addEventListener("click", function() {
        document.getElementById("form-title").innerHTML = '<i class="fa-solid fa-plus-circle text-success me-2"></i>Ajouter un poste hiérarchique';
        document.getElementById("form-poste").reset();
        document.getElementById("poste_id").value = "";
        this.classList.add("d-none");
        document.getElementById("btn-submit").className = "btn btn-success fw-bold flex-grow-1";
        document.getElementById("btn-submit").innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Enregistrer';
    });

    // SUPPRESSION
    const myModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    document.querySelectorAll(".btn-delete").forEach(btn => {
        btn.addEventListener("click", function() {
            document.getElementById("id_suppr").value = this.getAttribute("data-id");
            document.getElementById("suppr-titre").innerText = this.getAttribute("data-titre");
            myModal.show();
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>