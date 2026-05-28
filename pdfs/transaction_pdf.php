<?php
require_once "../vendor/autoload.php";
require_once "../config/database.php";

use Dompdf\Dompdf;
use Dompdf\Options;

$id = $_GET['id'];

// transaction
$sql = "SELECT t.*, l.libelle, u.nom FROM transactions t LEFT JOIN lignes_budget l  ON t.ligne_budget_id = l.id LEFT JOIN utilisateurs u ON t.utilisateur_id = u.id WHERE t.id=?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$t = $stmt->fetch();

$options = new Options();
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);

$html = '
<style>
body{
    font-family: Arial;
    font-size:14px;
}

h1{
    text-align:center;
}

table{
    width:100%;
    border-collapse: collapse;
}

td{
    border:1px solid #000;
    padding:10px;
}
</style>

<h1>Rapport Transaction</h1>

<table>

<tr>
<td><b>Type</b></td>
<td>'.$t['type_transaction'].'</td>
</tr>

<tr>
<td><b>Montant</b></td>
<td>'.number_format($t['montant'],0,',',' ').' FCFA</td>
</tr>

<tr>
<td><b>Description</b></td>
<td>'.$t['description'].'</td>
</tr>

<tr>
<td><b>Budget</b></td>
<td>'.$t['libelle'].'</td>
</tr>

<tr>
<td><b>Date</b></td>
<td>'.$t['date_transaction'].'</td>
</tr>

<tr>
<td><b>Enregistré par</b></td>
<td>'.$t['nom'].'</td>
</tr>

</table>
';

$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'portrait');

$dompdf->render();

$dompdf->stream("transaction.pdf", ["Attachment"=>false]);
