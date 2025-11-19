<?php
    require_once("./config.php");
    require_once("./DB.php");

    $ErrorMessage = "";

    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        if(isset($_POST["USERNAME"], $_POST["PASSWORD"])) {
            $EMAIL = $_POST["USERNAME"];
            $PASS = $_POST["PASSWORD"];

            $DB = Database::getInstance();
            $SQL = "SELECT * FROM users WHERE email = :email AND password = :pass;";
            $result = $DB->fetch($SQL, [':email' => $EMAIL, ':pass' => $PASS]);

            if($result) {
                if(session_status() === PHP_SESSION_ACTIVE) { session_destroy(); }
                session_start();

                $_SESSION['USER_ID'] = $result['id'];
                $_SESSION['USER_ROLE'] = $result['role'];

                header("Location: ./Ex1.php");
                die();
            } else {
                die("Aucun user pour : $EMAIL (MDP : $PASS)");
            }

        } else {
            $ErrorMessage = "Veuillez renseigner USERNAME + MDP";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h1 class="text-center"><?= $ErrorMessage; ?></h1>
    <div class="container">
        <div class="row">
            <div class="col-6">
                <form method="post">
                    <label for="USERNAME" class="form-label mt-2">Username :</label>
                    <input type="text" name="USERNAME" class="form-control">

                    <label for="PASSWORD" class="form-label mt-2">Password :</label>
                    <input type="text" name="PASSWORD" class="form-control">

                    <button type="submit">Valider</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
