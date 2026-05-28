<?php
// eglise_db/mutuelle/cotisation.php
require_once "../config/database.php";
require_once "../includes/session.php";
securiser_par_module($pdo, 'mutuelle');

$message = "";
$show_receipt = false;
$receipt_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $compte_id = (int)$_POST['compte_id'];
    $montant_verse = isset($_POST['montant_tontine']) ? (float)$_POST['montant_tontine'] : 0; // On garde le name du input pour ne pas casser ton HTML de base
    $date_op = $_POST['date_op'];
    $user_id = $_SESSION['user_id'];

    try {
        if ($montant_verse <= 0) {
            throw new Exception("Vous devez spécifier un montant supérieur à 0 pour effectuer un versement.");
        }

        $pdo->beginTransaction();

        // 1. Récupérer les informations du compte et du membre avec verrouillage
        $stmt_m = $pdo->prepare("SELECT m.nom, m.prenoms, m.matricule, mc.solde_tontine 
                                 FROM mutuelle_comptes mc 
                                 JOIN membres m ON mc.membre_id = m.id 
                                 WHERE mc.id = ? FOR UPDATE");
        $stmt_m->execute([$compte_id]);
        $membre_info = $stmt_m->fetch();

        if (!$membre_info) {
            throw new Exception("Le compte de ce membre est introuvable ou inactif.");
        }

        // 2. Vérifier s'il y a un prêt actif pour appliquer la règle des 60/40
        $stmt_pret = $pdo->prepare("SELECT id, montant_prete, montant_rembourse FROM mutuelle_prets WHERE compte_id = ? AND statut = 'EN_COURS' LIMIT 1 FOR UPDATE");
        $stmt_pret->execute([$compte_id]);
        $pret_actif = $stmt_pret->fetch();

        $part_epargne = $montant_verse;
        $part_remboursement = 0;
        $pret_id = null;

        if ($pret_actif) {
            $pret_id = $pret_actif['id'];
            // Application de la règle de ventilation : 60% remboursement / 40% épargne
            $part_remboursement = $montant_verse * 0.60;
            $part_epargne = $montant_verse * 0.40;

            // Enregistrement de l'opération de remboursement (60%)
            $stmt_remb = $pdo->prepare("INSERT INTO mutuelle_operations (compte_id, pret_id, type_operation, montant, date_op, utilisateur_id, commentaire) VALUES (?, ?, 'REMBOURSEMENT', ?, ?, ?, 'Remboursement Prêt (60%)')");
            $stmt_remb->execute([$compte_id, $pret_id, $part_remboursement, $date_op, $user_id]);
            $id_op_maitre = $pdo->lastInsertId(); // Servira de numéro de ticket

            // Mise à jour du cumul remboursé sur le prêt
            $upd_pret = $pdo->prepare("UPDATE mutuelle_prets SET montant_rembourse = montant_rembourse + ? WHERE id = ?");
            $upd_pret->execute([$part_remboursement, $pret_id]);

            // Vérification si le prêt est totalement soldé (en comptant la part remboursée actuelle)
            // Note : le montant remboursé attendu correspond au montant prêté de base
            $nouveau_cumul_remb = $pret_actif['montant_rembourse'] + $part_remboursement;
            if ($nouveau_cumul_remb >= $pret_actif['montant_prete']) {
                $upd_statut_pret = $pdo->prepare("UPDATE mutuelle_prets SET statut = 'SOLDE' WHERE id = ?");
                $upd_statut_pret->execute([$pret_id]);
            }

        }

        // 3. Enregistrement de la part Épargne (100% ou 40%)
        $stmt_epargne = $pdo->prepare("INSERT INTO mutuelle_operations (compte_id, pret_id, type_operation, montant, date_op, utilisateur_id, commentaire) VALUES (?, ?, 'DEPOT', ?, ?, ?, ?)");
        $commentaire_epargne = $pret_actif ? "Cotisation Épargne (40%)" : "Cotisation Épargne (100%)";
        $stmt_epargne->execute([$compte_id, $pret_id, $part_epargne, $date_op, $user_id]);
        
        if (!isset($id_op_maitre)) {
            $id_op_maitre = $pdo->lastInsertId();
        }

        // Mise à jour du solde de la tontine
        $upd_compte = $pdo->prepare("UPDATE mutuelle_comptes SET solde_tontine = solde_tontine + ? WHERE id = ?");
        $upd_compte->execute([$part_epargne, $compte_id]);

        $pdo->commit();

        // Préparation des données du reçu thermique
        $receipt_data = [
            'ticket_no' => $id_op_maitre,
            'matricule' => $membre_info['matricule'],
            'nom_complet' => $membre_info['nom'] . ' ' . $membre_info['prenoms'],
            'date' => $date_op,
            'total_general' => $montant_verse,
            'part_epargne' => $part_epargne,
            'part_remboursement' => $part_remboursement,
            'tontine_nouveau_solde' => $membre_info['solde_tontine'] + $part_epargne,
            'has_pret' => $pret_actif ? true : false
        ];
        $show_receipt = true;

        $message = "<div class='alert alert-success shadow-sm d-flex align-items-center justify-content-between mb-0'>
                        <span><i class='fa-solid fa-circle-check me-2'></i>Versement enregistré avec succès (" . number_format($montant_verse, 0, ',', ' ') . " F CFA) !</span>
                        <button onclick='imprimerTicket()' class='btn btn-light btn-sm border shadow-sm'><i class='fa-solid fa-print me-1'></i> Réimprimer le reçu</button>
                    </div>";

    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "<div class='alert alert-danger'><i class='fa-solid fa-triangle-exclamation me-2'></i>Erreur : " . $e->getMessage() . "</div>";
    }
}

