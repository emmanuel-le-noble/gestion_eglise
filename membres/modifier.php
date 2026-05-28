<?php
// eglise_db/membres/modifier.php
require_once "../config/database.php";
require_once "../includes/session.php"; 
require_once '../includes/helpers.php';

// Sécurisation de la page
securiser_par_module($pdo, 'membres');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login(); 

$message = "";
$erreur = "";

// 1. Récupération et vérification de l'ID du membre parent
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!$id) {
    header("Location: index.php");
    exit;
}

// Récupération initiale du membre
$stmt = $pdo->prepare("SELECT * FROM membres WHERE id = ?");
$stmt->execute([$id]);
$membre = $stmt->fetch();

if (!$membre) {
    echo "<div class='alert alert-danger m-4'>Membre introuvable.</div>";
    require_once '../includes/footer.php';
    exit;
}

// 2. Traitement de la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $nom = trim($_POST['nom'] ?? '');
    $prenoms = trim($_POST['prenoms'] ?? '');
    $sexe = $_POST['sexe'] ?? '';
    $date_naissance = !empty($_POST['date_naissance']) ? $_POST['date_naissance'] : null;
    $lieu_naissance = trim($_POST['lieu_naissance'] ?? '');
    $profession = trim($_POST['profession'] ?? '');
    $eglise_provenance = trim($_POST['eglise_provenance'] ?? '');
    $date_arrivee = !empty($_POST['date_arrivee']) ? $_POST['date_arrivee'] : null;
    
    $baptise = isset($_POST['baptise']) ? 1 : 0;
    $date_bapteme = (!empty($_POST['date_bapteme']) && $baptise) ? $_POST['date_bapteme'] : null;
    $lieu_bapteme = ($baptise) ? trim($_POST['lieu_bapteme'] ?? '') : null;
    $engagement_moral = isset($_POST['engagement_moral']) ? 1 : 0;
    $groupe_action = $_POST['groupe_action'] ?? '';
    $qualite = $_POST['qualite'] ?? '';
    $statut_membre = $_POST['statut'] ?? 'Actif'; 

    $telephone1 = trim($_POST['telephone1'] ?? '');
    $telephone2 = trim($_POST['telephone2'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $quartier = trim($_POST['quartier'] ?? '');
    $situation_matrimoniale = $_POST['situation_matrimoniale'] ?? '';
    $date_mariage = (!empty($_POST['date_mariage']) && $situation_matrimoniale !== 'Célibataire') ? $_POST['date_mariage'] : null;
    $lieu_mariage = ($situation_matrimoniale !== 'Célibataire') ? trim($_POST['lieu_mariage'] ?? '') : null;
    $nom_conjoint = trim($_POST['nom_conjoint'] ?? '');
    $nombre_enfants = (int)($_POST['nombre_enfants'] ?? 0);
    $commentaire = trim($_POST['commentaire'] ?? '');

    // Gestion de la photo
    $photo_nom = $_POST['ancienne_photo'] ?? 'default.png';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['photo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $target_dir = __DIR__ . "/../assets/uploads/membres/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            if (!empty($_POST['ancienne_photo']) && !in_array($_POST['ancienne_photo'], ['default.png', 'default_avatar.png'])) {
                $ancien_fichier = $target_dir . $_POST['ancienne_photo'];
                if (file_exists($ancien_fichier)) {
                    @unlink($ancien_fichier);
                }
            }

            $photo_nom = "PHOTO_MOD_" . $id . "_" . time() . "." . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], $target_dir . $photo_nom);
        }
    }

    if (!empty($nom) && !empty($prenoms) && !empty($sexe)) {
        try {
            $pdo->beginTransaction();

            // Mise à jour du membre parent
            $sql = "UPDATE membres SET 
                photo = ?, nom = ?, prenoms = ?, sexe = ?, date_naissance = ?, lieu_naissance = ?, profession = ?, 
                eglise_provenance = ?, date_arrivee = ?, baptise = ?, date_bapteme = ?, lieu_bapteme = ?, 
                engagement_moral = ?, groupe_action = ?, qualite = ?, statut_membre = ?, telephone1 = ?, telephone2 = ?, email = ?, 
                quartier = ?, situation_matrimoniale = ?, date_mariage = ?, lieu_mariage = ?, nom_conjoint = ?, 
                nombre_enfants = ?, commentaire = ?
                WHERE id = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $photo_nom, $nom, $prenoms, $sexe, $date_naissance, $lieu_naissance, $profession,
                $eglise_provenance, $date_arrivee, $baptise, $date_bapteme, $lieu_bapteme,
                $engagement_moral, $groupe_action, $qualite, $statut_membre, $telephone1, $telephone2, $email,
                $quartier, $situation_matrimoniale, $date_mariage, $lieu_mariage, $nom_conjoint,
                $nombre_enfants, $commentaire, $id
            ]);

            // --- GESTION DES ENFANTS ---
            // Supprimer les anciennes liaisons de dépendance pour ce parent
            $stmt_delete = $pdo->prepare("DELETE FROM enfants WHERE membre_id = ?");
            $stmt_delete->execute([$id]);

            if (isset($_POST['enfants']) && is_array($_POST['enfants'])) {
                $sql_enfant = "INSERT INTO enfants (membre_id, enfant_membre_id, nom, prenoms, sexe, date_naissance) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_enfant = $pdo->prepare($sql_enfant);

                $sql_insert_auto = "INSERT INTO membres (matricule, nom, prenoms, sexe, date_naissance, qualite, groupe_action, statut_membre) 
                                    VALUES (?, ?, ?, ?, ?, 'Enfant', 'Enfants', 'Actif')";
                $stmt_insert_auto = $pdo->prepare($sql_insert_auto);

                $sql_update_auto = "UPDATE membres SET nom = ?, prenoms = ?, sexe = ?, date_naissance = ? WHERE id = ?";
                $stmt_update_auto = $pdo->prepare($sql_update_auto);

                foreach ($_POST['enfants'] as $key => $enfant_data) {
                    $nom_enf = trim($enfant_data['nom'] ?? '');
                    if (empty($nom_enf)) continue;

                    $prenom_enf = trim($enfant_data['prenoms'] ?? '');
                    $sexe_enf = $enfant_data['sexe'] ?? 'Masculin';
                    $enfant_dob = !empty($enfant_data['dob']) ? $enfant_data['dob'] : null;
                    $enfant_membre_id = !empty($enfant_data['enfant_membre_id']) ? (int)$enfant_data['enfant_membre_id'] : null;

                    // Si la case d'inscription est cochée
                    if (isset($enfant_data['inscrire']) && $enfant_data['inscrire'] == '1') {
                        if ($enfant_membre_id) {
                            // Mettre à jour l'enfant autonome existant au cas où ses infos ont changé
                            $stmt_update_auto->execute([$nom_enf, $prenom_enf, $sexe_enf, $enfant_dob, $enfant_membre_id]);
                        } else {
                            // Vérifier les doublons globaux avant création numérique
                            $stmt_check = $pdo->prepare("SELECT id FROM membres WHERE nom = ? AND prenoms = ? AND date_naissance = ?");
                            $stmt_check->execute([$nom_enf, $prenom_enf, $enfant_dob]);
                            $existing_membre = $stmt_check->fetch();

                            if (!$existing_membre) {
                                // Génération d'un matricule plus robuste (E + année + identifiant unique court)
                                $matricule_enfant = "E-" . date('Y') . mt_rand(1000, 9999);
                                $stmt_insert_auto->execute([$matricule_enfant, $nom_enf, $prenom_enf, $sexe_enf, $enfant_dob]);
                                $enfant_membre_id = (int)$pdo->lastInsertId();
                            } else {
                                $enfant_membre_id = (int)$existing_membre['id'];
                            }
                        }
                    } else {
                        // Si décoché, on rompt le lien membre (la fiche autonome reste en base mais n'est plus liée à l'état "Inscrit" ici)
                        $enfant_membre_id = null;
                    }

                    // Insertion de la liaison
                    $stmt_enfant->execute([$id, $enfant_membre_id, $nom_enf, $prenom_enf, $sexe_enf, $enfant_dob]);
                }
            }

            $pdo->commit();
            $message = "La fiche du membre a été mise à jour avec succès !";
            
            if (function_exists('enregistrer_log')) {
                $id_operateur = $_SESSION['user_id'] ?? null;
                $details_log = "Mise à jour des informations du membre : $nom $prenoms (ID: #$id)";
                $enregistrer = enregistrer_log($pdo, "Modification", $details_log, $id_operateur);
            }
            
            // Re-charger les données
            $stmt = $pdo->prepare("SELECT * FROM membres WHERE id = ?");
            $stmt->execute([$id]);
            $membre = $stmt->fetch();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $erreur = "Erreur lors de la modification : " . $e->getMessage();
        }
    } else {
        $erreur = "Veuillez remplir tous les champs obligatoires (*).";
    }
}

