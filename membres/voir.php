<?php
// eglise_db/membres/voir.php
require_once "../config/database.php";
require_once "../includes/session.php";
require_once "../includes/helpers.php"; // Contient vos helpers de sécurité et enregistrer_log()

// Toutes les pages du dossier membres contiendront cette ligne :
securiser_par_module($pdo, 'membres');

// Initialisation de la session si ce n'est pas déjà fait dans session.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Récupération sécurisée et casting de l'identifiant
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!$id || $id <= 0) { 
    header("Location: index.php"); 
    exit; 
}

// Récupération des messages flash depuis la session
$message = isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : "";
$erreur = isset($_SESSION['flash_erreur']) ? $_SESSION['flash_erreur'] : "";

// Nettoyage des messages flash pour éviter qu'ils ne se réaffichent au prochain rafraîchissement
unset($_SESSION['flash_message'], $_SESSION['flash_erreur']);

// Définition du dossier d'upload physique
$target_dir = realpath(__DIR__ . "/../assets/uploads/membres") . "/";

// =========================================================================
// INTERCEPTIONS DES ACTIONS & INSCRIPTION DANS L'HISTORIQUE
// =========================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // SÉCURISATION CSRF GLOBALE POUR TOUS LES POSTS DE CETTE PAGE
    $token_recu = $_POST['csrf_token'] ?? '';
    if (function_exists('verifier_token_csrf') && !verifier_token_csrf($token_recu)) {
        $_SESSION['flash_error'] = "Action non autorisée (Échec CSRF).";
        header("Location: voir.php?id=" . $id);
        exit();
    }
    
    $id_operateur = $_SESSION['user_id'] ?? null;

    // CAS 1 : ENREGISTRER UN MARIAGE
    if (isset($_POST['action_mariage'])) {
        $statut_matrimonial = "Marié(e)";
        $nom_conjoint = trim($_POST['nom_conjoint']);
        $date_mariage = !empty($_POST['date_mariage']) ? $_POST['date_mariage'] : null;
        $lieu_mariage = trim($_POST['lieu_mariage']);

        if (!empty($nom_conjoint)) {
            try {
                $pdo->beginTransaction();

                // 1. Mise à jour de la fiche membre
                $stmt = $pdo->prepare("UPDATE membres SET situation_matrimoniale = ?, nom_conjoint = ?, date_mariage = ?, lieu_mariage = ? WHERE id = ?");
                $stmt->execute([$statut_matrimonial, $nom_conjoint, $date_mariage, $lieu_mariage, $id]);

                // 2. Journalisation dans la table historique_membre
                $date_effet = $date_mariage ?: date('Y-m-d');
                $motif = "Célébration de mariage avec " . $nom_conjoint . " à " . ($lieu_mariage ?: "l'Église") . ".";
                $stmt_hist = $pdo->prepare("INSERT INTO historique_membre (membre_id, type_evenement, date_evenement, description, utilisateur_id ) VALUES (?, 'Mariage', ?, ?, ?)");
                $stmt_hist->execute([$id, $date_effet, $motif, $id_operateur]);

                // 3. Enregistrement dans les logs système globaux
                if (function_exists('enregistrer_log')) {
                    enregistrer_log($pdo, "Modification", "Mariage enregistré pour le membre ID #$id avec $nom_conjoint.", $id_operateur);
                }

                $pdo->commit();
                
                $_SESSION['flash_message'] = "Le mariage a été acté et enregistré dans l'historique du membre !";
                header("Location: voir.php?id=" . $id);
                exit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                $_SESSION['flash_erreur'] = "Erreur lors de l'enregistrement du mariage : " . $e->getMessage();
                header("Location: voir.php?id=" . $id);
                exit();
            }
        } else {
            $erreur = "Le nom du conjoint est obligatoire pour acter un mariage.";
        }
    }

    // CAS 2 : ENREGISTRER UNE NAISSANCE
    if (isset($_POST['action_declaration_naissance'])) {
        $bebe_nom = trim($_POST['bebe_nom']);
        $bebe_prenoms = trim($_POST['bebe_prenoms']);
        $bebe_sexe = $_POST['bebe_sexe'];
        $bebe_dob = !empty($_POST['bebe_dob']) ? $_POST['bebe_dob'] : null;

        if (!empty($bebe_nom)) {
            try {
                $pdo->beginTransaction();

                // 1. Insertion dans la table enfants
                $stmt_bebe = $pdo->prepare("INSERT INTO enfants (membre_id, nom, prenoms, sexe, date_naissance) VALUES (?, ?, ?, ?, ?)");
                $stmt_bebe->execute([$id, $bebe_nom, $bebe_prenoms, $bebe_sexe, $bebe_dob]);

                // 2. Incrémentation du compteur de la famille
                $stmt_inc = $pdo->prepare("UPDATE membres SET nombre_enfants = nombre_enfants + 1 WHERE id = ?");
                $stmt_inc->execute([$id]);

                // 3. Journalisation dans l'historique du parent
                $date_effet = $bebe_dob ?: date('Y-m-d');
                $genre_bebe = ($bebe_sexe === 'Masculin') ? "un fils" : "une fille";
                $motif = "Naissance d'" . $genre_bebe . " nommé(e) " . $bebe_nom . " " . $bebe_prenoms . ".";
                
                $stmt_hist = $pdo->prepare("INSERT INTO historique_membre (membre_id, type_evenement, date_evenement, description, utilisateur_id ) VALUES (?, 'Naissance', ?, ?, ?)");
                $stmt_hist->execute([$id, $date_effet, $motif, $id_operateur]);

                // 4. Enregistrement dans les logs système globaux
                if (function_exists('enregistrer_log')) {
                    enregistrer_log($pdo, "Modification", "Déclaration de naissance liée au membre ID #$id (Enfant: $bebe_nom $bebe_prenoms).", $id_operateur);
                }

                $pdo->commit();
                
                $_SESSION['flash_message'] = "La naissance a été déclarée et ajoutée à l'historique familial !";
                header("Location: voir.php?id=" . $id);
                exit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                $_SESSION['flash_erreur'] = "Erreur lors de la déclaration de naissance : " . $e->getMessage();
                header("Location: voir.php?id=" . $id);
                exit();
            }
        } else {
            $erreur = "Le nom de l'enfant est requis.";
        }
    }

    // CAS 3 : ACTER UN DÉPART / CHANGEMENT DE STATUT ÉGLISE
    if (isset($_POST['action_depart'])) {
        $nouveau_statut = $_POST['statut_membre'];
        $date_mouvement = !empty($_POST['date_mouvement']) ? $_POST['date_mouvement'] : date('Y-m-d');
        $motif_depart = trim($_POST['motif_depart']);

        if (!empty($nouveau_statut)) {
            try {
                $pdo->beginTransaction();

                // 1. Mise à jour du statut dans la table membres
                $stmt = $pdo->prepare("UPDATE membres SET statut_membre = ? WHERE id = ?");
                $stmt->execute([$nouveau_statut, $id]);

                // 2. Inscription propre dans la table historique_membre
                $description_mvt = "Changement de statut vers [" . $nouveau_statut . "]. Motif : " . $motif_depart;
                $stmt_hist = $pdo->prepare("INSERT INTO historique_membre (membre_id, type_evenement, date_evenement, description, utilisateur_id) VALUES (?, 'Mouvement', ?, ?, ?)");
                $stmt_hist->execute([$id, $date_mouvement, $description_mvt, $id_operateur]);

                // 3. Enregistrement dans les logs système globaux
                if (function_exists('enregistrer_log')) {
                    enregistrer_log($pdo, "Modification", "Changement de statut ecclésiastique pour le membre ID #$id vers '$nouveau_statut'.", $id_operateur);
                }

                $pdo->commit();
                
                $_SESSION['flash_message'] = "Le changement de situation (Mouvement) a bien été enregistré dans l'historique.";
                header("Location: voir.php?id=" . $id);
                exit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                $_SESSION['flash_erreur'] = "Erreur lors de l'application du mouvement : " . $e->getMessage();
                header("Location: voir.php?id=" . $id);
                exit();
            }
        }
    }
}

