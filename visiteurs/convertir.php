<?php
require_once "../config/database.php";
require_once '../includes/helpers.php';

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: index.php"); exit; }

try {
    $pdo->beginTransaction();

    // 1. Récupérer les infos du visiteur
    $stmt = $pdo->prepare("SELECT * FROM visiteurs WHERE id = ?");
    $stmt->execute([$id]);
    $v = $stmt->fetch();

    if ($v) {
        // 2. Générer un nouveau matricule
        $nouveauMatricule = genererMatricule($pdo);
        
        // 3. Insérer dans la table membres (avec les infos connues)
        $sql = "INSERT INTO membres (matricule, nom, prenoms, telephone1, quartier, statut, utilisateur_id) 
                VALUES (?, ?, ?, ?, ?, 'Actif', ?)";
        $stmtM = $pdo->prepare($sql);
        
        // On sépare le nom et le prénom si possible (basé sur le premier espace)
        $parts = explode(' ', $v['nom_prenoms'], 2);
        $nom = $parts[0];
        $prenoms = $parts[1] ?? '';

        $stmtM->execute([
            $nouveauMatricule, 
            $nom, 
            $prenoms, 
            $v['telephone'], 
            $v['quartier'], 
            $_SESSION['user_id']
        ]);

        $nouveau_id = $pdo->lastInsertId();

        // 4. Mettre à jour le statut du visiteur pour dire qu'il est devenu membre
        $update = $pdo->prepare("UPDATE visiteurs SET statut_suivi = 'Fidélisé' WHERE id = ?");
        $update->execute([$id]);

        $pdo->commit();
        
        // Redirection vers la fiche du nouveau membre pour compléter le reste des infos
        header("Location: ../membres/modifier.php?id=" . $nouveau_id . "&msg=conversion_ok");
        exit;
    }
} catch (Exception $e) {
    $pdo->rollBack();
    die("Erreur lors de la conversion : " . $e->getMessage());
}