// 3. Récupération des enfants
$stmt_enfants = $pdo->prepare("
    SELECT e.*, m.matricule 
    FROM enfants e
    LEFT JOIN membres m ON e.enfant_membre_id = m.id
    WHERE e.membre_id = ?
");
$stmt_enfants->execute([$id]);
$enfants_existants = $stmt_enfants->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Modifier les infos du membre"; 
require_once '../includes/header.php'; 
?>

<div class="container mt-4">
    <?php if(!empty($message)): ?>
        <div class="alert alert-success border-0 shadow-sm mb-4"><i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if(!empty($erreur)): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4"><i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-5">
        <div class="card-body p-4">
            <form action="modifier.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="ancienne_photo" value="<?= htmlspecialchars($membre['photo'] ?? 'default.png') ?>">

                <div class="row g-4">
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <h5 class="text-primary fw-semibold mb-0"><i class="fa-solid fa-id-card me-2"></i>1. État civil & identification</h5>
                        <a href="index.php" class="btn btn-light btn-sm border">Retour</a>
                    </div>
                    <hr class="text-muted opacity-25 mt-2">
                    
                    <div class="col-md-3 text-center border-end">
                        <label class="form-label text-secondary small fw-bold">Photo du membre</label>
                        <div class="mb-2">
                            <?php 
                            $src_photo = "../assets/img/default_avatar.png";
                            if(!empty($membre['photo']) && file_exists(__DIR__ . "/../assets/uploads/membres/" . $membre['photo'])) {
                                $src_photo = "../assets/uploads/membres/" . $membre['photo'];
                            }
                            ?>
                            <img src="<?= $src_photo ?>" id="preview" class="img-thumbnail rounded" style="width: 140px; height: 140px; object-fit: cover;">
                        </div>
                        <input type="file" name="photo" id="photo_input" class="form-control form-control-sm" accept="image/png, image/jpeg">
                    </div>

                    <div class="col-md-9">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold">Matricule</label>
                                <input type="text" class="form-control bg-light fw-bold text-danger" value="<?= htmlspecialchars($membre['matricule'] ?? '') ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold">Nom <span class="text-danger">*</span></label>
                                <input type="text" name="nom" class="form-control bg-light border-0" value="<?= htmlspecialchars($membre['nom'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold">Prénoms <span class="text-danger">*</span></label>
                                <input type="text" name="prenoms" class="form-control bg-light border-0" value="<?= htmlspecialchars($membre['prenoms'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-secondary small fw-bold">Sexe <span class="text-danger">*</span></label>
                                <select name="sexe" class="form-select bg-light border-0" required>
                                    <option value="Masculin" <?= ($membre['sexe'] ?? '') === 'Masculin' ? 'selected' : '' ?>>Masculin</option>
                                    <option value="Feminin" <?= ($membre['sexe'] ?? '') === 'Feminin' ? 'selected' : '' ?>>Féminin</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-secondary small fw-bold">Date de naissance</label>
                                <input type="date" name="date_naissance" class="form-control bg-light border-0" value="<?= $membre['date_naissance'] ?? '' ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold">Lieu de naissance</label>
                                <input type="text" name="lieu_naissance" class="form-control bg-light border-0" value="<?= htmlspecialchars($membre['lieu_naissance'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold">Profession</label>
                                <input type="text" name="profession" class="form-control bg-light border-0" value="<?= htmlspecialchars($membre['profession'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mt-5">
                        <h5 class="text-primary fw-semibold mb-0"><i class="fa-solid fa-map-location-dot me-2"></i>2. Adresse & Contact</h5>
                        <hr class="text-muted opacity-25 mt-2">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold">Téléphone 1 <span class="text-danger">*</span></label>
                        <input type="text" name="telephone1" class="form-control bg-light border-0" value="<?= htmlspecialchars($membre['telephone1'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold">Téléphone 2</label>
                        <input type="text" name="telephone2" class="form-control bg-light border-0" value="<?= htmlspecialchars($membre['telephone2'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold">E-mail</label>
                        <input type="email" name="email" class="form-control bg-light border-0" value="<?= htmlspecialchars($membre['email'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label text-secondary small fw-bold">Domicile (Quartier)</label>
                        <input type="text" name="quartier" class="form-control bg-light border-0" value="<?= htmlspecialchars($membre['quartier'] ?? '') ?>">
                    </div>

                    <div class="col-12 mt-5">
                        <h5 class="text-primary fw-semibold mb-0"><i class="fa-solid fa-heart me-2"></i>3. Situation de famille</h5>
                        <hr class="text-muted opacity-25 mt-2">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold">Statut Matrimonial</label>
                        <select name="situation_matrimoniale" id="statut_matri" class="form-select bg-light border-0">
                            <option value="Célibataire" <?= ($membre['situation_matrimoniale'] ?? '') === 'Célibataire' ? 'selected' : '' ?>>Célibataire</option>
                            <option value="Marié(e)" <?= ($membre['situation_matrimoniale'] ?? '') === 'Marié(e)' ? 'selected' : '' ?>>Marié(e)</option>
                            <option value="Veuf(ve)" <?= ($membre['situation_matrimoniale'] ?? '') === 'Veuf(ve)' ? 'selected' : '' ?>>Veuf(ve)</option>
                            <option value="Divorcé(e)" <?= ($membre['situation_matrimoniale'] ?? '') === 'Divorcé(e)' ? 'selected' : '' ?>>Divorcé(e)</option>
                        </select>
                    </div>

                    <div class="col-md-9">
                        <div class="row g-3" id="section_mariage" style="display: none;">
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold">Date mariage</label>
                                <input type="date" name="date_mariage" class="form-control bg-light border-0" value="<?= $membre['date_mariage'] ?? '' ?>">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label text-secondary small fw-bold">Lieu du mariage</label>
                                <input type="text" name="lieu_mariage" class="form-control bg-light border-0" value="<?= htmlspecialchars($membre['lieu_mariage'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-secondary small fw-bold">Nom et prénoms conjoint(e)</label>
                        <input type="text" name="nom_conjoint" class="form-control bg-light border-0" value="<?= htmlspecialchars($membre['nom_conjoint'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-secondary small fw-bold">Nombre d'enfants</label>
                        <input type="number" name="nombre_enfants" id="nombre_enfants" class="form-control bg-light border-0" value="<?= (int)($membre['nombre_enfants'] ?? 0) ?>" min="0">
                    </div>

                    <div class="col-12 mt-4">
                        <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded">
                            <h6 class="m-0 fw-bold text-dark"><i class="fa-solid fa-baby me-2"></i>Détails des enfants</h6>
                            <small class="text-muted small">Cochez "Groupe (E)" pour lier/inscrire l'enfant comme membre autonome</small>
                        </div>
                        <div id="enfants_container" class="mt-3"></div>
                    </div>

                    <div class="col-12 mt-5">
                        <h5 class="text-primary fw-semibold mb-0"><i class="fa-solid fa-church me-2"></i>4. Situation ecclésiastique</h5>
                        <hr class="text-muted opacity-25 mt-2">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold">Église de provenance</label>
                        <input type="text" name="eglise_provenance" class="form-control bg-light border-0" value="<?= htmlspecialchars($membre['eglise_provenance'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold">Date d'arrivée à l'église</label>
                        <input type="date" name="date_arrivee" class="form-control bg-light border-0" value="<?= $membre['date_arrivee'] ?? '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold">Groupe d'action</label>
                        <select name="groupe_action" class="form-select bg-light border-0">
                            <option value="Hommes" <?= ($membre['groupe_action'] ?? '') === 'Hommes' ? 'selected' : '' ?>>Hommes (H)</option>
                            <option value="Femmes" <?= ($membre['groupe_action'] ?? '') === 'Femmes' ? 'selected' : '' ?>>Femmes (F)</option>
                            <option value="Jeunesses" <?= ($membre['groupe_action'] ?? '') === 'Jeunesses' ? 'selected' : '' ?>>Jeunesses (J)</option>
                            <option value="Enfants" <?= ($membre['groupe_action'] ?? '') === 'Enfants' ? 'selected' : '' ?>>Enfants (E)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold">Qualité</label>
                        <select name="qualite" class="form-select bg-light border-0">
                            <option value="Membre" <?= ($membre['qualite'] ?? '') === 'Membre' ? 'selected' : '' ?>>Membre</option>
                            <option value="Ami" <?= ($membre['qualite'] ?? '') === 'Ami' ? 'selected' : '' ?>>Ami</option>
                            <option value="Enfant" <?= ($membre['qualite'] ?? '') === 'Enfant' ? 'selected' : '' ?>>Enfant</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold">Statut</label>
                        <select name="statut" class="form-select bg-light border-0">
                            <option value="Actif" <?= ($membre['statut_membre'] ?? '') === 'Actif' ? 'selected' : '' ?>>Actif</option>
                            <option value="Inactif" <?= ($membre['statut_membre'] ?? '') === 'Inactif' ? 'selected' : '' ?>>Inactif</option>
                            <option value="Abandon" <?= ($membre['statut_membre'] ?? '') === 'Abandon' ? 'selected' : '' ?>>Abandon</option>
                            <option value="Départ" <?= ($membre['statut_membre'] ?? '') === 'Départ' ? 'selected' : '' ?>>Départ</option>
                        </select>
                    </div>

                    <div class="col-12 my-3">
                        <div class="form-check form-switch d-inline-block me-5">
                            <input class="form-check-input" type="checkbox" name="baptise" id="baptiseCheck" value="1" <?= (int)($membre['baptise'] ?? 0) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="baptiseCheck">Baptisé par immersion</label>
                        </div>
                        <div class="form-check form-switch d-inline-block">
                            <input class="form-check-input" type="checkbox" name="engagement_moral" id="engagement" value="1" <?= (int)($membre['engagement_moral'] ?? 0) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="engagement">Engagement moral signed</label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-secondary small fw-bold">Date du baptême</label>
                        <input type="date" name="date_bapteme" class="form-control bg-light border-0" value="<?= $membre['date_bapteme'] ?? '' ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-secondary small fw-bold">Lieu du baptême</label>
                        <input type="text" name="lieu_bapteme" class="form-control bg-light border-0" value="<?= htmlspecialchars($membre['lieu_bapteme'] ?? '') ?>">
                    </div>

                    <div class="col-12 mt-4">
                        <label class="form-label text-secondary small fw-bold">Commentaire / Observations</label>
                        <textarea name="commentaire" class="form-control bg-light border-0" rows="3"><?= htmlspecialchars($membre['commentaire'] ?? '') ?></textarea>
                    </div>

                    <div class="col-12 text-end mt-5 border-top pt-3">
                        <a href="index.php" class="btn btn-light px-4 me-2">Annuler</a>
                        <button type="submit" class="btn btn-primary px-5 shadow-sm fw-medium">Enregistrer les modifications</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Section Mariage Toggle
    const selectStatut = document.getElementById('statut_matri');
    const sectionMariage = document.getElementById('section_mariage');

    function toggleMariage() {
        if(sectionMariage) {
            sectionMariage.style.display = (selectStatut.value !== 'Célibataire') ? 'flex' : 'none';
        }
    }
    if(selectStatut) {
        selectStatut.addEventListener('change', toggleMariage);
        toggleMariage();
    }

    // 2. Preview Image
    const photoInput = document.getElementById('photo_input');
    const preview = document.getElementById('preview');
    if (photoInput && preview) {
        photoInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                preview.src = URL.createObjectURL(file);
            }
        });
    }

    // 3. Synchronisation dynamique des lignes d'enfants sécurisée contre les failles XSS en JS
    const inputNombreEnfants = document.getElementById('nombre_enfants');
    const containerEnfants = document.getElementById('enfants_container');
    
    // Échappement JSON sécurisé
    const enfantsExistants = <?= json_encode($enfants_existants, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    function synchroniserEnfants() {
        if(!containerEnfants || !inputNombreEnfants) return;

        const count = Math.max(0, parseInt(inputNombreEnfants.value) || 0);
        const currentRows = containerEnfants.querySelectorAll('.enfant-row').length;

        if (count > currentRows) {
            for (let i = currentRows; i < count; i++) {
                const dataEnfant = enfantsExistants[i] || { nom: '', prenoms: '', sexe: 'Masculin', date_naissance: '', enfant_membre_id: '', matricule: '' };
                
                let badgeMatricule = dataEnfant.matricule 
                    ? `<span class="badge bg-success ms-1">Matricule : ${dataEnfant.matricule}</span>`
                    : `<span class="badge bg-secondary ms-1">Non inscrit</span>`;

                const row = document.createElement('div');
                row.className = 'row g-2 mb-2 align-items-end border-bottom pb-3 enfant-row';
                row.innerHTML = `
                    <input type="hidden" name="enfants[${i}][enfant_membre_id]" value="${dataEnfant.enfant_membre_id || ''}">
                    
                    <div class="col-md-3">
                        <label class="small text-muted fw-bold">Nom de l'enfant #${i+1} ${badgeMatricule}</label>
                        <input type="text" name="enfants[${i}][nom]" class="form-control form-control-sm border-0 bg-light" placeholder="Nom" value="${escapeHtml(dataEnfant.nom)}">
                    </div>
                    <div class="col-md-3">
                        <label class="small text-muted fw-bold">Prénoms</label>
                        <input type="text" name="enfants[${i}][prenoms]" class="form-control form-control-sm border-0 bg-light" placeholder="Prénoms" value="${escapeHtml(dataEnfant.prenoms)}">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted fw-bold">Sexe</label>
                        <select name="enfants[${i}][sexe]" class="form-select form-select-sm border-0 bg-light">
                            <option value="Masculin" ${dataEnfant.sexe === 'Masculin' ? 'selected' : ''}>Masculin</option>
                            <option value="Feminin" ${dataEnfant.sexe === 'Feminin' ? 'selected' : ''}>Féminin</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted fw-bold">Date Naissance</label>
                        <input type="date" name="enfants[${i}][dob]" class="form-control form-control-sm border-0 bg-light" value="${dataEnfant.date_naissance || ''}">
                    </div>
                    <div class="col-md-2 d-flex align-items-center justify-content-center pb-1">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="enfants[${i}][inscrire]" value="1" id="switchEnfant_${i}" ${dataEnfant.enfant_membre_id ? 'checked' : ''}>
                            <label class="form-check-label small fw-bold text-primary" for="switchEnfant_${i}">Groupe (E)</label>
                        </div>
                    </div>
                `;
                containerEnfants.appendChild(row);
            }
        } else if (count < currentRows) {
            const rows = containerEnfants.querySelectorAll('.enfant-row');
            for (let i = currentRows - 1; i >= count; i--) {
                rows[i].remove();
            }
        }
    }

    // Petite fonction utilitaire pour sécuriser l'affichage dans le innerHTML du JS
    function escapeHtml(string) {
        if(!string) return '';
        return String(string).replace(/[&<>"']/g, function (s) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[s];
        });
    }

    inputNombreEnfants.addEventListener('input', synchroniserEnfants);
    inputNombreEnfants.addEventListener('change', synchroniserEnfants);
    synchroniserEnfants();
});
</script>

<?php require_once '../includes/footer.php'; ?>