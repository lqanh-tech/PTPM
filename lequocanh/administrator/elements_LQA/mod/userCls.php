<?php
$s = '../../elements_LQA/mod/database.php';
if (file_exists($s)) {
    $f = $s;
} else {
    $f = __DIR__ . '/database.php';
}
require_once $f;

$passwordHelperPath = __DIR__ . '/PasswordHelper.php';
if (file_exists($passwordHelperPath)) {
    require_once $passwordHelperPath;
} else {
    require_once './elements_LQA/mod/PasswordHelper.php';
}

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

            $username = trim($username);

            error_log("UserCheckLogin - Username: '$username', Password length: " . strlen($password));

            $sql = 'SELECT * FROM user WHERE username = ?';
            $data = array($username);

            $select = $this->db->prepare($sql);
            $select->setFetchMode(PDO::FETCH_OBJ);
            $select->execute($data);

            $user = $select->fetch();

            if ($user) {
                error_log("UserCheckLogin - Tìm thấy user: " . $user->username);

                $passwordMatch = false;
                
                if (PasswordHelper::isPlainText($user->password)) {
                    error_log("UserCheckLogin - Password là plain text, đang migration sang Bcrypt");
                    
                    if ($user->password === $password) {
                        $passwordMatch = true;
                        
                        $hashedPassword = PasswordHelper::hash($password);
                        $update_sql = "UPDATE user SET password = ? WHERE iduser = ?";
                        $update = $this->db->prepare($update_sql);
                        $update->execute(array($hashedPassword, $user->iduser));
                        error_log("UserCheckLogin - Đã migration password sang Bcrypt cho user: " . $username);
                    }
                } else {

                    $passwordMatch = PasswordHelper::verify($password, $user->password);
                    
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

                    if ($user->setlock == 1) {
                        error_log("UserCheckLogin - Tài khoản đã kích hoạt (setlock=1)");
                        return true;
                    } else {
                        error_log("UserCheckLogin - Tài khoản chưa kích hoạt (setlock=" . $user->setlock . ")");

                        $update_sql = "UPDATE user SET setlock = 1 WHERE iduser = ?";
                        $update = $this->db->prepare($update_sql);
                        $update->execute(array($user->iduser));
                        error_log("UserCheckLogin - Đã tự động kích hoạt tài khoản");

                        return true;
                    }
                } else {
                    error_log("UserCheckLogin - Mật khẩu không khớp");
                    return false;
                }
            } else {
                error_log("UserCheckLogin - Không tìm thấy user với username: '$username'");

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
        public function UserAdd($username, $password, $hoten, $gioitinh, $ngaysinh, $diachi, $dienthoai, $email = null, $province_id = null, $district_id = null, $ward_id = null)
        {

            $username = trim($username);

            $hashedPassword = PasswordHelper::hash($password);

            $sql = "INSERT INTO user (username, password, hoten, gioitinh, ngaysinh, diachi, province_id, district_id, ward_id, dienthoai, email, setlock) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
            $data = array($username, $hashedPassword, $hoten, $gioitinh, $ngaysinh, $diachi, $province_id, $district_id, $ward_id, $dienthoai, $email, 1);

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

                if (!empty($password)) {

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

            $sql = 'SELECT * FROM user WHERE iduser = ?';
            $data = array($iduser);

            $select = $this->db->prepare($sql);
            $select->setFetchMode(PDO::FETCH_OBJ);
            $select->execute($data);

            $user = $select->fetch();
            
            if ($user) {

                $passwordMatch = false;
                
                if (PasswordHelper::isPlainText($user->password)) {

                    $passwordMatch = ($user->password === $passwordold);
                } else {

                    $passwordMatch = PasswordHelper::verify($passwordold, $user->password);
                }
                
                if ($passwordMatch) {

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
    }
}
