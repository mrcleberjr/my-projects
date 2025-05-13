<?php
session_start();
require_once 'conexao.php';

class Auth {
    private $conn;
    private const PASSWORD_PEPPER = "sua_pepper_secreta_aqui"; // Adicione uma string aleatória segura
    
    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método não permitido'], 405);
            return;
        }

        $action = $_POST['action'] ?? '';
        
        try {
            switch ($action) {
                case 'login':
                    $this->handleLogin();
                    break;
                case 'register':
                    $this->handleRegister();
                    break;
                default:
                    $this->jsonResponse(['error' => 'Ação inválida'], 400);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Erro interno do servidor'], 500);
            error_log($e->getMessage());
        }
    }

    private function handleLogin() {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            $this->jsonResponse(['error' => 'Dados incompletos'], 400);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->jsonResponse(['error' => 'E-mail inválido'], 400);
            return;
        }

        try {
            $stmt = $this->conn->prepare("SELECT id, password, status FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if (!$user) {
                $this->jsonResponse(['error' => 'Credenciais inválidas'], 401);
                return;
            }

            if ($user['status'] !== 'active') {
                $this->jsonResponse(['error' => 'Conta inativa'], 403);
                return;
            }

            if (!$this->verifyPassword($password, $user['password'])) {
                $this->jsonResponse(['error' => 'Credenciais inválidas'], 401);
                return;
            }

            // Gerar novo token de sessão
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['last_activity'] = time();
            $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];

            $this->jsonResponse(['success' => true, 'message' => 'Login realizado com sucesso']);

        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Erro ao processar login'], 500);
            error_log("Erro no login: " . $e->getMessage());
        }
    }

    private function handleRegister() {
        // Validar e sanitizar inputs
        $inputs = $this->validateRegistrationInputs($_POST);
        if (isset($inputs['error'])) {
            $this->jsonResponse($inputs, 400);
            return;
        }

        try {
            // Verificar se e-mail ou CPF já existem
            if ($this->checkExistingUser($inputs['email'], $inputs['cpf'])) {
                $this->jsonResponse(['error' => 'E-mail ou CPF já cadastrado'], 409);
                return;
            }

            // Hash da senha
            $hashedPassword = $this->hashPassword($inputs['password']);

            // Inserir novo usuário
            $stmt = $this->conn->prepare("
                INSERT INTO users (name, nickname, cpf, email, password, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'active', NOW())
            ");

            $stmt->bind_param("sssss", 
                $inputs['name'],
                $inputs['nickname'],
                $inputs['cpf'],
                $inputs['email'],
                $hashedPassword
            );

            if (!$stmt->execute()) {
                throw new Exception("Erro ao inserir usuário");
            }

            $this->jsonResponse([
                'success' => true,
                'message' => 'Registro realizado com sucesso'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Erro ao processar registro'], 500);
            error_log("Erro no registro: " . $e->getMessage());
        }
    }

    private function validateRegistrationInputs($data) {
        $required = ['name', 'nickname', 'cpf', 'email', 'password'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['error' => "Campo $field é obrigatório"];
            }
        }

        // Validar e-mail
        $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'E-mail inválido'];
        }

        // Validar CPF
        $cpf = preg_replace('/[^0-9]/', '', $data['cpf']);
        if (!$this->validateCPF($cpf)) {
            return ['error' => 'CPF inválido'];
        }

        // Validar senha
        if (strlen($data['password']) < 8) {
            return ['error' => 'Senha deve ter no mínimo 8 caracteres'];
        }

        return [
            'name' => htmlspecialchars(strip_tags($data['name'])),
            'nickname' => htmlspecialchars(strip_tags($data['nickname'])),
            'cpf' => $cpf,
            'email' => $email,
            'password' => $data['password']
        ];
    }

    private function validateCPF($cpf) {
        // Implementar validação completa de CPF
        if (strlen($cpf) != 11) {
            return false;
        }
        
        // Verificar se todos os dígitos são iguais
        if (preg_match('/^(\d)\1*$/', $cpf)) {
            return false;
        }

        // Validar dígitos verificadores
        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$t] != $d) {
                return false;
            }
        }
        return true;
    }

    private function hashPassword($password) {
        return password_hash($password . self::PASSWORD_PEPPER, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }

    private function verifyPassword($password, $hash) {
        return password_verify($password . self::PASSWORD_PEPPER, $hash);
    }

    private function checkExistingUser($email, $cpf) {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ? OR cpf = ?");
        $stmt->bind_param("ss", $email, $cpf);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

// Uso
try {
    $auth = new Auth($conn);
    $auth->handleRequest();
} catch (Exception $e) {
    http_response_code(500);
    error_log("Erro crítico: " . $e->getMessage());
    echo json_encode(['error' => 'Erro interno do servidor']);
}
