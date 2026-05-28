<?php
// eglise_db/auth/login.php
require_once "../config/database.php";
session_start();

$message = "";

if (isset($_POST['login'])) {

    $email = $_POST['email'];
    $password = $_POST['password'];

    // On récupère l'utilisateur uniquement par email
    $sql = "SELECT u.*, r.nom_role FROM utilisateurs u JOIN roles r ON u.role_id = r.id WHERE u.email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);

    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['passwd'])) {
        
        // On stocke avec les clés exactes utilisées dans le layout (header/topbar)
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nom'] = $user['nom']; // Aligné avec la topbar
        $_SESSION['user_role_nom'] = $user['nom_role']; // Aligné avec la topbar
        $_SESSION['user_role_id'] = $user['role_id']; 

        header("Location: /gestion_eglise/dashboard/index.php");
        exit();
    } else {
        $message = "Email ou mot de passe incorrect";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/gestion_eglise/assets/css/login_style.css">
    <title>Connexion - Church manager</title>
</head>
<body>
    <div class="back">
        <?php if($message != "") echo "<p class='warning-msg'>$message</p>"; ?>
        <form method="POST">
            <h1 class="title-form">Connexion</h1>
            <div class="input-form">
                <input type="email" name="email" id="email" value="" placeholder=" " title="cliquez ici pour saisir votre email" required>
                <label for="email">E-mail</label>
            </div>

            <div class="input-form">
                <input type="password" name="password" id="password" value="" placeholder=" " title="cliquez ici pour saisir votre mot de passe" required>
                <label for="password">Mot de passe</label>
            </div>

            <div class="submit-form">
                <button type="submit" name="login">Se connecter</button>
            </div>
        </form>
    </div>
</body>
</html>