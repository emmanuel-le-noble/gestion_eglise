<?php
// eglise_db/mutuelle/situation_mensuelle.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'mutuelle');

$page_title = "Situation financière mensuelle"; 

$compte_id = isset($_GET['compte_id']) ? (int)$_GET['compte_id'] : null;
$mois = isset($_GET['mois']) ? (int)$_GET['mois'] : (int)date('m');
$annee = isset($_GET['annee']) ? (int)$_GET['annee'] : (int)date('Y');

$membre = null;
$statsMois = ['total_depot' => 0, 'total_retrait' => 0, 'total_commission' => 0, 'total_rembourse' => 0, 'total_prete' => 0];
$soldeGlobal = 0;
$resteARembourserGlobal = 0;

$mois_fr = [
    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
    7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
];

if ($compte_id) {
    // 1. Informations du membre
    $stmt = $pdo->prepare("SELECT m.*, c.date_adhesion, c.id as cid FROM mutuelle_comptes c JOIN membres m ON c.membre_id = m.id WHERE c.id = ?");
    $stmt->execute([$compte_id]);
    $membre = $stmt->fetch();

    if ($membre) {
        // 2. Flux du mois
        $sqlStats = "SELECT 
            IFNULL(SUM(CASE WHEN type_operation = 'DEPOT' THEN montant ELSE 0 END), 0) as total_depot,
            IFNULL(SUM(CASE WHEN type_operation = 'RETRAIT' THEN montant ELSE 0 END), 0) as total_retrait,
            IFNULL(SUM(CASE WHEN type_operation = 'COMMISSION' THEN montant ELSE 0 END), 0) as total_commission,
            IFNULL(SUM(CASE WHEN type_operation = 'REMBOURSEMENT' THEN montant ELSE 0 END), 0) as total_rembourse,
            IFNULL(SUM(CASE WHEN type_operation = 'PRET' THEN montant ELSE 0 END), 0) as total_prete
            FROM mutuelle_operations WHERE compte_id = ? AND MONTH(date_op) = ? AND YEAR(date_op) = ?";
        $stmtStats = $pdo->prepare($sqlStats);
        $stmtStats->execute([$compte_id, $mois, $annee]);
        $statsMois = $stmtStats->fetch() ?: $statsMois;

        // 3. Bilans globaux
        $sqlGlobal = "SELECT 
            IFNULL(SUM(CASE WHEN type_operation = 'DEPOT' THEN montant ELSE 0 END), 0) - 
            IFNULL(SUM(CASE WHEN type_operation IN ('RETRAIT', 'COMMISSION') THEN montant ELSE 0 END), 0) as solde_total,
            IFNULL(SUM(CASE WHEN type_operation = 'PRET' THEN montant ELSE 0 END), 0) - 
            IFNULL(SUM(CASE WHEN type_operation = 'REMBOURSEMENT' THEN montant ELSE 0 END), 0) as reste_a_rembourser
            FROM mutuelle_operations WHERE compte_id = ?";
        $stmtGlobal = $pdo->prepare($sqlGlobal);
        $stmtGlobal->execute([$compte_id]);
        $resGlobal = $stmtGlobal->fetch();
        
        if ($resGlobal) {
            $soldeGlobal = $resGlobal['solde_total'];
            $resteARembourserGlobal = max(0, $resGlobal['reste_a_rembourser']);
        }
    }
}

