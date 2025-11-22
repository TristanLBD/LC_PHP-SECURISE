<?php

class CashValue {
    private int $cents;
    private string $label;

    public function __construct(int $cents, string $label) {
        $this->cents = $cents;
        $this->label = $label;
    }

    public function getCents(): int {
        return $this->cents;
    }

    public function getLabel(): string {
        return $this->label;
    }

    public function getFloatValue(): float {
        return $this->cents / 100;
    }
}
