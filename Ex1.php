<?php
    require_once("./config.php");
    require_once("./DB.php");

    if(session_status() !== PHP_SESSION_ACTIVE) session_start();

    if(!isset($_SESSION['USER_ID'])) { header('Location: ./login.php'); }

    $DB = Database::getInstance();

    $response      = null;
    $errorMessage  = null;
    $cashRegister  = [];

    // Chargement de la caisse
    $SQL = "SELECT *
            FROM cashier
            JOIN valeurs ON valeurs.cashier_id = cashier.cashier_id
            WHERE cashier.cashier_id = :cashier_id";

    $result = $DB->fetchAll($SQL, [':cashier_id' => 1]);

    foreach ($result as $row) {
        $cashRegister[$row["value_cents"]] = $row["value_quantity"];
    }

    // Traitement du formulaire
    if($_SERVER['REQUEST_METHOD'] === 'POST') {

        $SommeDue        = sanytizeInputValue("PRICE_TO_PAY", FILTER_VALIDATE_FLOAT);
        $SommeRecue      = sanytizeInputValue("AMOUNT_CUSTOMER_GAVE", FILTER_VALIDATE_FLOAT);
        $PreferencesString = sanytizeInputValue("PREFERENCES", FILTER_SANITIZE_FULL_SPECIAL_CHARS, true);

        if ($SommeDue === null || $SommeRecue === null) {
            $errorMessage = "Les montants indiqués sont invalides.";
        } else {
            // Gestion des préférences
            $Preferences = [];
            if (!empty($PreferencesString)) {
                $Preferences = explode(",", str_replace(" ", "", $PreferencesString));
            }
            $sortedCashRegister = SortCashRegister($cashRegister, "DESC", $Preferences);

            /* Calcul du rendu */
            $workingCashRegister = $sortedCashRegister;
            $response = calculerRendu($SommeDue, $SommeRecue, $workingCashRegister, $errorMessage);

            // Si rendu valide => MAJ DB
            if ($response && empty($errorMessage)) {
                try {
                    $DB->beginTransaction();

                    $sql = "UPDATE valeurs
                            SET value_quantity = :qty
                            WHERE cashier_id = :cashier_id
                            AND value_cents = :value_cents";

                    foreach ($workingCashRegister as $valueCents => $qty) {
                        $DB->request($sql, [
                            "qty"         => $qty,
                            "cashier_id"  => 1,
                            "value_cents" => $valueCents
                        ]);
                    }

                    $DB->commit();

                    // Met à jour la caisse affichée à l'écran après transaction
                    $cashRegister = $workingCashRegister;
                } catch (\Exception $e) {

                    $DB->rollback();
                    $errorMessage = "Erreur lors de la mise à jour de la caisse : " . $e->getMessage();
                    $response = null;
                }
            }
        }
    }

    function SortCashRegister($cashRegister, $order = "DESC", $preferences = []) {
        $orderedCash = [];

        foreach ($preferences as $pref) {
            if (isset($cashRegister[$pref * 100])) {
                $orderedCash[$pref * 100] = $cashRegister[$pref * 100];
            }
        }

        $rest = [];
        foreach ($cashRegister as $amount => $qty) {
            if (!isset($orderedCash[$amount])) {
                $rest[$amount] = $qty;
            }
        }

        ($order === "ASC") ? ksort($rest) : krsort($rest);

        return $orderedCash + $rest;
    }

    function calculerRendu($MontantDue, $MontantPaye, &$cashRegister, &$errorMessage) {

        $MontantDueCents  = round($MontantDue * 100);
        $MontantPayeCents = round($MontantPaye * 100);
        $MontantARendre   = $MontantPayeCents - $MontantDueCents;

        if ($MontantARendre < 0) {
            $errorMessage = "Le montant reçu est inférieur au montant dû.";
            return [];
        }

        $result = [
            "Montant du"   => $MontantDue,
            "Montant payé" => $MontantPaye,
            "A rendre"     => $MontantARendre / 100,
            "Rendu"        => []
        ];

        foreach ($cashRegister as $BillAmount => $BillQuantity) {

            if ($BillQuantity <= 0) continue;
            if ($MontantARendre <= 0) break;

            $NumberOfBillNeeded = floor($MontantARendre / $BillAmount);
            $NumberOfBillsToUse = min($NumberOfBillNeeded, $BillQuantity);

            if ($NumberOfBillsToUse <= 0) continue;

            $result["Rendu"][$BillAmount / 100] = $NumberOfBillsToUse;
            $cashRegister[$BillAmount] -= $NumberOfBillsToUse;

            $MontantARendre -= $NumberOfBillsToUse * $BillAmount;
        }

        if ($MontantARendre > 0) {
            $errorMessage = "Impossible de rendre la monnaie : il manque " . ($MontantARendre / 100) . " €.";
            return [];
        }

        return $result;
    }

    function sanytizeInputValue($PostValueName, $ValidationType, $CanBeNull = false) {
        $rawData = $_POST[$PostValueName] ?? null;
        $rawData = str_replace(',', '.', $rawData);
        $rawData = filter_var($rawData, $ValidationType);

        if (!$CanBeNull && ($rawData === false || $rawData === null)) {
            return null;
        }
        return $rawData;
    }
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

    <h1 class="text-center mb-4">🤑 Caisse enregistreuse (ID : <?= $_SESSION['USER_ID'] ?> ) 🤑</h1>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?= $errorMessage ?></div>
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
                        <input type="number" step="0.01" name="PRICE_TO_PAY" class="form-control" value="<?= $_POST['PRICE_TO_PAY'] ?? '' ?>">

                        <label class="form-label mt-3">Somme reçue</label>
                        <input type="number" step="0.01" name="AMOUNT_CUSTOMER_GAVE" class="form-control" value="<?= $_POST['AMOUNT_CUSTOMER_GAVE'] ?? '' ?>">

                        <label class="form-label mt-3">Préférences (ex: 20,10,5)</label>
                        <input type="text" name="PREFERENCES" class="form-control" value="<?= $_POST['PREFERENCES'] ?? '' ?>">

                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-lg">Valider</button>

            </div>

            <!-- Caisse -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-dark text-white">Contenu de la caisse</div>
                    <div class="card-body">
                        <?php foreach ($result as $row): ?>
                            <label class="form-label"><?= $row['value_name'] ?></label>
                            <input type="number" class="form-control mb-2" name="MONEY_<?= $row['value_cents'] ?>" value="<?= $cashRegister[$row['value_cents']] ?>" min="0">
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

                <p><strong>Somme due :</strong> <?= $response["Montant du"] ?> €</p>
                <p><strong>Somme reçue :</strong> <?= $response["Montant payé"] ?> €</p>
                <p><strong>A rendre :</strong> <?= $response["A rendre"] ?> €</p>

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
                            <td><?= $valeur ?> €</td>
                            <td><?= $qte ?></td>
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
