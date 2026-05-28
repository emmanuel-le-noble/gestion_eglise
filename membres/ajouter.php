<?php
// eglise_db/membres/ajouter.php
require_once "../config/database.php";
require_once "../includes/session.php"; 
require_once '../includes/helpers.php';

// Toutes les pages du dossier membres contiendront cette ligne :
securiser_par_module($pdo, 'membres');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Optionnel mais recommandé : Vérifier ici si l'utilisateur est connecté
require_login(); 

$message = "";
$erreur = "";

$nouveauMatricule = genererMatricule($pdo, null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $date_arrivee = !empty($_POST['date_arrivee']) ? $_POST['date_arrivee'] : null;
    $nouveauMatricule = genererMatricule($pdo, $date_arrivee);

    // --- GESTION DE LA PHOTO CORRIGÉE ---
    $photo_nom = "default.png"; 
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['photo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $target_dir = __DIR__ . "/../assets/uploads/membres/";
            
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $photo_nom = "PHOTO_" . $nouveauMatricule . "_" . time() . "." . $ext;
            
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $target_dir . $photo_nom)) {
                $erreur = "Échec du transfert de l'image. Vérifiez les permissions du dossier.";
            }
        } else {
            $erreur = "Format d'image non supporté (JPG, PNG uniquement).";
        }
    }

    // --- RÉCUPÉRATION DES AUTRES DONNÉES ---
    $nom = trim($_POST['nom']);
    $prenoms = trim($_POST['prenoms']);
    $sexe = $_POST['sexe'];
    $date_naissance = !empty($_POST['date_naissance']) ? $_POST['date_naissance'] : null;
    $lieu_naissance = trim($_POST['lieu_naissance']);
    $profession = trim($_POST['profession']);
    $eglise_provenance = trim($_POST['eglise_provenance']);
    
    $baptise = isset($_POST['baptise']) ? 1 : 0;
    $date_bapteme = (!empty($_POST['date_bapteme']) && $baptise) ? $_POST['date_bapteme'] : null;
    $lieu_bapteme = ($baptise) ? trim($_POST['lieu_bapteme']) : null;
    $engagement_moral = isset($_POST['engagement_moral']) ? 1 : 0;
    $groupe_action = $_POST['groupe_action'];
    $qualite = $_POST['qualite'];
    $statut = $_POST['statut'] ?? 'Actif'; 

    $telephone1 = trim($_POST['telephone1']);
    $telephone2 = trim($_POST['telephone2']);
    $email = trim($_POST['email']);
    $quartier = trim($_POST['quartier']);
    $situation_matrimoniale = $_POST['situation_matrimoniale'];
    $date_mariage = !empty($_POST['date_mariage']) ? $_POST['date_mariage'] : null;
    $lieu_mariage = trim($_POST['lieu_mariage']);
    $nom_conjoint = trim($_POST['nom_conjoint']);
    $nombre_enfants = (int)$_POST['nombre_enfants'];
    $commentaire = trim($_POST['commentaire']);
    $utilisateur_id = $_SESSION['user_id'] ?? 1; 

    if (empty($erreur) && !empty($nom) && !empty($prenoms) && !empty($sexe)) {
        try {
            $pdo->beginTransaction();

            // 1. Insertion du parent dans la table membres
            $sql = "INSERT INTO membres (
                matricule, photo, nom, prenoms, sexe, date_naissance, lieu_naissance, profession, 
                eglise_provenance, date_arrivee, baptise, date_bapteme, lieu_bapteme, 
                engagement_moral, groupe_action, qualite, statut_membre, telephone1, telephone2, email, 
                quartier, situation_matrimoniale, date_mariage, lieu_mariage, nom_conjoint, 
                nombre_enfants, commentaire, utilisateur_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nouveauMatricule, $photo_nom, $nom, $prenoms, $sexe, $date_naissance, $lieu_naissance, $profession,
                $eglise_provenance, $date_arrivee, $baptise, $date_bapteme, $lieu_bapteme,
                $engagement_moral, $groupe_action, $qualite, $statut, $telephone1, $telephone2, $email,
                $quartier, $situation_matrimoniale, $date_mariage, $lieu_mariage, $nom_conjoint,
                $nombre_enfants, $commentaire, $utilisateur_id
            ]);

            $membre_id = $pdo->lastInsertId();

            // 2. Traitement des enfants (Processus Familial Unique)
            if (isset($_POST['enfant_nom']) && is_array($_POST['enfant_nom'])) {
                
                $sql_enfant_pivot = "INSERT INTO enfants (membre_id, enfant_membre_id, nom, prenoms, sexe, date_naissance) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_enfant_pivot = $pdo->prepare($sql_enfant_pivot);

                $sql_enfant_membre = "INSERT INTO membres (matricule, photo, nom, prenoms, sexe, date_naissance, qualite, groupe_action, statut_membre, utilisateur_id) VALUES (?, ?, ?, ?, ?, ?, 'Enfant', 'Enfants', 'Actif', ?)";
                $stmt_enfant_membre = $pdo->prepare($sql_enfant_membre);

                $offset = 1; // Sert à décaler les matricules générés simultanément pour éviter les collisions

                foreach ($_POST['enfant_nom'] as $key => $val) {
                    $nom_enf = trim($val);
                    if (!empty($nom_enf)) {
                        $prenoms_enf = trim($_POST['enfant_prenoms'][$key] ?? '');
                        $sexe_enf = $_POST['enfant_sexe'][$key] ?? 'Masculin';
                        $dob_enf = !empty($_POST['enfant_dob'][$key]) ? $_POST['enfant_dob'][$key] : null;
                        
                        $enfant_membre_id = null; // Par défaut, l'enfant n'est pas membre autonome (ex: nourrisson)

                        // Si la case "Lui attribuer un matricule" a été cochée
                        if (isset($_POST['enfant_creer_matricule'][$key]) && $_POST['enfant_creer_matricule'][$key] === '1') {
                            
                            // Génération du matricule séquentiel pour l'enfant avec son décalage
                            $matricule_enfant = genererMatricule($pdo, $date_arrivee, $offset);
                            
                            // Insertion directe de l'enfant en tant que membre à part entière
                            $stmt_enfant_membre->execute([
                                $matricule_enfant,
                                'default.png',
                                $nom_enf,
                                $prenoms_enf,
                                $sexe_enf,
                                $dob_enf,
                                $utilisateur_id
                            ]);
                            
                            // On récupère l'identifiant généré pour le lier au pivot
                            $enfant_membre_id = $pdo->lastInsertId();
                            $offset++;
                        }

                        // Création de la relation dans la table enfants
                        $stmt_enfant_pivot->execute([
                            $membre_id,
                            $enfant_membre_id, // L'ID du membre enfant généré OU null
                            $nom_enf,
                            $prenoms_enf,
                            $sexe_enf,
                            $dob_enf
                        ]);
                    }
                }
            }

            $pdo->commit();
            $message = "La famille a été inscrite avec succès ! Parent : <strong class='text-primary'>$nouveauMatricule</strong>";
            
            $nouveauMatricule = genererMatricule($pdo, null); 
        } catch (PDOException $e) {
            $pdo->rollBack();
            $erreur = "Erreur lors de l'enregistrement : " . $e->getMessage();
        }
    } else {
        if(empty($erreur)) {
            $erreur = "Veuillez remplir tous les champs obligatoires (*).";
        }
    }
}

