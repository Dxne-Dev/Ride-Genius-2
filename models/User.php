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
    public $email_verification_code; // Pour le code de vérification

    public function __construct($db) {
        $this->conn = $db;
    }

    // Créer un nouvel utilisateur
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  SET first_name = :first_name,
                      last_name = :last_name,
                      email = :email,
                      password = :password,
                      phone = :phone,
                      role = :role,
                      email_verification_code = :code"; // Ajout de la colonne
        $stmt = $this->conn->prepare($query);

        // Nettoyer les données
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->email_verification_code = $this->email_verification_code ?? ''; // Conserve la valeur existante

        // Liaison des paramètres
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":code", $this->email_verification_code);

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

    // Marquer l'utilisateur comme vérifié
    public function markAsVerified() {
        $query = "UPDATE " . $this->table . " 
                  SET verified = 1, email_verification_code = NULL 
                  WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $this->email);
        return $stmt->execute();
    }

    // Vérifier si l'utilisateur est vérifié
    public function isVerified($user_id) {
        $query = "SELECT verified FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row && $row['verified'] == 1;
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
}
?>