// =========================================================================
// RÉCUPÉRATION DES DONNÉES DE LA FICHE CONSOLIDÉE
// =========================================================================

$stmt = $pdo->prepare("SELECT * FROM membres WHERE id = ?");
$stmt->execute([$id]);
$m = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$m) { 
    echo "<div class='container mt-5'><div class='alert alert-danger shadow-sm border-0'>Membre introuvable ou supprimé.</div></div>"; 
    require_once '../includes/footer.php';
    exit; 
}

$stmtEnfants = $pdo->prepare("SELECT * FROM enfants WHERE membre_id = ? ORDER BY date_naissance DESC");
$stmtEnfants->execute([$id]);
$enfants = $stmtEnfants->fetchAll(PDO::FETCH_ASSOC);

$stmtHist = $pdo->prepare("SELECT * FROM historique_membre WHERE membre_id = ? ORDER BY date_evenement DESC, id DESC");
$stmtHist->execute([$id]);
$historique = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

$stmtSolde = $pdo->prepare("SELECT 
    COALESCE(SUM(CASE WHEN type_operation IN ('DEPOT', 'REMBOURSEMENT') THEN montant ELSE 0 END), 0) - 
    COALESCE(SUM(CASE WHEN type_operation IN ('RETRAIT', 'COMMISSION') THEN montant ELSE 0 END), 0) as solde
    FROM mutuelle_operations o 
    JOIN mutuelle_comptes c ON o.compte_id = c.id 
    WHERE c.membre_id = ?");
$stmtSolde->execute([$id]);
$solde_mutuelle = $stmtSolde->fetchColumn();

$page_title = "Détails du membre"; 
require_once '../includes/header.php'; 
?>

<div class="container mt-4 mb-5">
    
    <?php if(!empty($message)): ?>
        <div class="alert alert-success border-0 shadow-sm mb-4"><i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if(!empty($erreur)): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4"><i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <h3 class="fw-bold mb-0 text-dark">
            <i class="fa fa-id-card text-primary me-2"></i> Fiche membre
        </h3>
        <div class="d-flex flex-wrap gap-2">
            <a href="index.php" class="btn btn-outline-secondary"><i class="fa fa-arrow-left me-1"></i> Retour</a>
            
            <div class="dropdown">
                <button class="btn btn-success fw-bold dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Actions Situation
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                    <li><a class="dropdown-item py-2 small fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#modalMariage"><i class="fa-solid fa-heart text-danger me-2"></i> Déclarer un Mariage</a></li>
                    <li><a class="dropdown-item py-2 small fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#modalNaissance"><i class="fa-solid fa-baby text-primary me-2"></i> Déclarer une Naissance</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 small fw-bold text-danger" href="#" data-bs-toggle="modal" data-bs-target="#modalDepart"><i class="fa-solid fa-door-open me-2"></i> Signaler un Départ / Mutation</a></li>
                </ul>
            </div>

            <a href="modifier.php?id=<?= (int)$m['id'] ?>" class="btn btn-warning fw-bold"><i class="fa fa-edit me-1"></i> Modifier</a>
            <a href="fiche.php?id=<?= (int)$m['id'] ?>" class="btn btn-outline-dark"><i class="fa fa-print me-1"></i> Imprimer</a>
            
            <form id="delete-form-<?= (int)$m['id'] ?>" action="supprimer.php" method="POST" style="display:none;">
                <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= function_exists('generer_token_csrf') ? generer_token_csrf() : '' ?>">
            </form>
            <button onclick="confirmDeletion(<?= (int)$m['id'] ?>)" class="btn btn-danger fw-bold"><i class="fa fa-trash me-1"></i> Supprimer</button>
        </div>
    </div>

    <?php if ($m['statut_membre'] !== 'Actif'): ?>
        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center p-3 mb-4">
            <i class="fa-solid fa-circle-exclamation fa-2x me-3 text-warning"></i>
            <div>
                <h6 class="mb-1 fw-bold text-dark">Mouvement Ecclésiastique : Ce membre est actuellement enregistré comme [<?= htmlspecialchars($m['statut_membre']) ?>]</h6>
                <p class="mb-0 small text-secondary">Consultez l'historique complet au bas de la fiche pour voir les dates d'effet et les motifs.</p>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm text-center p-4 mb-4">
                <div class="mb-3 position-relative">
                    <?php if(!empty($m['photo']) && file_exists($target_dir . $m['photo'])): ?>
                        <img src="../assets/uploads/membres/<?= htmlspecialchars($m['photo']) ?>" class="rounded-circle shadow-sm border" style="width: 160px; height: 160px; object-fit: cover; border: 5px solid #fff !important;">
                    <?php else: ?>
                        <div class="mx-auto bg-light rounded-circle d-flex align-items-center justify-content-center border shadow-sm" style="width: 160px; height: 160px;">
                            <i class="fa fa-user fa-4x text-secondary opacity-20"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <h4 class="fw-bold mb-1"><?= htmlspecialchars(($m['nom'] ?? '') . ' ' . ($m['prenoms'] ?? '')) ?></h4>
                <p class="text-muted small mb-3"><?= htmlspecialchars($m['matricule'] ?? 'Pas de matricule') ?></p>
                
                <div class="d-flex justify-content-center gap-2 mb-3">
                    <span class="badge bg-primary px-3 py-2 text-uppercase"><?= htmlspecialchars($m['qualite'] ?? 'Membre') ?></span>
                    <span class="badge bg-<?= ($m['statut_membre'] == 'Actif') ? 'success' : 'danger' ?> px-3 py-2 text-uppercase"><?= htmlspecialchars($m['statut_membre'] ?? 'Inactif') ?></span>
                </div>
                
                <hr class="my-3 opacity-10">
                
                <div class="text-start">
                    <div class="mb-3">
                        <small class="text-muted d-block mb-1">Téléphone principal</small>
                        <span class="fw-bold text-dark"><i class="fa fa-phone me-2 text-primary"></i><?= !empty($m['telephone1']) ? htmlspecialchars($m['telephone1']) : 'Non renseigné' ?></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block mb-1">Email</small>
                        <span class="fw-bold text-dark"><i class="fa fa-envelope me-2 text-primary"></i><?= !empty($m['email']) ? htmlspecialchars($m['email']) : 'Non renseigné' ?></span>
                    </div>
                    <div class="mb-0">
                        <small class="text-muted d-block mb-1">Quartier / Domicile</small>
                        <span class="fw-bold text-dark"><i class="fa fa-map-marker-alt me-2 text-primary"></i><?= !empty($m['quartier']) ? htmlspecialchars($m['quartier']) : 'Non renseigné' ?></span>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white pt-3 border-0">
                    <h6 class="mb-0 fw-bold text-primary"><i class="fa fa-info-circle me-2"></i> Informations personnelles</h6>
                </div>
                <div class="card-body px-3">
                    <div class="row g-3">
                        <div class="col-sm-12">
                            <label class="text-muted small d-block">Date de naissance</label>
                            <span class="fw-bold text-dark"><?= (!empty($m['date_naissance']) && $m['date_naissance'] !== '0000-00-00') ? htmlspecialchars(date('d/m/Y', strtotime($m['date_naissance']))) : '-' ?></span>
                        </div>
                        <div class="col-sm-12">
                            <label class="text-muted small d-block">Lieu de naissance</label>
                            <span class="fw-bold text-dark"><?= htmlspecialchars($m['lieu_naissance'] ?: '-') ?></span>
                        </div>
                        <div class="col-sm-12">
                            <label class="text-muted small d-block">Sexe</label>
                            <span class="fw-bold text-dark"><?= htmlspecialchars($m['sexe'] ?? '-') ?></span>
                        </div>
                        <div class="col-sm-12">
                            <label class="text-muted small d-block">Profession</label>
                            <span class="fw-bold text-dark"><?= htmlspecialchars($m['profession'] ?: '-') ?></span>
                        </div>
                        <div class="col-sm-12">
                            <label class="text-muted small d-block">Téléphone secondaire</label>
                            <span class="fw-bold text-dark"><?= !empty($m['telephone2']) ? htmlspecialchars($m['telephone2']) : '-' ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold text-primary"><i class="fa fa-church me-2"></i> Vie spirituelle & engagement</h6>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="text-muted small d-block">Église de provenance</label>
                            <span class="fw-bold text-dark"><?= htmlspecialchars($m['eglise_provenance'] ?: '-') ?></span>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small d-block">Date d'arrivée</label>
                            <span class="fw-bold text-dark"><?= (!empty($m['date_arrivee']) && $m['date_arrivee'] !== '0000-00-00') ? htmlspecialchars(date('d/m/Y', strtotime($m['date_arrivee']))) : '-' ?></span>
                        </div>
                        <div class="col-sm-4">
                            <label class="text-muted small d-block mb-1">Baptisé(e)</label>
                            <span class="badge bg-<?= !empty($m['baptise']) ? 'info' : 'secondary' ?> px-2 py-1">
                                <?= !empty($m['baptise']) ? 'OUI' : 'NON' ?>
                            </span>
                        </div>
                        
                        <?php if(!empty($m['baptise'])): ?>
                        <div class="col-sm-4">
                            <label class="text-muted small d-block">Date baptême</label>
                            <span class="fw-bold text-dark"><?= (!empty($m['date_bapteme']) && $m['date_bapteme'] !== '0000-00-00') ? htmlspecialchars(date('d/m/Y', strtotime($m['date_bapteme']))) : '-' ?></span>
                        </div>
                        <div class="col-sm-4">
                            <label class="text-muted small d-block">Lieu baptême</label>
                            <span class="fw-bold text-dark"><?= htmlspecialchars($m['lieu_bapteme'] ?: '-') ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="col-sm-6">
                            <label class="text-muted small d-block">Groupe d'action actuel</label>
                            <span class="fw-bold text-primary"><i class="fa-solid fa-users-gear me-1"></i><?= htmlspecialchars($m['groupe_action'] ?: 'Aucun') ?></span>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small d-block">Engagement moral</label>
                            <span class="fw-bold text-dark"><?= !empty($m['engagement_moral']) ? 'Signé' : 'Non signé' ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold text-primary"><i class="fa fa-users me-2"></i> Situation familiale</h6>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-3 mb-4">
                        <div class="col-sm-4">
                            <label class="text-muted small d-block">Situation Matrimoniale</label>
                            <span class="fw-bold text-dark"><i class="fa-solid fa-heart text-danger me-1"></i><?= htmlspecialchars($m['situation_matrimoniale'] ?? 'Célibataire') ?></span>
                        </div>
                        <div class="col-sm-8">
                            <label class="text-muted small d-block">Conjoint(e)</label>
                            <span class="fw-bold text-dark"><?= htmlspecialchars($m['nom_conjoint'] ?: 'Aucun conjoint lié') ?></span>
                        </div>
                        
                        <?php if(!empty($m['date_mariage']) && $m['date_mariage'] !== '0000-00-00'): ?>
                        <div class="col-sm-4">
                            <label class="text-muted small d-block">Date du mariage</label>
                            <span class="fw-bold text-dark"><i class="fa-solid fa-calendar me-1 text-muted"></i><?= htmlspecialchars(date('d/m/Y', strtotime($m['date_mariage']))) ?></span>
                        </div>
                        <div class="col-sm-8">
                            <label class="text-muted small d-block">Lieu du mariage</label>
                            <span class="fw-bold text-dark"><i class="fa-solid fa-location-dot me-1 text-muted"></i><?= htmlspecialchars($m['lieu_mariage'] ?: '-') ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="border-top pt-3">
                        <label class="text-secondary small fw-bold mb-2"><i class="fa-solid fa-baby-carriage text-primary me-1"></i> Enfants à charge (<?= count($enfants) ?>)</label>
                        <?php if(!empty($enfants)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover border align-middle mb-0" style="font-size: 0.85rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nom & Prénoms de l'enfant</th>
                                            <th class="text-center">Sexe</th>
                                            <th>Date de naissance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($enfants as $enf): ?>
                                            <tr>
                                                <td class="fw-bold text-dark"><?= htmlspecialchars($enf['nom'] . ' ' . $enf['prenoms']) ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-light text-dark border">
                                                        <?= $enf['sexe'] === 'Masculin' ? '<i class="fa-solid fa-mars text-primary me-1"></i> Garçon' : '<i class="fa-solid fa-venus text-danger me-1"></i> Fille' ?>
                                                    </span>
                                                </td>
                                                <td><i class="fa-solid fa-cake-candles text-muted me-1"></i> <?= htmlspecialchars(date('d/m/Y', strtotime($enf['date_naissance']))) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted small fst-italic mb-0 bg-light p-2 rounded text-center">Aucun enfant déclaré pour l'instant.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-clock-rotate-left text-secondary me-2"></i> Historique des changements de situation</h6>
                </div>
                <div class="card-body pt-0">
                    <?php if (!empty($historique)): ?>
                        <div class="position-relative ps-2" style="border-left: 2px solid #e9ecef;">
                            <?php foreach ($historique as $hist): ?>
                                <?php 
                                    $icon = "fa-circle-dot"; $color = "secondary";
                                    if ($hist['type_evenement'] === 'Mariage') { $icon = "fa-heart text-danger"; $color = "danger"; }
                                    elseif ($hist['type_evenement'] === 'Naissance') { $icon = "fa-baby text-primary"; $color = "primary"; }
                                    elseif ($hist['type_evenement'] === 'Mouvement') { $icon = "fa-person-walking-arrow-right text-warning"; $color = "warning"; }
                                ?>
                                <div class="mb-3 position-relative">
                                    <div class="position-absolute bg-white d-flex align-items-center justify-content-center" style="left: -19px; top: 2px; width: 14px; height: 14px;">
                                        <i class="fa-solid <?= $icon ?>" style="font-size: 0.75rem;"></i>
                                    </div>
                                    <div class="ms-3 bg-light p-3 rounded-3 border-0">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="badge bg-<?= $color ?> text-white px-2 py-1 small" style="font-size:0.75rem;"><?= htmlspecialchars($hist['type_evenement']) ?></span>
                                            <small class="text-muted fw-bold"><i class="fa-regular fa-calendar me-1"></i><?= htmlspecialchars(date('d/m/Y', strtotime($hist['date_evenement']))) ?></small>
                                        </div>
                                        <p class="mb-0 small text-dark"><?= htmlspecialchars($hist['description']) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small fst-italic mb-0 bg-light p-2 rounded text-center">Aucun événement n'est encore répertorié dans l'historique de ce membre.</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($m['commentaire'])): ?>
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body p-3">
                    <small class="text-muted d-block fw-bold mb-1">Commentaires libres historiques :</small>
                    <p class="mb-0 small text-secondary" style="white-space: pre-line; font-family: monospace; font-size: 0.8rem;"><?= htmlspecialchars($m['commentaire']) ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="modalMariage" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-heart me-2"></i>Matérialiser un Mariage</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="voir.php?id=<?= (int)$id ?>" method="POST">
                <input type="hidden" name="action_mariage" value="1">
                <input type="hidden" name="csrf_token" value="<?= function_exists('generer_token_csrf') ? generer_token_csrf() : '' ?>">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Nom & Prénoms complets du Conjoint(e) <span class="text-danger">*</span></label>
                        <input type="text" name="nom_conjoint" class="form-control" placeholder="Ex: KOFFI Amélie" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Date du mariage célébré</label>
                        <input type="date" name="date_mariage" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Lieu / Paroisse de célébration</label>
                        <input type="text" name="lieu_mariage" class="form-control" placeholder="Ex: Paroisse Grand-Amour, Lomé">
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary btn-sm rounded-2" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger btn-sm fw-bold rounded-2 shadow-sm">Unir et Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNaissance" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-baby me-2"></i>Déclarer une nouvelle Naissance</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="voir.php?id=<?= (int)$id ?>" method="POST">
                <input type="hidden" name="action_declaration_naissance" value="1">
                <input type="hidden" name="csrf_token" value="<?= function_exists('generer_token_csrf') ? generer_token_csrf() : '' ?>">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Nom de famille de l'enfant <span class="text-danger">*</span></label>
                        <input type="text" name="bebe_nom" class="form-control text-uppercase" value="<?= htmlspecialchars($m['nom'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Prénoms du nouveau-né <span class="text-danger">*</span></label>
                        <input type="text" name="bebe_prenoms" class="form-control" placeholder="Ex: Emmanuel" required>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">Sexe</label>
                            <select name="bebe_sexe" class="form-select">
                                <option value="Masculin">Masculin (Garçon)</option>
                                <option value="Feminin">Féminin (Fille)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">Date de naissance</label>
                            <input type="date" name="bebe_dob" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary btn-sm rounded-2" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success btn-sm fw-bold rounded-2 shadow-sm">Ajouter au livret familial</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDepart" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-door-open me-2"></i>Signaler un Mouvement / Mutation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="voir.php?id=<?= (int)$id ?>" method="POST">
                <input type="hidden" name="action_depart" value="1">
                <input type="hidden" name="csrf_token" value="<?= function_exists('generer_token_csrf') ? generer_token_csrf() : '' ?>">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Nouveau Statut de Situation <span class="text-danger">*</span></label>
                        <select name="statut_membre" class="form-select" required>
                            <option value="Muté">Muté / Transféré</option>
                            <option value="Suspendu">Suspendu</option>
                            <option value="Excommunié">Excommunié</option>
                            <option value="Décédé">Décédé</option>
                            <option value="Inactif">Inactif / Autre motif</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Date d'effet du mouvement</label>
                        <input type="date" name="date_mouvement" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Motif explicatif précis <span class="text-danger">*</span></label>
                        <textarea name="motif_depart" class="form-control" rows="3" placeholder="Ex: Changement de province professionnelle ou démission volontaire..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary btn-sm rounded-2" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning btn-sm fw-bold text-dark rounded-2 shadow-sm">Acter le Mouvement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDeletion(id) {
    if (confirm("Êtes-vous certain de vouloir supprimer définitivement ce membre ? Cette action supprimera également ses liaisons enfants de manière irréversible.")) {
        document.getElementById('delete-form-' + id).submit();
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>