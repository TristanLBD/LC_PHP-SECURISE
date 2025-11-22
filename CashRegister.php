<?php

require_once __DIR__ . '/CashValue.php';

class CashRegister {
    private array $quantities = [];
    private array $values = [];

    public function __construct(array $initialQuantities = [], array $values = []) {
        $this->quantities = $initialQuantities;
        $this->values = $values;
    }

    public function getQuantities(): array {
        return $this->quantities;
    }

    public function getCashValues(): array {
        return $this->values;
    }

    public function setValues(array $values): void {
        $this->values = $values;
    }

    public function setQuantity(int $valueCents, int $qty): void {
        $this->quantities[$valueCents] = $qty;
    }

    public function getQuantity(int $valueCents): int {
        return $this->quantities[$valueCents] ?? 0;
    }

    public function sort(array $preferences = [], string $order = "DESC"): array {
        $ordered = [];
        foreach ($preferences as $pref) {
            if (!is_numeric($pref)) { continue; }
            $cent = (int) round(((float)$pref) * 100);

            if (isset($this->quantities[$cent])) {
                $ordered[$cent] = $this->quantities[$cent];
            }
        }

        $remaining = array_diff_key($this->quantities, $ordered);
        ($order === "ASC") ? ksort($remaining) : krsort($remaining);
        return $ordered + $remaining;
    }

    public function replaceQuantities(array $newQuantities): void {
        $this->quantities = $newQuantities;
    }
}
