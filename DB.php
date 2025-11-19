<?php

class Database {
    private $inTransaction = false;
    private $rowCount;
    private $errorLogMessage;
    private $db;

    // Propriété statique pour stocker les informations SQL
    private static $lastSqlError = null;

    // Instance singleton
    private static $instance = null;

    /**
     * Constructeur privé de la classe Database (Singleton)
     */
    private function __construct() {
        try {
            $connectOptions = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
            $this->db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';port='.DB_PORT, DB_USER, DB_PASS, $connectOptions);
        } catch(PDOException $e) {
            throw new PDOException("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }

    /**
     * Méthode pour obtenir l'instance singleton
     *
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Empêcher le clonage de l'instance
     */
    private function __clone() {}

    /**
     * Empêcher la désérialisation de l'instance
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }

    /**
     * Exécute une requête SQL
     *
     * @param string $sql
     * @param array $data
     * @return PDOStatement
     * @throws Exception
     */
    private function executeQuery($sql, $data = []) {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($data);
            $this->rowCount = $stmt->rowCount();
            return $stmt;
        } catch (PDOException $e) {
            $this->rollback();

            // Récupérer les informations de l'appelant
            $trace = $e->getTrace();

            // Rechercher le premier appelant qui n'est pas dans la classe Database
            $caller = null;
            $skipClasses = ['Database', 'PDO', 'PDOStatement'];

            foreach ($trace as $t) {
                if (!isset($t['class']) || !in_array($t['class'], $skipClasses)) {
                    $caller = $t;
                    break;
                }
            }

            if (!$caller && !empty($trace)) {
                $caller = $trace[0];
            }

            // Construire la requête avec les paramètres interpolés pour le log
            $SQLAvecParams = strtr($sql, array_combine(
                array_keys($data),
                array_map(function ($value) {
                    return is_null($value) ? 'NULL' : (is_numeric($value) ? $value : "'" . addslashes($value) . "'");
                }, $data)
            ));

            // Construire le message d'erreur détaillé pour le log
            $errorDetails = [
                "sql_error" => true,
                "message" => $e->getMessage(),
                "sql_original" => $sql,
                "sql_params" => $data,
                "sql_complete" => $SQLAvecParams,
                "sql_code" => $e->getCode(),
                "file" => $caller['file'] ?? $e->getFile(),
                "line" => $caller['line'] ?? $e->getLine(),
                "function" => $caller['function'] ?? 'Inconnu',
                "class" => $caller['class'] ?? 'Inconnu',
                "trace" => $e->getTraceAsString()
            ];

            // Stocker les détails pour récupération ultérieure
            $this->errorLogMessage = json_encode($errorDetails, JSON_PRETTY_PRINT);
            self::$lastSqlError = $errorDetails;

            // Propager l'exception avec un message plus clair
            throw new PDOException("Erreur SQL: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Récupère une seule ligne
     *
     * @param string $sql
     * @param array $data
     * @return array|false
     */
    public function fetch($sql, $data = []) {
        return $this->executeQuery($sql, $data)->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère toutes les lignes
     *
     * @param string $sql
     * @param array $data
     * @return array
     */
    public function fetchAll($sql, $data = []) {
        return $this->executeQuery($sql, $data)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Exécute une requête sans retour
     *
     * @param string $sql
     * @param array $data
     * @return bool
     */
    public function request($sql, $data = []) {
        return $this->executeQuery($sql, $data);
    }

    /**
     * Retourne le nombre de lignes affectées
     *
     * @return int
     */
    public function rowCount() {
        return $this->rowCount;
    }

    /**
     * Retourne l'ID de la dernière insertion
     *
     * @return string
     */
    public function getLastInsertedID() {
        return $this->db->lastInsertId();
    }

    /**
     * Démarre une transaction
     *
     * @return bool
     */
    public function beginTransaction() {
        if (!$this->inTransaction) {
            $this->inTransaction = $this->db->beginTransaction();
        }
        return $this->inTransaction;
    }

    /**
     * Valide une transaction
     *
     * @return bool
     */
    public function commit() {
        if ($this->inTransaction) {
            $this->inTransaction = false;
            return $this->db->commit();
        }
        return false;
    }

    /**
     * Annule une transaction
     *
     * @return bool
     */
    public function rollback() {
        if ($this->inTransaction) {
            $this->inTransaction = false;
            return $this->db->rollBack();
        }
        return false;
    }

    /**
     * Récupère le message d'erreur détaillé
     *
     * @return string
     */
    public function getErrorLogMessage() :String {
        return $this->errorLogMessage;
    }

    /**
     * Récupère les détails de la dernière erreur SQL
     *
     * @return array|null
     */
    public static function getLastSqlError() {
        return self::$lastSqlError;
    }

    /**
     * Ferme la connexion à la base de données et réinitialise l'instance singleton
     *
     * @return void
     */
    public function close() {
        if ($this->db) {
            $this->db = null;
        }
        self::$instance = null;
    }

    /**
     * Méthode statique pour fermer l'instance singleton
     *
     * @return void
     */
    public static function closeInstance() {
        if (self::$instance !== null) {
            self::$instance->close();
        }
    }

    /**
     * Destructeur pour fermer automatiquement la connexion
     */
    public function __destruct() {
        $this->close();
    }
}