$comptes = $pdo->query("SELECT c.id, m.matricule, m.nom, m.prenoms FROM mutuelle_comptes c JOIN membres m ON c.membre_id = m.id ORDER BY m.nom ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registre_Membres_Mutuelle</title>
    <!-- Bootstrap 5.3.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<!-- FILTRE ET NAVIGATION (MASQUÉS À L'IMPRESSION) -->
<div class="container mt-4 mb-4 no-print">
    <div class="card border-0 shadow-sm no-print">
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
                        <?php $ac = (int)date('Y'); for($i=$ac-2; $i<=$ac+2; $i++): ?>
                            <option value="<?= $i ?>" <?= $annee == $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="fa fa-search"></i> Filtrer</button>
                    <?php if ($compte_id && $membre): ?>
                        <button type="button" onclick="window.print();" class="btn btn-success"><i class="fa fa-print"></i> Imprimer A4</button>
                    <?php endif; ?>
                </div>
            </form>
            <div class="d-flex justify-content-between gap-4 mt-3 pt-3 border-top">
                <a href="profil_compte.php?id=<?= $compte_id ?>" class="btn btn-light btn-sm border <?= !$compte_id ? 'disabled' : '' ?>">
                    <i class="fa-solid fa-user me-1"></i> Voir le profil compte
                </a>
                <a href="fiche_mensuelle.php?compte_id=<?= $compte_id ?>" class="btn btn-light btn-sm border">
                    <i class="fa-solid fa-arrow-left me-1"></i> retour au fiche mensuelle
                </a>
                <a href="membres_mutuelle.php" class="btn btn-light btn-sm border">
                    <i class="fa-solid fa-list me-1"></i> Retour à la liste des comptes
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ZONE IMPRIMABLE FORMATÉ A4 -->
<?php if ($compte_id && $membre): ?>
<div class="a4-page-container">
    <div id="printableArea" class="a4-sheet">
        
        <!-- En-tête Institutionnel -->
        <div class="row align-items-center pb-3 mb-4 border-bottom border-2 border-dark">
            <div class="col-7">
                <h5 class="fw-bold text-dark m-0 tracking-wide text-uppercase">Mutuelle de Crédit et d'Entraide</h5>
                <span class="text-muted small fs-7">Rapport de gestion financière automatisé</span>
            </div>
            <div class="col-5 text-end">
                <span class="d-block text-secondary small">Date d'édition : <?= date('d/m/Y à H:i') ?></span>
                <span class="d-block text-secondary small">Identifiant Compte : MUT-<?= str_pad($membre['cid'], 5, '0', STR_PAD_LEFT) ?></span>
            </div>
        </div>

        <!-- Titre du Document -->
        <div class="text-center mb-4">
            <h3 class="fw-bold text-uppercase m-0 text-dark">Fiche de Situation Financière</h3>
            <div class="fs-6 fw-semibold text-secondary mt-1 text-uppercase">Période comptable : <?= $mois_fr[$mois] ?> <?= $annee ?></div>
        </div>

        <!-- Blocs d'informations et Soldes -->
        <div class="row g-3 mb-4">
            <div class="col-6">
                <div class="p-3 bg-light rounded height-full border-start border-4 border-primary">
                    <div class="text-uppercase text-muted fw-bold small mb-1">Titulaire du compte</div>
                    <div class="fs-6 fw-bold text-dark text-uppercase"><?= htmlspecialchars($membre['nom'] . ' ' . $membre['prenoms']) ?></div>
                    <div class="text-dark mt-1 small">Matricule : <span class="fw-bold"><?= htmlspecialchars($membre['matricule']) ?></span></div>
                    <div class="text-muted small">Adhésion : <?= !empty($membre['date_adhesion']) ? date('d/m/Y', strtotime($membre['date_adhesion'])) : 'Non renseignée' ?></div>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 bg-light rounded height-full border-end border-4 border-success text-end">
                    <div class="mb-2">
                        <span class="text-uppercase text-muted fw-bold small d-block">Solde Épargne Disponible</span>
                        <span class="fs-5 fw-bold text-success"><?= number_format($soldeGlobal, 0, ',', ' ') ?> F CFA</span>
                    </div>
                    <div class="border-top pt-1">
                        <span class="text-uppercase text-muted fw-bold cs-small d-block">Encours des Prêts (Reste à payer)</span>
                        <span class="fs-6 fw-bold text-danger"><?= number_format($resteARembourserGlobal, 0, ',', ' ') ?> F CFA</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tableau des flux du mois -->
        <div class="mb-4">
            <div class="text-uppercase text-dark fw-bold small mb-2 tracking-wide">I. Flux de trésorerie constatés sur la période</div>
            <table class="table table-bordered table-print align-middle text-center mb-0">
                <thead>
                    <tr class="bg-light text-uppercase text-dark border-dark">
                        <th class="py-2 small" width="20%">Dépôts (Tontine)</th>
                        <th class="small" width="20%">Retraits Épargne</th>
                        <th class="small" width="16%">Frais / Comm.</th>
                        <th class="small bg-gray-100" width="22%">Prêts accordés</th>
                        <th class="small" width="22%">Remboursements</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="fs-6 text-dark border-dark">
                        <td class="py-3 text-success fw-bold">+<?= number_format($statsMois['total_depot'], 0, ',', ' ') ?></td>
                        <td class="text-danger fw-bold">-<?= number_format($statsMois['total_retrait'], 0, ',', ' ') ?></td>
                        <td class="text-warning fw-bold">-<?= number_format($statsMois['total_commission'], 0, ',', ' ') ?></td>
                        <td class="fw-bold bg-gray-100" style="color: #6f42c1;">+<?= number_format($statsMois['total_prete'], 0, ',', ' ') ?></td>
                        <td class="text-info fw-bold">+<?= number_format($statsMois['total_rembourse'], 0, ',', ' ') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Indicateur Évolution Mensuelle -->
        <div class="row g-3 mb-4">
            <div class="col-12">
                <?php 
                $soldeMois = $statsMois['total_depot'] - ($statsMois['total_retrait'] + $statsMois['total_commission']);
                ?>
                <div class="p-2 px-3 rounded border text-center d-flex justify-content-between align-items-center <?= $soldeMois >= 0 ? 'bg-success-subtle-print text-success' : 'bg-danger-subtle-print text-danger' ?>">
                    <span class="text-uppercase small fw-bold">Variation nette de l'épargne sur ce mois :</span>
                    <span class="fs-5 font-monospace fw-bold"><?= $soldeMois >= 0 ? '+' : '' ?><?= number_format($soldeMois, 0, ',', ' ') ?> F CFA</span>
                </div>
            </div>
        </div>

        <!-- Notes de conformité -->
        <div class="mb-5">
            <div class="text-uppercase text-dark fw-bold small mb-1 tracking-wide">II. Observations & Mentions légales</div>
            <div class="p-3 bg-light rounded text-muted justify-text border" style="font-size: 0.8rem; line-height: 1.4;">
                Le présent état récapitule l'ensemble des écritures comptables enregistrées au nom du titulaire pour le mois spécifié. Les calculs de solde global prennent en considération l'antériorité historique du compte depuis sa création. En cas de contestation sur les montants affichés, le sociétaire dispose d'un délai de quinze (15) jours après édition pour formuler un recours écrit auprès du bureau d'administration de la mutuelle.
            </div>
        </div>

        <!-- Zone des signatures en bas de page -->
        <div class="row text-center signature-block mt-auto">
            <div class="col-6">
                <span class="text-uppercase fw-bold text-secondary d-block mb-5 pb-4" style="font-size: 0.75rem; letter-spacing: 0.5px;">Signature de l'adhérent</span>
                <div class="border-top w-60 mx-auto pt-2 text-muted small italic">Mention "Lu et approuvé"</div>
            </div>
            <div class="col-6">
                <span class="text-uppercase fw-bold text-secondary d-block mb-5 pb-4" style="font-size: 0.75rem; letter-spacing: 0.5px;">Pour la Commission de Contrôle</span>
                <div class="border-top w-60 mx-auto pt-2 text-muted small italic">Nom, prénom et cachet</div>
            </div>
        </div>

    </div>
</div>
<?php endif; ?>

<!-- ARCHITECTURE STYLING CSS SPECIFIQUE A4 -->
<style>
/* Affichage écran standard */
*{
    font-family: 'poppins', sans-serif;
}
.a4-page-container {
    display: flex;
    justify-content: center;
    background-color: #f4f6f9;
    padding: 20px 0;
}
.a4-sheet {
    background: #ffffff;
    width: 210mm;
    min-height: 297mm;
    padding: 20mm;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
}
.fs-7 { font-size: 0.75rem; }
.cs-small { font-size: 0.8rem; }
.bg-gray-100 { background-color: #f8f9fa !important; }
.w-60 { width: 60% !important; }
.justify-text { text-align: justify; }
.tracking-wide { letter-spacing: 0.5px; }

/* Directives d'impression strictes */
@media print {
    /* Nettoyage complet des éléments web superflus */
    html, body {
        background: #fff !important;
        width: 210mm;
        height: 297mm;
        margin: 0 !important;
        padding: 0 !important;
    }
    header, footer, nav, .no-print, .btn, .main-sidebar, .main-footer {
        display: none !important;
    }
    .a4-page-container {
        padding: 0 !important;
        background: none !important;
    }
    .a4-sheet {
        border: none !important;
        box-shadow: none !important;
        width: 210mm;
        height: 297mm;
        padding: 15mm 15mm 15mm 15mm !important;
        margin: 0 !important;
        box-sizing: border-box;
        page-break-after: avoid;
        page-break-inside: avoid;
    }
    /* Rendre les bordures de tableaux Bootstrap visibles sur toutes les imprimantes */
    .table-print th, .table-print td {
        border: 1px solid #000000 !important;
    }
    .table-print thead tr {
        background-color: #eaeaea !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    /* Forcer la coloration des fonds clairs à l'impression */
    .bg-light {
        background-color: #f8f9fa !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .bg-success-subtle-print {
        background-color: #d1e7dd !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .bg-danger-subtle-print {
        background-color: #f8d7da !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    /* Pousser le bloc signature tout en bas de la page A4 */
    .signature-block {
        margin-top: auto !important;
        padding-bottom: 20mm !important;
    }
}
</style>

</body>
</html>