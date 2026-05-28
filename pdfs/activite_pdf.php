<?php
require_once "../vendor/autoload.php";
require_once "../config/database.php";

use Dompdf\Dompdf;
use Dompdf\Options;

$id = $_GET['id'];

// activité
$stmt = $pdo->prepare(" SELECT * FROM activites WHERE id=? ");

$stmt->execute([$id]);

$a = $stmt->fetch();

// participants
$sql = "SELECT pa.*, m.nom, m.prenoms FROM participants_activites pa JOIN membres m ON pa.membre_id = m.id WHERE pa.activite_id=?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$participants = $stmt->fetchAll();

$options = new Options();
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);

$html = '
<style>

body{
    font-family: Arial;
    font-size:13px;
}

h1{
    text-align:center;
}

table{
    width:100%;
    border-collapse: collapse;
}

th, td{
    border:1px solid #000;
    padding:8px;
}

</style>

<h1>Rapport Activité</h1>

<p><b>Titre :</b> '.$a['titre'].'</p>
<p><b>Date :</b> '.$a['date_activite'].'</p>
<p><b>Description :</b> '.$a['description'].'</p>

<h3>Participants</h3>

<table>

<tr>
<th>Nom</th>
<th>Prénoms</th>
<th>Présence</th>
</tr>';

foreach($participants as $p){

$html .= '

<tr>
<td>'.$p['nom'].'</td>
<td>'.$p['prenoms'].'</td>
<td>'.$p['presence'].'</td>
</tr>';

}

$html .= '
</table>
';

$dompdf->loadHtml($html);

$dompdf->setPaper('A4','portrait');

$dompdf->render();

$dompdf->stream("activite.pdf", ["Attachment"=>false]);
