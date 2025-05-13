<?php
// Configurações em arquivo separado
require_once 'config.php';

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $this->connection = new mysqli(
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME
            );

            if ($this->connection->connect_error) {
                throw new Exception("Erro na conexão: " . $this->connection->connect_error);
            }

            // Configura charset e collation
            $this->connection->set_charset("utf8mb4");
            $this->connection->query("SET NAMES utf8mb4");
            $this->connection->query("SET collation_connection = utf8mb4_unicode_ci");
            
            // Configura timezone
            $this->connection->query("SET time_zone = '-03:00'");
        } catch (Exception $e) {
            error_log("Erro de conexão: " . $e->getMessage());
            throw new Exception("Erro ao conectar ao banco de dados");
        }
    }

    // Implementa Singleton pattern
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    // Método para queries preparadas
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }

    // Método para escapar strings
    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }

    // Fecha a conexão explicitamente se necessário
    public function closeConnection() {
        if ($this->connection) {
            $this->connection->close();
            self::$instance = null;
        }
    }

    // Previne clonagem do objeto
    private function __clone() {}
    
    // Previne desserialização
    private function __wakeup() {}
}

// Arquivo config.php separado:
/*
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'usuario_seguro');
define('DB_PASS', 'senha_forte_aqui');
define('DB_NAME', 'apalette');

// Ativa exibição de erros em desenvolvimento
if ($_SERVER['SERVER_NAME'] === 'localhost') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    error_reporting(0);
}
*/

// Exemplo de uso:
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Exemplo de query preparada
    $stmt = $db->prepare("SELECT * FROM tabela WHERE id = ?");
    $id = 1;
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Processa resultados
    while ($row = $result->fetch_assoc()) {
        // Processa cada linha
    }

    $stmt->close();
} catch (Exception $e) {
    // Log do erro em arquivo
    error_log($e->getMessage());
    // Mensagem genérica para o usuário
    echo "Ocorreu um erro ao processar sua requisição.";
}