$page_title = "Nouveau membre"; 
require_once '../includes/header.php'; 
?>

<?php if(!empty($message)): ?>
    <div class="alert alert-success border-0 shadow-sm mb-4"><?= $message ?></div>
<?php endif; ?>
<?php if(!empty($erreur)): ?>
    <div class="alert alert-danger border-0 shadow-sm mb-4"><?= $erreur ?></div>
<?php endif; ?>

<div class="container mt-4">
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-body p-4">
            <form action="ajouter.php" method="POST" enctype="multipart/form-data">
                <div class="row g-4">
                    
                    <div class="col-12 d-flex justify-content-between">
                        <h5 class="text-primary fw-semibold mb-0"><i class="fa-solid fa-id-card me-2"></i>1. État civil & identification</h5>
                        <a href="index.php" class="btn btn-light btn-sm border">Retour</a>
                    </div>
                    <hr class="text-muted opacity-25 mt-2">
                    
                    <div class="col-md-3 text-center border-end">
                        <label class="form-label text-secondary small fw-bold">Photo du membre</label>
                        <div class="mb-2">
                            <img src="../assets/img/default_avatar.png" id="preview" class="img-thumbnail rounded" style="width: 140px; height: 140px; object-fit: cover;">
                        </div>
                        <input type="file" name="photo" id="photo_input" class="form-control form-control-sm" accept="image/png, image/jpeg">
                    </div>

                    <div class="col-md-9">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold">Matricule (Auto)</label>
                                <input type="text" class="form-control bg-light fw-bold text-danger" value="<?= htmlspecialchars($nouveauMatricule ?: 'MEMB-MMAA-XXXX') ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold">Nom <span class="text-danger">*</span></label>
                                <input type="text" name="nom" class="form-control bg-light border-0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold">Prénoms <span class="text-danger">*</span></label>
                                <input type="text" name="prenoms" class="form-control bg-light border-0" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-secondary small fw-bold">Sexe <span class="text-danger">*</span></label>
                                <select name="sexe" class="form-select bg-light border-0" required>
                                    <option value="Masculin">Masculin</option>
                                    <option value="Feminin">Féminin</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-secondary small fw-bold">Date de naissance</label>
                                <input type="date" name="date_naissance" class="form-control bg-light border-0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold">Lieu de naissance</label>
                                <input type="text" name="lieu_naissance" class="form-control bg-light border-0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold">Profession</label>
                                <input type="text" name="profession" class="form-control bg-light border-0">
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mt-5">
                        <h5 class="text-primary fw-semibold mb-0"><i class="fa-solid fa-map-location-dot me-2"></i>2. Adresse & Contact</h5>
                        <hr class="text-muted opacity-25 mt-2">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold">Téléphone 1 <span class="text-danger">*</span></label>
                        <input type="text" name="telephone1" class="form-control bg-light border-0" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold">Téléphone 2</label>
                        <input type="text" name="telephone2" class="form-control bg-light border-0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold">E-mail</label>
                        <input type="email" name="email" class="form-control bg-light border-0">
                    </div>
                    <div class="col-12">
                        <label class="form-label text-secondary small fw-bold">Domicile (Quartier)</label>
                        <input type="text" name="quartier" class="form-control bg-light border-0">
                    </div>

                    <div class="col-12 mt-5">
                        <h5 class="text-primary fw-semibold mb-0"><i class="fa-solid fa-heart me-2"></i>3. Situation de famille</h5>
                        <hr class="text-muted opacity-25 mt-2">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label text-secondary small fw-bold">Statut Matrimonial</label>
                        <select name="situation_matrimoniale" id="statut_matri" class="form-select bg-light border-0">
                            <option value="Célibataire">Célibataire</option>
                            <option value="Marié(e)">Marié(e)</option>
                            <option value="Veuf(ve)">Veuf(ve)</option>
                            <option value="Divorcé(e)">Divorcé(e)</option>
                        </select>
                    </div>

                    <div class="col-md-9">
                        <div class="row g-3" id="section_mariage" style="display: none;">
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold">Date mariage</label>
                                <input type="date" name="date_mariage" class="form-control bg-light border-0">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label text-secondary small fw-bold">Lieu du mariage</label>
                                <input type="text" name="lieu_mariage" class="form-control bg-light border-0">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-secondary small fw-bold">Nom et prénoms conjoint(e)</label>
                        <input type="text" name="nom_conjoint" class="form-control bg-light border-0">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-secondary small fw-bold">Nombre d'enfants</label>
                        <input type="number" name="nombre_enfants" id="nombre_enfants" class="form-control bg-light border-0" value="0" min="0">
                    </div>

                    <div class="col-12 mt-4">
                        <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded">
                            <h6 class="m-0 fw-bold text-dark"><i class="fa-solid fa-baby me-2"></i>Détails des enfants</h6>
                            <small class="text-muted">Généré automatiquement selon le nombre d'enfants</small>
                        </div>
                        <div id="enfants_container" class="mt-3"></div>
                    </div>

                    <div class="col-12 mt-5">
                        <h5 class="text-primary fw-semibold mb-0"><i class="fa-solid fa-church me-2"></i>4. Situation ecclésiastique</h5>
                        <hr class="text-muted opacity-25 mt-2">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold">Église de provenance</label>
                        <input type="text" name="eglise_provenance" class="form-control bg-light border-0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold">Date d'arrivée à l'église</label>
                        <input type="date" name="date_arrivee" class="form-control bg-light border-0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold">Groupe d'action</label>
                        <select name="groupe_action" class="form-select bg-light border-0">
                            <option value="Hommes">Hommes (H)</option>
                            <option value="Femmes">Femmes (F)</option>
                            <option value="Jeunesses">Jeunesses (J)</option>
                            <option value="Enfants">Enfants (E)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold">Qualité</label>
                        <select name="qualite" class="form-select bg-light border-0">
                            <option value="Membre">Membre</option>
                            <option value="Ami">Ami</option>
                            <option value="Enfant">Enfant</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label text-secondary small fw-bold">Statut</label>
                        <select name="statut" class="form-select bg-light border-0">
                            <option value="Actif">Actif</option>
                            <option value="Inactif">Inactif</option>
                            <option value="Abandon">Abandon</option>
                            <option value="Départ">Départ</option>
                        </select>
                    </div>

                    <div class="col-12 my-3">
                        <div class="form-check form-switch d-inline-block me-5">
                            <input class="form-check-input" type="checkbox" name="baptise" id="baptiseCheck" value="1">
                            <label class="form-check-label fw-semibold" for="baptiseCheck">Baptisé par immersion</label>
                        </div>
                        <div class="form-check form-switch d-inline-block">
                            <input class="form-check-input" type="checkbox" name="engagement_moral" id="engagement" value="1">
                            <label class="form-check-label fw-semibold" for="engagement">Engagement moral signed</label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-secondary small fw-bold">Date du baptême</label>
                        <input type="date" name="date_bapteme" class="form-control bg-light border-0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-secondary small fw-bold">Lieu du baptême</label>
                        <input type="text" name="lieu_bapteme" class="form-control bg-light border-0">
                    </div>

                    <div class="col-12 mt-4">
                        <label class="form-label text-secondary small fw-bold">Commentaire / Observations</label>
                        <textarea name="commentaire" class="form-control bg-light border-0" rows="3"></textarea>
                    </div>

                    <div class="col-12 text-end mt-5 border-top pt-3">
                        <button type="reset" class="btn btn-light px-4 me-2">Vider</button>
                        <button type="submit" class="btn btn-primary px-5 shadow-sm fw-medium">Enregistrer le membre</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Gestion du Mariage
    const selectStatut = document.getElementById('statut_matri');
    const sectionMariage = document.getElementById('section_mariage');

    function toggleMariage() {
        sectionMariage.style.display = (selectStatut.value !== 'Célibataire') ? 'flex' : 'none';
    }
    selectStatut.addEventListener('change', toggleMariage);
    toggleMariage();

    // 2. Prévisualisation Photo
    const photoInput = document.getElementById('photo_input');
    const preview = document.getElementById('preview');
    photoInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            preview.src = URL.createObjectURL(file);
        }
    });

    // 3. SYNCHRONISATION AUTOMATIQUE ENFANTS AVEC ENRÔLEMENT AUTONOME
    const inputNombreEnfants = document.getElementById('nombre_enfants');
    const containerEnfants = document.getElementById('enfants_container');

    inputNombreEnfants.addEventListener('input', function() {
        const count = parseInt(this.value) || 0;
        const currentRows = containerEnfants.querySelectorAll('.enfant-row').length;

        if (count > currentRows) {
            for (let i = currentRows; i < count; i++) {
                const row = document.createElement('div');
                row.className = 'card bg-white p-3 mb-3 border border-1 shadow-sm enfant-row';
                row.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-2 bg-light p-2 rounded">
                        <span class="small fw-bold text-secondary">Enfant #${i+1}</span>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" name="enfant_creer_matricule[${i}]" value="1" id="creer_mat_${i}">
                            <label class="form-check-label small fw-bold text-danger" for="creer_mat_${i}">Lui attribuer un Matricule (Groupe d'action Enfants)</label>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <input type="text" name="enfant_nom[]" class="form-control form-control-sm bg-light border-0" placeholder="Nom" required>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="enfant_prenoms[]" class="form-control form-control-sm bg-light border-0" placeholder="Prénoms" required>
                        </div>
                        <div class="col-md-2">
                            <select name="enfant_sexe[]" class="form-select form-select-sm bg-light border-0">
                                <option value="Masculin">Masculin</option>
                                <option value="Feminin">Féminin</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="date" name="enfant_dob[]" class="form-control form-control-sm bg-light border-0">
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
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>