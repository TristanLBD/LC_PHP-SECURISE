<?php

require_once __DIR__ . '/ChangeCalculator.php';
require_once __DIR__ . '/InputSanitizer.php';
require_once __DIR__ . '/CashRegister.php';
require_once __DIR__ . '/CashValue.php';
require_once __DIR__ . '/CashRegisterRepository.php';

class CashController {
    public ?string $error = null;
    public ?array $response = null;

    public CashRegister $cashRegister;
    private CashRegisterRepository $repo;
    private ChangeCalculator $calculator;

    public function __construct(CashRegisterRepository $repo) {
        $this->repo = $repo;
        $this->calculator = new ChangeCalculator();
        $this->cashRegister = $this->repo->load();
    }

    public function handleForm(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $due = InputSanitizer::get("PRICE_TO_PAY", FILTER_VALIDATE_FLOAT);
        $paid = InputSanitizer::get("AMOUNT_CUSTOMER_GAVE", FILTER_VALIDATE_FLOAT);
        $prefString = InputSanitizer::get("PREFERENCES", FILTER_SANITIZE_FULL_SPECIAL_CHARS, true);

        if ($due === null || $paid === null) {
            $this->error = "Les montants indiqués sont invalides.";
            return;
        }

        $preferences = [];
        if (!empty($prefString)) {
            $array = explode(',', $prefString);
            $array = array_map('trim', $array);
            $preferences = array_filter($array, fn($v) => $v !== '');
        }

        $sorted = $this->cashRegister->sort($preferences, "DESC");
        $working = $sorted;

        $this->response = $this->calculator->calculate($due, $paid, $working, $this->error);

        if ($this->response && empty($this->error)) {
            // Si rendu ok : remplacer quantités dans l'objet cashRegister puis sauvegarder
            $this->cashRegister->replaceQuantities($working);

            try {
                $this->repo->updateQuantities($this->cashRegister);
            } catch (\Exception $e) {
                $this->error = "Erreur lors de la mise à jour de la caisse : " . $e->getMessage();
                $this->response = null;
            }
        }
    }
}
