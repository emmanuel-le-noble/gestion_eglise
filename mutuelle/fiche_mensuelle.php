<?php
// eglise_db/mutuelle/situation_mensuelle.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'mutuelle');

// Récupération et assainissement des paramètres (Membre, Mois et Année)
$compte_id = isset($_GET['compte_id']) ? (int)$_GET['compte_id'] : null;
$mois = isset($_GET['mois']) ? (int)$_GET['mois'] : (int)date('m');
$annee = isset($_GET['annee']) ? (int)$_GET['annee'] : (int)date('Y');

$membre = null;
$statsMois = [
    'total_depot' => 0,
    'total_retrait' => 0,
    'total_frais_tenue' => 0,
    'total_rembourse' => 0,
    'total_prete' => 0
];
$soldeGlobal = 0;
$resteARembourserGlobal = 0;

$mois_fr = [
    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
    7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
];

if ($compte_id) {
    // 1. Informations du membre et du compte
    $stmt = $pdo->prepare("SELECT m.nom, m.prenoms, m.matricule, c.date_adhesion, c.id as cid 
                           FROM mutuelle_comptes c 
                           JOIN membres m ON c.membre_id = m.id 
                           WHERE c.id = ?");
    $stmt->execute([$compte_id]);
    $resMembre = $stmt->fetch();
    if ($resMembre) {
        $membre = $resMembre;
        
        // Intégration du log de consultation d'audit
        if (function_exists('enregistrer_log')) {
            $nom_mois = $mois_fr[$mois] ?? $mois;
            enregistrer_log(
                $pdo, 
                'Consultation Situation Mensuelle', 
                "Consultation de la fiche financière du membre {$membre['matricule']} pour la période : {$nom_mois} {$annee}."
            );
        }
    }

    // 2. Calcul des totaux du mois (Utilisation de COALESCE au lieu de IFNULL)
    $sqlStats = "SELECT 
        COALESCE(SUM(CASE WHEN type_operation = 'DEPOT' THEN montant ELSE 0 END), 0) as total_depot,
        COALESCE(SUM(CASE WHEN type_operation = 'RETRAIT' THEN montant ELSE 0 END), 0) as total_retrait,
        COALESCE(SUM(CASE WHEN type_operation = 'FRAIS_TENUE' THEN montant ELSE 0 END), 0) as total_frais_tenue,
        COALESCE(SUM(CASE WHEN type_operation = 'REMBOURSEMENT' THEN montant ELSE 0 END), 0) as total_rembourse,
        COALESCE(SUM(CASE WHEN type_operation = 'PRET' THEN montant ELSE 0 END), 0) as total_prete
        FROM mutuelle_operations 
        WHERE compte_id = ? AND MONTH(date_op) = ? AND YEAR(date_op) = ?";
    $stmtStats = $pdo->prepare($sqlStats);
    $stmtStats->execute([$compte_id, $mois, $annee]);
    $resStats = $stmtStats->fetch();
    
    if ($resStats) {
        $statsMois = [
            'total_depot' => floatval($resStats['total_depot']),
            'total_retrait' => floatval($resStats['total_retrait']),
            'total_frais_tenue' => floatval($resStats['total_frais_tenue']),
            'total_rembourse' => floatval($resStats['total_rembourse']),
            'total_prete' => floatval($resStats['total_prete'])
        ];
    }

    // 3. Calcul du Solde Épargne Global & Reste à rembourser historique (Prêts vs Remboursements)
    $sqlGlobal = "SELECT 
        COALESCE(SUM(CASE WHEN type_operation = 'DEPOT' THEN montant ELSE 0 END), 0) - 
        COALESCE(SUM(CASE WHEN type_operation IN ('RETRAIT', 'FRAIS_TENUE') THEN montant ELSE 0 END), 0) as solde_total,
        
        COALESCE(SUM(CASE WHEN type_operation = 'PRET' THEN montant ELSE 0 END), 0) - 
        COALESCE(SUM(CASE WHEN type_operation = 'REMBOURSEMENT' THEN montant ELSE 0 END), 0) as reste_a_rembourser
        FROM mutuelle_operations WHERE compte_id = ?";
    
    $stmtGlobal = $pdo->prepare($sqlGlobal);
    $stmtGlobal->execute([$compte_id]);
    $resGlobal = $stmtGlobal->fetch();
    
    if ($resGlobal) {
        $soldeGlobal = floatval($resGlobal['solde_total']);
        $resteARembourserGlobal = floatval($resGlobal['reste_a_rembourser']);
        if ($resteARembourserGlobal < 0) { 
            $resteARembourserGlobal = 0; 
        }
    }
}

// Liste des comptes pour le sélecteur
$comptes = $pdo->query("SELECT c.id, m.matricule, m.nom, m.prenoms FROM mutuelle_comptes c JOIN membres m ON c.membre_id = m.id ORDER BY m.nom ASC")->fetchAll();

$page_title = "Situation financière mensuelle"; 
require_once '../includes/header.php'; 
?>

<div class="container mt-4 mb-5">
    <div class="card border-0 shadow-sm mb-4 no-print">
        <div class="card-body p-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="small fw-bold mb-1">Membre / Adhérent</label>
                    <select name="compte_id" class="form-select select2" required>
                        <option value="">-- Choisir un membre --</option>
                        <?php foreach($comptes as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $compte_id == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['matricule'] . ' - ' . $c['nom'] . ' ' . $c['prenoms']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold mb-1">Mois</label>
                    <select name="mois" class="form-select">
                        <?php for($i=1; $i<=12; $i++): ?>
                            <option value="<?= $i ?>" <?= $mois == $i ? 'selected' : '' ?>><?= $mois_fr[$i] ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold mb-1">Année</label>
                    <select name="annee" class="form-select">
                        <?php 
                        $annee_courante = (int)date('Y');
                        for($i = $annee_courante - 2; $i <= $annee_courante + 2; $i++): 
                        ?>
                            <option value="<?= $i ?>" <?= $annee == $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary flex-grow-1"><i class="fa-solid fa-magnifying-glass me-1"></i> Filtrer</button>
                    <?php if ($compte_id && $membre): ?>
                        <button onclick="window.print();" class="btn btn-success"><i class="fa fa-print"></i> Imprimer</button>
                    <?php endif; ?>
                </div>
            </form>
            
            <div class="d-flex justify-content-between gap-4 mt-3 pt-3 border-top">
                <a href="profil_compte.php?id=<?= $compte_id ?>" class="btn btn-light btn-sm border <?= !$compte_id ? 'disabled' : '' ?>">
                    <i class="fa-solid fa-user me-1"></i> Voir le profil compte
                </a>
                <a href="index.php" class="btn btn-light btn-sm border">
                    <i class="fa-solid fa-house me-1"></i> Retour à l'accueil
                </a>
                <a href="membres_mutuelle.php" class="btn btn-light btn-sm border">
                    <i class="fa-solid fa-list me-1"></i> Liste des comptes
                </a>
            </div>
        </div>
    </div>

    <?php if ($compte_id && $membre): ?>
    <div class="card border-0 shadow p-5" id="printableArea">
        
        <div class="row border-bottom pb-3 mb-4 align-items-center">
            <div class="col-8">
                <h5 class="fw-bold text-dark m-0 text-uppercase">Mutuelle de Crédit et d'Entraide</h5>
                <small class="text-muted">Rapport Interne de Contrôle de Situation Caisse</small>
            </div>
            <div class="col-4 text-end">
                <small class="text-muted d-block">Généré le : <?= date('d/m/Y à H:i') ?></small>
            </div>
        </div>

        <div class="text-center mb-5">
            <h4 class="fw-bold text-uppercase mb-1 text-decoration-underline">Fiche de Situation Financière</h4>
            <span class="badge bg-secondary px-3 py-2 text-uppercase fs-6">Période : <?= htmlspecialchars($mois_fr[$mois]) ?> <?= htmlspecialchars($annee) ?></span>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-5">
                <h6 class="text-uppercase text-muted fw-bold small mb-2">Identification du Membre</h6>
                <div class="p-3 bg-light rounded-3 border-start border-3 border-primary">
                    <p class="mb-1 text-dark fs-5 fw-bold text-uppercase"><?= htmlspecialchars($membre['nom'] . ' ' . $membre['prenoms']) ?></p>
                    <p class="mb-1 text-muted">Matricule : <b><?= htmlspecialchars($membre['matricule']) ?></b></p>
                    <p class="mb-0 text-muted">Date d'adhésion : <?= !empty($membre['date_adhesion']) ? date('d/m/Y', strtotime($membre['date_adhesion'])) : 'N/A' ?></p>
                </div>
            </div>
            <div class="col-7 text-end d-flex flex-column justify-content-center">
                <div class="mb-2">
                    <h6 class="text-uppercase text-muted fw-bold small mb-0">Solde Épargne Disponible</h6>
                    <h2 class="text-success fw-bold m-0" style="font-size: 2rem;"><?= number_format($soldeGlobal, 0, ',', ' ') ?> <small class="fs-6 fw-semibold text-muted">FCFA</small></h2>
                </div>
                <div>
                    <h6 class="text-uppercase text-muted fw-bold small mb-0">Dette de Prêt en cours (Restant Dû)</h6>
                    <h4 class="text-danger fw-bold m-0"><?= number_format($resteARembourserGlobal, 0, ',', ' ') ?> <small class="fs-6 fw-semibold text-muted">FCFA</small></h4>
                </div>
            </div>
        </div>

        <h6 class="text-uppercase text-muted fw-bold small mb-2">Flux financiers constatés sur le mois</h6>
        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle text-center mb-0">
                <thead class="table-light text-secondary small text-uppercase">
                    <tr>
                        <th class="py-3">Total Épargné (Dépôts)</th>
                        <th>Total Retraits Épargne</th>
                        <th>Frais de Tenue</th>
                        <th class="bg-light-subtle text-dark border-dark-subtle">Somme Prêtée (Emprunts)</th>
                        <th>Remboursements (60%)</th>
                        <th class="bg-dark text-white">Bilan Épargne Mois</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="py-4 text-success fw-bold fs-6">+ <?= number_format($statsMois['total_depot'], 0, ',', ' ') ?> F</td>
                        <td class="py-4 text-danger fw-bold fs-6">- <?= number_format($statsMois['total_retrait'], 0, ',', ' ') ?> F</td>
                        <td class="py-4 fw-bold fs-6" style="color: #7b1fa2;">- <?= number_format($statsMois['total_frais_tenue'], 0, ',', ' ') ?> F</td>
                        <td class="py-4 fw-bold fs-6 bg-light" style="color: #bc5100;">+ <?= number_format($statsMois['total_prete'], 0, ',', ' ') ?> F</td>
                        <td class="py-4 text-info fw-bold fs-6">+ <?= number_format($statsMois['total_rembourse'], 0, ',', ' ') ?> F</td>
                        <?php 
                        // Bilan net de l'épargne disponible du mois = dépôts - (retraits + frais de tenue)
                        $soldeMois = $statsMois['total_depot'] - ($statsMois['total_retrait'] + $statsMois['total_frais_tenue']); 
                        ?>
                        <td class="py-4 fw-bold fs-6 <?= $soldeMois >= 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>">
                            <?= number_format($soldeMois, 0, ',', ' ') ?> F
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="p-3 bg-light rounded border small mb-5">
            <span class="fw-bold text-dark d-block mb-1"><i class="fa fa-info-circle me-1"></i> Synthèse des Prêts de la période :</span>
            <span class="text-muted">
                Ce mois-ci, le membre a contracté des engagements de <b><?= number_format($statsMois['total_prete'], 0, ',', ' ') ?> F</b> et ses prélèvements automatiques de tontine ont généré <b><?= number_format($statsMois['total_rembourse'], 0, ',', ' ') ?> F</b> de remboursement.
            </span>
        </div>
        
        <div class="row text-center mt-5 signature-zone">
            <div class="col-6">
                <p class="small fw-bold text-uppercase text-muted mb-5">Émargement de l'adhérent</p>
                <div class="border-top w-50 mx-auto border-secondary pt-2"><small class="text-muted">(Précéder de la date)</small></div>
            </div>
            <div class="col-6">
                <p class="small fw-bold text-uppercase text-muted mb-5">Visa de la Caisse / Trésorier</p>
                <div class="border-top w-50 mx-auto border-secondary pt-2"><small class="text-muted">Cachet officiel</small></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
@media print {
    body { background-color: #ffffff !important; }
    .no-print, header, footer, nav, .btn, .card-body form { display: none !important; }
    .container { width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
    #printableArea { border: none !important; box-shadow: none !important; padding: 0 !important; }
    .signature-zone { margin-top: 80px !important; }
}
</style>

<?php require_once '../includes/footer.php'; ?>