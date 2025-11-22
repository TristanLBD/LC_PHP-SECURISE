<?php

require_once __DIR__ . '/CashRegister.php';
require_once __DIR__ . '/CashValue.php';

class CashRegisterRepository {
    private Database $db;
    private int $cashierId;

    public function __construct(Database $db, int $cashierId = 1) {
        $this->db = $db;
        $this->cashierId = $cashierId;
    }

    public function load(): CashRegister {
        $sql = "
            SELECT value_cents, value_quantity, value_name
            FROM valeurs
            WHERE cashier_id = :id
            ORDER BY value_cents DESC
        ";

        $rows = $this->db->fetchAll($sql, [':id' => $this->cashierId]);

        $quantities = [];
        $values = [];

        foreach ($rows as $row) {
            $cents = (int)$row['value_cents'];
            $qty = (int)($row['value_quantity'] ?? $row['quantity'] ?? 0);
            $label = (string)($row['value_name'] ?? $row['label'] ?? '');

            $quantities[$cents] = $qty;
            $values[] = new CashValue($cents, $label);
        }

        return new CashRegister($quantities, $values);
    }

    public function updateQuantities(CashRegister $register): void {
        $sql = "UPDATE valeurs
                SET value_quantity = :qty
                WHERE cashier_id = :cashier_id
                AND value_cents = :value_cents";

        $this->db->beginTransaction();

        try {
            foreach ($register->getQuantities() as $valueCents => $qty) {
                $this->db->request($sql, [
                    'qty' => $qty,
                    'cashier_id' => $this->cashierId,
                    'value_cents' => $valueCents
                ]);
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
