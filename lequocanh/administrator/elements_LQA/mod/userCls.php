<?php
$s = '../../elements_LQA/mod/database.php';
if (file_exists($s)) {
    $f = $s;
} else {
    $f = __DIR__ . '/database.php';
}
require_once $f;

// Include PasswordHelper để sử dụng Bcrypt
$passwordHelperPath = __DIR__ . '/PasswordHelper.php';
if (file_exists($passwordHelperPath)) {
    require_once $passwordHelperPath;
} else {
    require_once './elements_LQA/mod/PasswordHelper.php';
}

// Kiểm tra xem class user đã được định nghĩa chưa
if (!class_exists('user')) {
    class user
    {
        private $db;

        public function __construct()
        {
            $this->db = Database::getInstance()->getConnection();
        }

        public function UserCheckLogin($username, $password)
        {
            // Loại bỏ khoảng trắng thừa từ username
            $username = trim($username);

            // Ghi log để debug
            error_log("UserCheckLogin - Username: '$username', Password length: " . strlen($password));

            // Kiểm tra trực tiếp trong cơ sở dữ liệu
            $sql = 'SELECT * FROM user WHERE username = ?';
            $data = array($username);

            $select = $this->db->prepare($sql);
            $select->setFetchMode(PDO::FETCH_OBJ);
            $select->execute($data);

            $user = $select->fetch();

            if ($user) {
                error_log("UserCheckLogin - Tìm thấy user: " . $user->username);

                // Kiểm tra mật khẩu sử dụng Bcrypt
                $passwordMatch = false;
                
                // Kiểm tra xem password trong DB có phải là plain text không
                if (PasswordHelper::isPlainText($user->password)) {
                    error_log("UserCheckLogin - Password là plain text, đang migration sang Bcrypt");
                    
                    // So sánh plain text
                    if ($user->password === $password) {
                        $passwordMatch = true;
                        
                        // Tự động hash lại password và cập nhật vào database
                        $hashedPassword = PasswordHelper::hash($password);
                        $update_sql = "UPDATE user SET password = ? WHERE iduser = ?";
                        $update = $this->db->prepare($update_sql);
                        $update->execute(array($hashedPassword, $user->iduser));
                        error_log("UserCheckLogin - Đã migration password sang Bcrypt cho user: " . $username);
                    }
                } else {
                    // Verify password với Bcrypt hash
                    $passwordMatch = PasswordHelper::verify($password, $user->password);
                    
                    // Kiểm tra xem có cần rehash không (khi thay đổi cost factor)
                    if ($passwordMatch && PasswordHelper::needsRehash($user->password)) {
                        $hashedPassword = PasswordHelper::hash($password);
                        $update_sql = "UPDATE user SET password = ? WHERE iduser = ?";
                        $update = $this->db->prepare($update_sql);
                        $update->execute(array($hashedPassword, $user->iduser));
                        error_log("UserCheckLogin - Đã rehash password cho user: " . $username);
                    }
                }

                if ($passwordMatch) {
                    error_log("UserCheckLogin - Mật khẩu khớp");

                    // Kiểm tra trạng thái tài khoản
                    if ($user->setlock == 1) {
                        error_log("UserCheckLogin - Tài khoản đã kích hoạt (setlock=1)");
                        return true;
                    } else {
                        error_log("UserCheckLogin - Tài khoản chưa kích hoạt (setlock=" . $user->setlock . ")");

                        // Tự động kích hoạt tài khoản
                        $update_sql = "UPDATE user SET setlock = 1 WHERE iduser = ?";
                        $update = $this->db->prepare($update_sql);
                        $update->execute(array($user->iduser));
                        error_log("UserCheckLogin - Đã tự động kích hoạt tài khoản");

                        return true; // Cho phép đăng nhập sau khi kích hoạt
                    }
                } else {
                    error_log("UserCheckLogin - Mật khẩu không khớp");
                    return false;
                }
            } else {
                error_log("UserCheckLogin - Không tìm thấy user với username: '$username'");

                // Kiểm tra xem có user nào gần giống không
                $sql_like = "SELECT * FROM user WHERE username LIKE ?";
                $stmt_like = $this->db->prepare($sql_like);
                $stmt_like->execute(array('%' . $username . '%'));
                $similar_users = $stmt_like->fetchAll(PDO::FETCH_OBJ);

                if (count($similar_users) > 0) {
                    error_log("UserCheckLogin - Tìm thấy " . count($similar_users) . " user tương tự");
                }

                return false;
            }
        }
        public function UserCheckUsername($username)
        {
            $sql = 'select * from user where username = ?';
            $data = array($username);

            $select = $this->db->prepare($sql);
            $select->setFetchMode(PDO::FETCH_OBJ);
            $select->execute($data);

            $get_obj = count($select->fetchAll());

            if ($get_obj === 1) {
                return true;
            } else {
                return false;
            }
        }
        public function UserGetAll()
        {
            $sql = 'select * from user';

            $getAll = $this->db->prepare($sql);
            $getAll->setFetchMode(PDO::FETCH_OBJ);
            $getAll->execute();

            return $getAll->fetchAll();
        }
        public function UserAdd($username, $password, $hoten, $gioitinh, $ngaysinh, $diachi, $dienthoai, $email = null)
        {
            // Loại bỏ khoảng trắng thừa từ username
            $username = trim($username);

            // Hash password sử dụng Bcrypt
            $hashedPassword = PasswordHelper::hash($password);

            $sql = "INSERT INTO user (username, password, hoten, gioitinh, ngaysinh, diachi, dienthoai, email, setlock) VALUES (?,?,?,?,?,?,?,?,?)";
            $data = array($username, $hashedPassword, $hoten, $gioitinh, $ngaysinh, $diachi, $dienthoai, $email, 1); // Thêm setlock=1 để kích hoạt tài khoản

            $add = $this->db->prepare($sql);
            $add->execute($data);
            return $add->rowCount();
        }
        public function UserDelete($iduser)
        {
            $sql = "DELETE from user where iduser = ?";
            $data = array($iduser);

            $del = $this->db->prepare($sql);
            $del->execute($data);
            return $del->rowCount();
        }
        public function UserUpdate($username, $password, $hoten, $gioitinh, $ngaysinh, $diachi, $dienthoai, $iduser, $email = null)
        {
            try {
                // Nếu password không rỗng, hash nó trước khi update
                if (!empty($password)) {
                    // Kiểm tra xem password có phải là hash chưa
                    if (PasswordHelper::isPlainText($password)) {
                        $password = PasswordHelper::hash($password);
                    }
                }

                $sql = "UPDATE user SET
                    username=?,
                    password=?,
                    hoten=?,
                    gioitinh=?,
                    ngaysinh=?,
                    diachi=?,
                    dienthoai=?,
                    email=?
                    WHERE iduser=?";

                $data = array($username, $password, $hoten, $gioitinh, $ngaysinh, $diachi, $dienthoai, $email, $iduser);
                $update = $this->db->prepare($sql);
                $update->execute($data);
                return $update->rowCount();
            } catch (PDOException $e) {
                return false;
            }
        }
        public function UserGetbyId($iduser)
        {
            $sql = 'select * from user where iduser=?';
            $data = array($iduser);

            $getOne = $this->db->prepare($sql);
            $getOne->setFetchMode(PDO::FETCH_OBJ);
            $getOne->execute($data);

            return $getOne->fetch();
        }
        public function UserSetPassword($iduser, $password)
        {
            // Hash password sử dụng Bcrypt
            $hashedPassword = PasswordHelper::hash($password);

            $sql = "UPDATE user set password = ? WHERE iduser =? ";
            $data = array($hashedPassword, $iduser);

            $update_pass = $this->db->prepare($sql);
            $update_pass->execute($data);
            return $update_pass->rowCount();
        }
        public function UserSetActive($iduser, $setlock)
        {
            $sql = "UPDATE user set setlock = ? WHERE iduser =? ";
            $data = array($setlock, $iduser);

            $update_lock = $this->db->prepare($sql);
            $update_lock->execute($data);
            return $update_lock->rowCount();
        }
        public function UserChangePassword($iduser, $passwordold, $passwordnew)
        {
            // Lấy thông tin user
            $sql = 'SELECT * FROM user WHERE iduser = ?';
            $data = array($iduser);

            $select = $this->db->prepare($sql);
            $select->setFetchMode(PDO::FETCH_OBJ);
            $select->execute($data);

            $user = $select->fetch();
            
            if ($user) {
                // Verify password cũ
                $passwordMatch = false;
                
                if (PasswordHelper::isPlainText($user->password)) {
                    // So sánh plain text
                    $passwordMatch = ($user->password === $passwordold);
                } else {
                    // Verify với Bcrypt
                    $passwordMatch = PasswordHelper::verify($passwordold, $user->password);
                }
                
                if ($passwordMatch) {
                    // Hash password mới và update
                    $hashedPassword = PasswordHelper::hash($passwordnew);
                    $sql = "UPDATE user SET password = ? WHERE iduser = ?";
                    $data = array($hashedPassword, $iduser);

                    $update_pass = $this->db->prepare($sql);
                    $update_pass->execute($data);
                    return $update_pass->rowCount();
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
        public function UserGetAllExceptAdmin()
        {
            $sql = "SELECT * FROM user WHERE username != 'admin'";

            $getAll = $this->db->prepare($sql);
            $getAll->setFetchMode(PDO::FETCH_OBJ);
            $getAll->execute();

            return $getAll->fetchAll();
        }

        public function UserGetbyUsername($username)
        {
            $sql = 'SELECT * FROM user WHERE username = ?';
            $data = array($username);

            $getOne = $this->db->prepare($sql);
            $getOne->setFetchMode(PDO::FETCH_OBJ);
            $getOne->execute($data);

            return $getOne->fetch();
        }
    } // Đóng class user
} // Đóng if (!class_exists('user'))
// Removed direct instantiation of user class