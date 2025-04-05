<?php
class User {
    private $conn;
    private $table = "users";

    // Propriétés
    public $id;
    public $first_name;
    public $last_name;
    public $email;
    public $password;
    public $phone;
    public $role;
    public $created_at;
    public $verified;
    public $email_verification_code;
    public $verification_code_expires;

    public function __construct($db) {
        $this->conn = $db;
        $this->createVerificationColumns();
    }

    // Créer les colonnes de vérification si elles n'existent pas
    private function createVerificationColumns() {
        try {
            // Vérifier si la colonne verified existe
            $check_verified = "SHOW COLUMNS FROM " . $this->table . " LIKE 'verified'";
            $stmt = $this->conn->prepare($check_verified);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                $this->conn->exec("ALTER TABLE " . $this->table . " ADD COLUMN verified TINYINT(1) DEFAULT 0");
            }

            // Vérifier si la colonne email_verification_code existe
            $check_code = "SHOW COLUMNS FROM " . $this->table . " LIKE 'email_verification_code'";
            $stmt = $this->conn->prepare($check_code);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                $this->conn->exec("ALTER TABLE " . $this->table . " ADD COLUMN email_verification_code VARCHAR(255) DEFAULT NULL");
            }

            // Vérifier si la colonne verification_code_expires existe
            $check_expires = "SHOW COLUMNS FROM " . $this->table . " LIKE 'verification_code_expires'";
            $stmt = $this->conn->prepare($check_expires);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                $this->conn->exec("ALTER TABLE " . $this->table . " ADD COLUMN verification_code_expires DATETIME DEFAULT NULL");
            }
        } catch (PDOException $e) {
            error_log("Erreur lors de la création des colonnes de vérification : " . $e->getMessage());
        }
    }

    // Créer un nouvel utilisateur
    public function create() {
        // Générer un code de vérification de 10 caractères
        $this->email_verification_code = substr(bin2hex(random_bytes(5)), 0, 10);
        $this->verification_code_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $this->verified = 0;

        error_log("Création d'un nouvel utilisateur avec le code de vérification: " . $this->email_verification_code);

        $query = "INSERT INTO " . $this->table . " 
                  SET first_name = :first_name,
                      last_name = :last_name,
                      email = :email,
                      password = :password,
                      phone = :phone,
                      role = :role,
                      verified = :verified,
                      email_verification_code = :verification_code,
                      verification_code_expires = :verification_expires";
        $stmt = $this->conn->prepare($query);

        // Nettoyer les données
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->role = htmlspecialchars(strip_tags($this->role));

        // Liaison des paramètres
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":verified", $this->verified);
        $stmt->bindParam(":verification_code", $this->email_verification_code);
        $stmt->bindParam(":verification_expires", $this->verification_code_expires);

        try {
            $result = $stmt->execute();
            if (!$result) {
                error_log("Erreur lors de la création de l'utilisateur: " . print_r($stmt->errorInfo(), true));
            } else {
                error_log("Utilisateur créé avec succès. ID: " . $this->conn->lastInsertId());
                error_log("Code de vérification: " . $this->email_verification_code);
                error_log("Date d'expiration: " . $this->verification_code_expires);
            }
            return $result;
        } catch (PDOException $e) {
            error_log("Exception lors de la création de l'utilisateur: " . $e->getMessage());
            return false;
        }
    }

    // Vérifier le code de vérification
    public function verifyEmailCode($code) {
        error_log("Tentative de vérification du code: " . $code);
        
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE email_verification_code = :code 
                  AND verification_code_expires > NOW() 
                  AND verified = 0 
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":code", $code);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Code de vérification valide trouvé pour l'utilisateur: " . $row['email']);
            $this->id = $row['id'];
            $this->email = $row['email'];
            return $row;
        } else {
            error_log("Aucun utilisateur trouvé avec ce code de vérification");
            return false;
        }
    }

    // Marquer l'utilisateur comme vérifié
    public function markAsVerified($user_id) {
        try {
            $query = "UPDATE " . $this->table . " 
                      SET verified = 1, 
                          email_verification_code = NULL, 
                          verification_code_expires = NULL 
                      WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            
            $result = $stmt->execute();
            if (!$result) {
                error_log("Erreur SQL lors de la vérification de l'email: " . print_r($stmt->errorInfo(), true));
            }
            return $result;
        } catch (PDOException $e) {
            error_log("Exception lors de la vérification de l'email: " . $e->getMessage());
            return false;
        }
    }

    // Vérifier si l'email est vérifié
    public function isEmailVerified($email) {
        $query = "SELECT verified FROM " . $this->table . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $row['verified'] == 1;
        }
        return false;
    }

    // Renvoyer le code de vérification
    public function resendVerificationCode() {
        $this->email_verification_code = bin2hex(random_bytes(16));
        $this->verification_code_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $query = "UPDATE " . $this->table . " 
                  SET email_verification_code = :code,
                      verification_code_expires = :expires
                  WHERE email = :email AND verified = 0";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":code", $this->email_verification_code);
        $stmt->bindParam(":expires", $this->verification_code_expires);
        $stmt->bindParam(":email", $this->email);
        
        return $stmt->execute();
    }

    // Vérifier si l'email existe déjà
    public function emailExists() {
        $query = "SELECT id, first_name, last_name, email, password, role 
                  FROM " . $this->table . " 
                  WHERE email = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->email = $row['email'];
            $this->password = $row['password'];
            $this->role = $row['role'];
            return true;
        }
        return false;
    }

    // Login utilisateur
    public function login($email, $password) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (password_verify($password, $row['password'])) {
                return $row;
            }
        }
        return false;
    }

    // Mise à jour du code de vérification
    public function updateVerificationCode() {
        $query = "UPDATE " . $this->table . " SET email_verification_code = :code WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':code', $this->email_verification_code);
        $stmt->bindParam(':email', $this->email);
        return $stmt->execute();
    }

    // Vérifier le code de vérification
    public function verifyCode() {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE email_verification_code = :code AND email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':code', $this->email_verification_code);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Trouver un utilisateur par son ID
    public function findById($id) {
        try {
            $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->id = $row['id'];
                $this->first_name = $row['first_name'];
                $this->last_name = $row['last_name'];
                $this->email = $row['email'];
                $this->phone = $row['phone'];
                $this->role = $row['role'];
                $this->created_at = $row['created_at'];
                $this->verified = $row['verified'];
                return $row;
            }
            return false;
        } catch (Exception $e) {
            error_log("Erreur lors de la recherche de l'utilisateur: " . $e->getMessage());
            return false;
        }
    }

    // Lire tous les utilisateurs
    public function read() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Lire un utilisateur
    public function readOne() {
        $query = "SELECT * FROM " . $this->table . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->email = $row['email'];
            $this->phone = $row['phone'];
            $this->role = $row['role'];
            $this->created_at = $row['created_at'];
            
            return true;
        }
        
        return false;
    }
    
    // Mise à jour d'un utilisateur
    public function update() {
        $query = "UPDATE " . $this->table . "
                SET
                    first_name = :first_name,
                    last_name = :last_name,
                    phone = :phone,
                    role = :role
                WHERE
                    id = :id";
                    
        $stmt = $this->conn->prepare($query);
        
        // Nettoyage des données
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // Binding des paramètres
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':id', $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Mettre à jour le mot de passe
    public function updatePassword() {
        $query = "UPDATE " . $this->table . "
                SET
                    password = :password
                WHERE
                    id = :id";
                    
        $stmt = $this->conn->prepare($query);
        
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':id', $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Supprimer un utilisateur
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(1, $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    public function getAdminId() {
        $query = "SELECT id FROM users WHERE role = 'admin' LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id'] : null;
    }
}
?>
