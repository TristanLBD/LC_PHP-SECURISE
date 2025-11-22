<?php
// ChangeCalculator.php

class ChangeCalculator {
    /**
     * Calcule le rendu.
     *
     * @param float $due montant dû en euros (ex: 12.50)
     * @param float $paid montant payé en euros
     * @param array<int,int> &$cashRegister tableau [value_cents => qty] passés par référence (sera modifié)
     * @param string|null &$error
     * @return array résultat avec clefs Montant du, Montant payé, A rendre, Rendu (assoc value=>qty)
     */
    public function calculate(float $due, float $paid, array &$cashRegister, ?string &$error = null): array {
        $dueCents = (int)round($due * 100);
        $paidCents = (int)round($paid * 100);
        $toReturn = $paidCents - $dueCents;

        if ($toReturn < 0) {
            $error = "Le montant reçu est inférieur au montant dû.";
            return [];
        }

        $result = [
            "Montant du" => $due,
            "Montant payé" => $paid,
            "A rendre" => $toReturn / 100,
            "Rendu" => []
        ];

        foreach ($cashRegister as $billValue => $billQty) {
            if ($billQty <= 0) continue;
            if ($toReturn <= 0) break;

            $needed = (int)floor($toReturn / $billValue);
            $use = min($needed, $billQty);

            if ($use <= 0) continue;

            $result['Rendu'][$billValue / 100] = $use;
            $cashRegister[$billValue] -= $use;
            $toReturn -= $use * $billValue;
        }

        if ($toReturn > 0) {
            $error = "Impossible de rendre la monnaie : il manque " . ($toReturn / 100) . " €.";
            return [];
        }

        return $result;
    }
}
