<?php
header('Content-Type: application/json');

class DatabaseHandler {
    private $pdo;
    
    public function __construct() {
        $host = 'localhost';
        $dbname = 'contacts_db';
        $username = 'root';
        $password = '';
        
        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
        } catch(PDOException $e) {
            try {
                $this->pdo = new PDO("mysql:host=$host", $username, $password);
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $this->pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
                $this->pdo->exec("USE $dbname");
                
            } catch(PDOException $e2) {
                die(json_encode([
                    'success' => false, 
                    'message' => 'Ошибка подключения к базе данных: ' . $e2->getMessage()
                ]));
            }
        }
        $this->createTable();
    }
    
    private function createTable() {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS contacts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }
    
    public function checkRecentSubmission($email, $phone) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM contacts 
            WHERE (email = :email OR phone = :phone)
            AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ");
        
        $stmt->execute([
            ':email' => $email,
            ':phone' => $phone
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
    
    public function getDuplicateField($email, $phone) {
        $stmt = $this->pdo->prepare("
            SELECT 
                CASE 
                    WHEN email = :email THEN 'email' 
                    WHEN phone = :phone THEN 'phone'
                END as duplicate_field
            FROM contacts 
            WHERE (email = :email OR phone = :phone)
            AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            LIMIT 1
        ");
        
        $stmt->execute([
            ':email' => $email,
            ':phone' => $phone
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['duplicate_field'] ?? null;
    }
    
    public function insertContact($name, $email, $phone) {
        $stmt = $this->pdo->prepare("
            INSERT INTO contacts (name, email, phone) 
            VALUES (:name, :email, :phone)
        ");
        
        return $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone
        ]);
    }
}

class FormValidator {
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function validatePhone($phone) {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        return strlen($cleaned) >= 10;
    }
    
    public static function validateName($name) {
        return !empty(trim($name)) && strlen(trim($name)) >= 2;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    $response = ['success' => false, 'message' => ''];
    
    if (!FormValidator::validateName($name)) {
        $response['message'] = 'Пожалуйста, введите корректное имя (минимум 2 символа).';
        echo json_encode($response);
        exit;
    }
    
    if (!FormValidator::validateEmail($email)) {
        $response['message'] = 'Пожалуйста, введите корректный email.';
        echo json_encode($response);
        exit;
    }
    
    if (!FormValidator::validatePhone($phone)) {
        $response['message'] = 'Пожалуйста, введите корректный номер телефона (минимум 10 цифр).';
        echo json_encode($response);
        exit;
    }
    
    try {
        $db = new DatabaseHandler();
        
        if ($db->checkRecentSubmission($email, $phone)) {
            $duplicateField = $db->getDuplicateField($email, $phone);
            
            switch ($duplicateField) {
                case 'email':
                    $response['message'] = 'Заявка с таким email уже была отправлена в последние 5 минут.';
                    break;
                case 'phone':
                    $response['message'] = 'Заявка с таким номером телефона уже была отправлена в последние 5 минут.';
                    break;
                default:
                    $response['message'] = 'Вы уже отправляли заявку с этими данными в последние 5 минут.';
            }
            
            echo json_encode($response);
            exit;
        }
        
        if ($db->insertContact($name, $email, $phone)) {
            $response['success'] = true;
            $response['message'] = '✅ Заявка успешно отправлена! Мы свяжемся с вами в ближайшее время.';
        } else {
            $response['message'] = '❌ Произошла ошибка при сохранении данных.';
        }
        
    } catch (Exception $e) {
        $response['message'] = '❌ Произошла ошибка при обработке запроса: ' . $e->getMessage();
    }
    
    echo json_encode($response);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
}
?>