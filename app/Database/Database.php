<?php

    namespace App\Database;

    use PDO;
    use PDOException;

    class Database
    {
        protected const DEFAULT_CONFIG = [
            'host' => 'localhost',
            'user' => 'root',
            'password' => null,
            'database' => 'films'
        ];

        protected static ?Database $instance = null;        //? azt jelenti hogy lehet null is
        private PDO $pdo;

        public function __construct(array $config){
            $host = $config['host'] ?? self::DEFAULT_CONFIG['host'];
            $user = $config['user'] ?? self::DEFAULT_CONFIG['user'];
            $password = $config['password'] ?? self::DEFAULT_CONFIG['password'];
            $database = $config['database'] ?? null;

            try{
                $dsn = "mysql:host=$host;charset=utf8mb4";

                if ($database !== null) {
                    $dsn .= ";dbname=$database";
                }
                $this->pdo = new PDO($dsn, $user, $password,[
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        //Enable exceptio mode
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       //Fetch as associated
                    PDO::ATTR_EMULATE_PREPARES => false,                    //Use real prepared smth (emulators)
                ]);
            }
            catch (PDOException $e){
                error_log($e->getMessage());
                throw new \RuntimeException("Database connection error.");
            }
        }

        public static function getInstance(array $config = []): Database
        {
            if (self::$instance === null){
                self::$instance = new self($config);
            }
            return self::$instance;
        }

        public function getPdo(): PDO
        {
            return $this->pdo;
        }

        public function execSql(string $sql, array $params = []): bool|int|array
        {
            try{
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);

                //Handle INSERT (return last insert ID)
                if (str_starts_with(strtoupper(trim($sql)), 'INSERT')){
                    return (int) $this->pdo->lastInsertId();
                }

                //Handle SELECT (return results)
                if (str_starts_with(strtoupper(trim($sql)), 'SELECT')) {
                    return $stmt->fetchAll() ?: [];
                }

                //Handle UPDATE / DELETE
                return $stmt->rowCount() > 0;
            } catch (PDOException $e){
                $_SESSION['error_message'] = $e->getMessage();
                error_log($e->getMessage());
                return false;
            }
        }

        public function beginTransaction(): bool
        {
            return $this->pdo->beginTransaction();
        }
        public function commit(): bool
        {
            return $this->pdo->commit();
        }
        public function rollback(): bool
        {
            return $this->pdo->rollback();
        }
    }