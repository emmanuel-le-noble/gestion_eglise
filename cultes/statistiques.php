<?php
require_once "../config/database.php";
require_once '../includes/helpers.php';
$page_title = "Statistiques de croissance"; 
require_once '../includes/header.php'; 

// 1. Récupérer la fréquentation moyenne par mois sur les 6 derniers mois
$query = "SELECT  DATE_FORMAT(date_culte, '%b %Y') as mois,  AVG(total_presences) as moyenne_presence, SUM(nombre_visiteurs) as total_visiteurs FROM cultes  GROUP BY mois  ORDER BY date_culte ASC  LIMIT 6";
$stats = $pdo->query($query)->fetchAll();

// Préparation des données pour le graphique JavaScript
$labels = [];
$presences = [];
$visiteurs = [];

foreach($stats as $s) {
    $labels[] = $s['mois'];
    $presences[] = round($s['moyenne_presence']);
    $visiteurs[] = $s['total_visiteurs'];
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between">
        <h4 class="fw-bold">Analyse de la croissance</h4>
        <a href="index.php" class="btn btn-light btn-sm border">Retour</a>
    </div>
    <br>

    <div class="row g-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm p-4">
                <h6 class="fw-bold text-secondary">Fréquentation moyenne (6 derniers mois)</h6>
                <canvas id="growthChart" height="370"></canvas>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4">
                <h6 class="fw-bold text-secondary text-center">Répartition du dernier culte</h6>
                <canvas id="repartitionChart"></canvas>
            </div>
        </div>
    </div>
</div>

<?php
// On récupère les données du tout dernier culte pour le graphique en camembert
$dernier_culte = $pdo->query("SELECT nombre_hommes, nombre_femmes, nombre_enfants FROM cultes ORDER BY date_culte DESC LIMIT 1")->fetch();
?>

<script>
// --- Graphique de Croissance (Courbe) ---
const ctxGrowth = document.getElementById('growthChart').getContext('2d');
new Chart(ctxGrowth, {
    type: 'line',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: 'Moyenne de fidèles',
            data: <?= json_encode($presences) ?>,
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13, 110, 253, 0.1)',
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});

// --- Graphique de Répartition (Camembert) ---
const ctxRep = document.getElementById('repartitionChart').getContext('2d');
new Chart(ctxRep, {
    type: 'doughnut',
    data: {
        labels: ['Hommes', 'Femmes', 'Enfants'],
        datasets: [{
            data: [
                <?= $dernier_culte['nombre_hommes'] ?? 0 ?>, 
                <?= $dernier_culte['nombre_femmes'] ?? 0 ?>, 
                <?= $dernier_culte['nombre_enfants'] ?? 0 ?>
            ],
            backgroundColor: ['#0d6efd', '#dc3545', '#198754']
        }]
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>