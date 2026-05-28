<?php
require_once "../config/database.php";
require_once "../includes/session.php";

// Toutes les pages du dossier membres contiendront cette ligne :
securiser_par_module($pdo, 'membres');

// Nom du fichier avec la date du jour
$filename = "Export_Global_Membres_" . date('d-m-Y') . ".xls";

// Headers pour forcer le téléchargement en format Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Entête BOM UTF-8 indispensable pour les accents
echo "\xEF\xBB\xBF"; 

// 1. Récupération de toutes les colonnes de membres + le nom de l'utilisateur
// Ajuste 'u.nom' par 'u.username' ou 'u.identifiant' si la colonne porte un autre nom dans ta table utilisateurs
$sql = "SELECT m.*, u.nom AS enregistre_by  FROM membres m LEFT JOIN utilisateurs u ON m.utilisateur_id = u.id  ORDER BY m.nom ASC, m.prenoms ASC";
$query = $pdo->query($sql);
$membres = $query->fetchAll(PDO::FETCH_ASSOC);

?>
<table border="1">
    <thead>
        <tr style="background-color: #1F4E78; color: white; font-weight: bold; text-align: center; font-family :'poppins';">
            <!-- 1. État civil & Identification -->
            <th>Matricule</th>
            <th>Nom</th>
            <th>Prénoms</th>
            <th>Sexe</th>
            <th>Date de naissance</th>
            <th>Lieu de naissance</th>
            <th>Profession</th>
            
            <!-- 2. Adresse & Contact -->
            <th>Téléphone 1</th>
            <th>Téléphone 2</th>
            <th>E-mail</th>
            <th>Quartier / Domicile</th>
            
            <!-- 3. Situation de famille -->
            <th>Situation Matrimoniale</th>
            <th>Date mariage</th>
            <th>Lieu mariage</th>
            <th>Nom Conjoint</th>
            <th>Nombre d'enfants</th>
            
            <!-- 4. Situation ecclésiastique -->
            <th>Église de provenance</th>
            <th>Date d'arrivée</th>
            <th>Baptisé (Immersion)</th>
            <th>Date de baptême</th>
            <th>Lieu de baptême</th>
            <th>Engagement moral</th>
            <th>Groupe d'action</th>
            <th>Qualité</th>
            <th>Statut Membre</th>
            
            <!-- 5. Métadonnées -->
            <th>Commentaire / Observations</th>
            <th>Date d'enregistrement</th>
            <th>Enregistré par</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($membres as $m): ?>
            <tr>
                <!-- 1. État civil & Identification -->
                <td><?php echo htmlspecialchars($m['matricule'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($m['nom'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($m['prenoms'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($m['sexe'] ?? ''); ?></td>
                <td style="text-align: center;"><?php echo htmlspecialchars($m['date_naissance'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($m['lieu_naissance'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($m['profession'] ?? ''); ?></td>
                
                <!-- 2. Adresse & Contact -->
                <td style="mso-number-format:'\@';"><?php echo htmlspecialchars($m['telephone1'] ?? ''); ?></td>
                <td style="mso-number-format:'\@';"><?php echo htmlspecialchars($m['telephone2'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($m['email'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($m['quartier'] ?? ''); ?></td>
                
                <!-- 3. Situation de famille -->
                <td><?php echo htmlspecialchars($m['situation_matrimoniale'] ?? ''); ?></td>
                <td style="text-align: center;"><?php echo htmlspecialchars($m['date_mariage'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($m['lieu_mariage'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($m['nom_conjoint'] ?? ''); ?></td>
                <td style="text-align: right;"><?php echo (int)($m['nombre_enfants'] ?? 0); ?></td>
                
                <!-- 4. Situation ecclésiastique -->
                <td><?php echo htmlspecialchars($m['eglise_provenance'] ?? ''); ?></td>
                <td style="text-align: center;"><?php echo htmlspecialchars($m['date_arrivee'] ?? ''); ?></td>
                <td style="text-align: center;"><?php echo ($m['baptise'] == 1) ? 'Oui' : 'Non'; ?></td>
                <td style="text-align: center;"><?php echo htmlspecialchars($m['date_bapteme'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($m['lieu_bapteme'] ?? ''); ?></td>
                <td style="text-align: center;"><?php echo ($m['engagement_moral'] == 1) ? 'Oui' : 'Non'; ?></td>
                <td style="text-align: center;"><?php echo htmlspecialchars($m['groupe_action'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($m['qualite'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($m['statut_membre'] ?? ''); ?></td>
                
                <!-- 5. Métadonnées -->
                <td><?php echo htmlspecialchars($m['commentaire'] ?? ''); ?></td>
                <td style="text-align: center;"><?php echo htmlspecialchars($m['date_enregistrement'] ?? ''); ?></td>
                
                <!-- CORRECTION ICI : On affiche le nom aliasé à la place de l'ID -->
                <td><?php echo htmlspecialchars($m['enregistre_by'] ?? 'Inconnu'); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>