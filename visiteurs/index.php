<?php
require_once "../config/database.php";
require_once '../includes/helpers.php';
$page_title = "Gestion du Suivi"; 
require_once '../includes/header.php'; 

$visiteurs = $pdo->query("SELECT * FROM visiteurs ORDER BY date_visite DESC")->fetchAll();
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold"><i class="fa-solid fa-users-rays text-warning"></i> Suivi des Visiteurs</h4>
        <a href="ajouter.php" class="btn btn-dark btn-sm shadow-sm"><i class="fa fa-plus"></i> Nouveau Visiteur</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Nom & Contact</th>
                        <th>Date Venue</th>
                        <th>Statut</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($visiteurs as $v): ?>
                    <tr>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($v['nom_prenoms']) ?></div>
                            <small class="text-muted"><i class="fa fa-phone small"></i> <?= $v['telephone'] ?></small>
                        </td>
                        <td><?= date('d/m/Y', strtotime($v['date_visite'])) ?></td>
                        <td>
                            <?php 
                                $color = "bg-secondary";
                                if($v['statut_suivi'] == 'À contacter') $color = "bg-danger";
                                if($v['statut_suivi'] == 'En cours') $color = "bg-warning text-dark";
                                if($v['statut_suivi'] == 'Fidélisé') $color = "bg-success";
                            ?>
                            <span class="badge <?= $color ?>"><?= $v['statut_suivi'] ?></span>
                        </td>
                        <td class="text-center">
                            <div class="btn-group">
                                <a href="tel:<?= $v['telephone'] ?>" class="btn btn-outline-success btn-sm"><i class="fa fa-phone"></i></a>
                                <a href="modifier.php?id=<?= $v['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="fa fa-edit"></i></a>
                                <a href="convertir.php?id=<?= $v['id'] ?>" 
                                    class="btn btn-outline-dark btn-sm" 
                                    onclick="return confirm('Voulez-vous transformer ce visiteur en membre officiel ?')" 
                                    title="Convertir en Membre">
                                    <i class="fa-solid fa-user-check"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>