// Récupérer les membres actifs de la mutuelle
$membres = $pdo->query("SELECT mc.id, m.nom, m.prenoms, m.matricule FROM mutuelle_comptes mc JOIN membres m ON mc.membre_id = m.id WHERE mc.statut = 'ACTIF' ORDER BY m.nom ASC")->fetchAll();

$page_title = "Enregistrer une cotisation"; 
require_once '../includes/header.php'; 
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-primary"><i class="fa-solid fa-piggy-bank me-2"></i>Versement Cotisation</h5>
                    <div>
                        <a href="membres_mutuelle.php" class="btn btn-light btn-sm border">Liste des comptes</a>
                        <a href="index.php" class="btn btn-light btn-sm border">Retour</a>
                    </div>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($message)): ?>
                        <div class="mb-4"><?= $message ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="small fw-bold mb-1 text-muted">Membre de la mutuelle</label>
                            <select name="compte_id" class="form-select select2" required>
                                <option value="">-- Sélectionner le membre --</option>
                                <?php foreach($membres as $m): ?>
                                    <option value="<?= $m['id'] ?>"><?= $m['matricule'] ?> - <?= htmlspecialchars($m['nom'] . ' ' . $m['prenoms']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold mb-1 text-success">Montant versé (FCFA)</label>
                            <input type="number" name="montant_tontine" class="form-control form-control-lg border-success border-opacity-50 fw-bold text-success" placeholder="Ex: 5000" min="50" step="50" required>
                        </div>

                        <div class="mb-4">
                            <label class="small fw-bold mb-1 text-muted">Date du versement</label>
                            <input type="date" name="date_op" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary shadow-sm fw-semibold py-2">
                                <i class="fa-solid fa-floppy-disk me-2"></i>Enregistrer & imprimer le reçu
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="p-3 bg-light rounded border-start border-4 border-info small shadow-sm">
                <i class="fa-solid fa-circle-info me-2 text-info"></i>
                <strong>Note :</strong> Si le membre possède un prêt en cours, le système affectera automatiquement 60% au remboursement et 40% à son épargne tontine.
            </div>
        </div>
    </div>
</div>

<?php if ($show_receipt): ?>
<div id="thermal-receipt" style="display:none;">
    <div style="font-family:'Courier New', Courier, monospace; width: 80mm; padding: 3mm; color:#000; font-size:12px;">
        <div style="text-align:center; font-weight:bold; font-size:14px; text-transform:uppercase; margin-bottom:2px;">MUTUELLE DE CRÉDIT & ENTRAIDE</div>
        <div style="text-align:center; font-size:11px; margin-bottom:12px;">Guichet d'enregistrement Caisse</div>
        
        <div style="border-bottom:1px dashed #000; margin-bottom:8px;"></div>
        
        <div>
            <b>REÇU N° :</b> COT-<?= str_pad($receipt_data['ticket_no'], 6, '0', STR_PAD_LEFT) ?><br>
            <b>Date :</b> <?= date('d/m/Y à H:i', strtotime($receipt_data['date'] . ' ' . date('H:i:s'))) ?><br>
            <b>Opérateur :</b> <?= htmlspecialchars($_SESSION['user_name'] ?? 'Caissier') ?>
        </div>
        
        <div style="border-bottom:1px dashed #000; margin-bottom:8px;"></div>
        
        <div style="margin-bottom:12px;">
            <b>Adhérent :</b> <?= htmlspecialchars($receipt_data['nom_complet']) ?><br>
            <b>Matricule :</b> <?= htmlspecialchars($receipt_data['matricule']) ?>
        </div>
        
        <div style="border-bottom:1px dashed #000; margin-bottom:5px;"></div>
        <div style="font-weight:bold; text-align:center; margin-bottom:5px; font-size:11px;">DÉTAIL DU VERSEMENT</div>
        <div style="border-bottom:1px dashed #000; margin-bottom:8px;"></div>
        
        <div style="display:flex; justify-content:between; margin-bottom:3px;">
            <span>Montant Global Déposé :</span>
            <span style="font-weight:bold;"><?= number_format($receipt_data['total_general'], 0, ',', ' ') ?> F</span>
        </div>

        <div style="border-bottom:1px dotted #000; margin-top:5px; margin-bottom:5px;"></div>

        <!-- Section ventilation dynamique -->
        <div style="display:flex; justify-content:between; margin-bottom:3px;">
            <span>• Affectation Épargne <?= $receipt_data['has_pret'] ? '(40%)' : '(100%)' ?> :</span>
            <span style="font-weight:bold;"><?= number_format($receipt_data['part_epargne'], 0, ',', ' ') ?> F</span>
        </div>

        <?php if ($receipt_data['has_pret']): ?>
            <div style="display:flex; justify-content:between; margin-bottom:3px;">
                <span>• Affectation Prêt (60%) :</span>
                <span style="font-weight:bold;"><?= number_format($receipt_data['part_remboursement'], 0, ',', ' ') ?> F</span>
            </div>
        <?php endif; ?>

        <div style="font-size:11px; color:#555; text-align:right; margin-top:5px; margin-bottom:6px; font-style:italic;">
            (Nouveau solde épargne : <?= number_format($receipt_data['tontine_nouveau_solde'], 0, ',', ' ') ?> F)
        </div>
        
        <div style="border-bottom:1px dashed #000; margin-top:5px; margin-bottom:5px;"></div>
        
        <div style="background-color:#eee; padding:6px; text-align:center; font-size:14px; font-weight:bold; margin-bottom:15px;">
            TOTAL NET NETTOYÉ :<br>
            <?= number_format($receipt_data['total_general'], 0, ',', ' ') ?> F CFA
        </div>
        
        <div style="border-bottom:1px dashed #000; margin-bottom:10px;"></div>
        
        <div style="text-align:center; font-size:10px; font-style:italic;">
            Merci pour votre régularité.<br>
            Document édité automatiquement par le système.
        </div>
    </div>
</div>

<script>
function imprimerTicket() {
    var rawHtml = document.getElementById('thermal-receipt').innerHTML;
    var printWindow = window.open('', '', 'height=600,width=420');
    
    printWindow.document.write('<html><head><title>Ticket de Caisse - Cotisation</title>');
    printWindow.document.write('<style>body{margin:0;padding:0;} @media print { body { width: 80mm; } div { display:block; } span { display:inline-block; } }</style>');
    printWindow.document.write('<style>div{box-sizing:border-box;} span:nth-child(2){float:right;}</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(rawHtml);
    printWindow.document.write('</body></html>');
    
    printWindow.document.close();
    printWindow.focus();
    
    setTimeout(function(){
        printWindow.print();
        printWindow.close();
    }, 400);
}

window.onload = function() {
    imprimerTicket();
};
</script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>