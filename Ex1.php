<?php

require_once './config.php';
require_once './DB.php';

require_once './CashValue.php';
require_once './CashRegister.php';
require_once './CashRegisterRepository.php';
require_once './ChangeCalculator.php';
require_once './InputSanitizer.php';
require_once './CashController.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['USER_ID'])) {
    header('Location: ./login.php');
    exit;
}

$db = Database::getInstance();
$repo = new CashRegisterRepository($db, 1);
$controller = new CashController($repo);

$controller->handleForm();

$errorMessage = $controller->error;
$response = $controller->response;
$cashRegister = $controller->cashRegister;
$quantities = $cashRegister->getQuantities();
$cashValues = $cashRegister->getCashValues();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caisse enregistreuse</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>

<body class="bg-light">

<div class="container py-5">

    <h1 class="text-center mb-4">🤑 Caisse enregistreuse (ID : <?= htmlspecialchars($_SESSION['USER_ID']) ?> ) 🤑</h1>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <?php if ($response && empty($errorMessage)): ?>
        <div class="alert alert-success">Rendu calculé avec succès !</div>
    <?php endif; ?>

    <form method="post">
        <div class="row">
            <!-- Formulaire -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">Paiement</div>
                    <div class="card-body">

                        <label class="form-label mt-2">Somme due</label>
                        <input type="number" step="0.01" name="PRICE_TO_PAY" class="form-control" value="<?= InputSanitizer::PostIssetEscape('PRICE_TO_PAY') ?>">

                        <label class="form-label mt-3">Somme reçue</label>
                        <input type="number" step="0.01" name="AMOUNT_CUSTOMER_GAVE" class="form-control" value="<?= InputSanitizer::PostIssetEscape('AMOUNT_CUSTOMER_GAVE') ?>">

                        <label class="form-label mt-3">Préférences (ex: 20,10,5)</label>
                        <input type="text" name="PREFERENCES" class="form-control" value="<?= InputSanitizer::PostIssetEscape('PREFERENCES') ?>">

                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-lg">Valider</button>

            </div>

            <!-- Caisse -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-dark text-white">Contenu de la caisse</div>
                    <div class="card-body">
                        <?php foreach ($cashValues as $valueObj): ?>
                            <?php $cents = $valueObj->getCents(); ?>
                            <label class="form-label"><?= InputSanitizer::e($valueObj->getLabel()) ?></label>
                            <input type="number"
                            class="form-control mb-2"
                            name="MONEY_<?= $cents ?>"
                            value="<?= InputSanitizer::e((string)($quantities[$cents] ?? 0)) ?>" min="0">
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <?php if ($response && empty($errorMessage)): ?>

        <div class="card mt-5">
            <div class="card-header bg-success text-white">
                Rendu à donner au client
            </div>
            <div class="card-body">

                <p><strong>Somme due :</strong> <?= htmlspecialchars((string)$response["Montant du"]) ?> €</p>
                <p><strong>Somme reçue :</strong> <?= htmlspecialchars((string)$response["Montant payé"]) ?> €</p>
                <p><strong>A rendre :</strong> <?= htmlspecialchars((string)$response["A rendre"]) ?> €</p>

                <h5 class="mt-4">Détail du rendu :</h5>

                <table class="table table-striped mt-2">
                    <thead>
                    <tr>
                        <th>Valeur</th>
                        <th>Quantité</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($response["Rendu"] as $valeur => $qte): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$valeur) ?> €</td>
                            <td><?= htmlspecialchars((string)$qte) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

            </div>
        </div>

    <?php endif; ?>

</div>

</body>
</html>
