<?php
session_start();
ob_start();

// PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/PHPMailer-master/src/SMTP.php';

// ====== HÀM HỖ TRỢ ======
function creatcode() {
    $code = '';
    $characters = '0123456789';
    for ($i = 0; $i < 4; $i++) {
        $code .= $characters[mt_rand(0, strlen($characters) - 1)];
    }
    return $code;
}

function pdo_get_connection(){
    $dburl = "mysql:host=localhost;dbname=zstyle;charset=utf8";
    $username = 'root';
    $password = '';
    $conn = new PDO($dburl, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $conn;
}

function pdo_query($sql){
    $sql_args = array_slice(func_get_args(), 1);
    try{
        $conn = pdo_get_connection();
        $stmt = $conn->prepare($sql);
        $stmt->execute($sql_args);
        return $stmt->fetchAll();
    } finally{ unset($conn); }
}

function pdo_query_one($sql){
    $sql_args = array_slice(func_get_args(), 1);
    try{
        $conn = pdo_get_connection();
        $stmt = $conn->prepare($sql);
        $stmt->execute($sql_args);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } finally{ unset($conn); }
}

function getusertoemail($email){
    $sql="SELECT * FROM users WHERE email=?";
    return pdo_query_one($sql, $email);
}

// ====== GỬI MAIL ĐẶT HÀNG ======
if (isset($_POST["sendmail"]) && !empty($_SESSION['giohang'])) {

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->CharSet  = "utf-8";
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'zstyleshopvn@gmail.com';
        $mail->Password   = 'sxdl zwmw frep mzwh'; // mật khẩu ứng dụng
        $mail->SMTPSecure = 'ssl';
        $mail->Port       = 465;                                    

        $mail->setFrom('zstyleshopvn@gmail.com', 'ZStyle');
        $mail->addAddress($_POST["emaildat"], $_POST["tendat"]);

        $mail->isHTML(true);
        $mail->Subject = 'Cảm ơn bạn đã mua hàng cùng chúng tôi!';
        $mail->AddEmbeddedImage('view/layout/assets/images/logo-form.png', 'logo', 'logo-form.png');
        $mail->AddEmbeddedImage('upload/form-thanks.jpg', 'img', 'form-thanks.jpg');

        // Lấy thông tin đơn hàng và khách hàng an toàn
        $ma_donhang = $_SESSION['donhang']['ma_donhang'] ?? '';
        $ngaylap    = $_SESSION['ngaylap'] ?? '';
        $name       = $_SESSION['name'] ?? '';
        $email      = $_SESSION['email'] ?? '';
        $sdt        = $_SESSION['sdt'] ?? '';
        $diachi     = $_SESSION['diachi'] ?? '';

        $html_donhang = '';
        $tongtien = 0;
        $i = 0;
        foreach ($_SESSION['giohang'] as $item) {
            $i++;
            extract($item);
            $thanhtien = $price * $soluong;
            $html_donhang .= '<tr>
                <td>'.$i.'</td>
                <td>'.$name.'</td>
                <td>'.$size.'</td>
                <td>'.$color.'</td>
                <td>'.number_format($price,0,'.',',').'</td>
                <td>'.$soluong.'</td>
                <td>'.number_format($thanhtien,0,'.',',').'</td>
            </tr>';
            $tongtien += $thanhtien;
        }

        // Giảm giá
        if(isset($_SESSION['giamgia']) && $_SESSION['giamgia']>0){
            $giamgia=$_SESSION['giamgia'];
            $html_donhang.='<tr>
                <td colspan="5"></td>
                <td>Giảm giá</td>
                <td>'.number_format(($tongtien*$giamgia/100),0,'.',',').'</td>
            </tr>';
            $html_donhang.='<tr>
                <td colspan="5"></td>
                <td><strong>Tổng tiền</strong></td>
                <td>'.number_format(($tongtien-$tongtien*$giamgia/100),0,'.',',').'</td>
            </tr>';
        } else {
            $html_donhang.='<tr>
                <td colspan="5"></td>
                <td><strong>Tổng tiền</strong></td>
                <td>'.number_format($tongtien,0,'.',',').'</td>
            </tr>';
        }

        // Thông tin tài khoản nếu có
        $account='';
        if(isset($_SESSION['username'], $_SESSION['password'])){
            $account='<tbody>
                        <tr><td colspan="2"><strong>Username</strong></td><td colspan="6">'.$_SESSION['username'].'</td></tr>
                        <tr><td colspan="2"><strong>Password</strong></td><td colspan="6">'.$_SESSION['password'].'</td></tr>
                      </tbody>';
        }

        // Tạo nội dung email
        $text= '<html lang="en">
        <head>
            <meta charset="UTF-8">
            <style>
                .title{ text-align: center; color: #46694F; }
                .thank{ text-align: center; }
                table { border-collapse: collapse; width: 100%; margin: 20px 0; }
                th{ background-color: #46694F; color: #fff; text-align: center; }
                td,th{ border:1px solid #ddd; padding:8px; text-align:center; }
            </style>
        </head>
        <body>
            <img src="cid:logo" alt="ZStyle Logo" style="width:150px; display:block; margin:auto;">
            <hr>
            <h2 class="title">THÔNG TIN ĐƠN HÀNG</h2> 
            <p class="thank">Cảm ơn bạn đã ghé thăm cửa hàng!</p>
            <table>
                <thead>
                    <tr><th colspan="8">Mã đơn hàng: '.$ma_donhang.'</th></tr>
                </thead>
                <tbody>
                    <tr><td colspan="2">Ngày lập</td><td colspan="6">'.$ngaylap.'</td></tr>
                    <tr><td colspan="2">Họ tên</td><td colspan="6">'.$name.'</td></tr>
                    <tr><td colspan="2">Email</td><td colspan="6">'.$email.'</td></tr>
                    <tr><td colspan="2">Số điện thoại</td><td colspan="6">'.$sdt.'</td></tr>
                    <tr><td colspan="2">Địa chỉ</td><td colspan="6">'.$diachi.'</td></tr>
                    '.$account.'
                </tbody>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Tên SP</th>
                        <th>Size</th>
                        <th>Màu</th>
                        <th>Giá</th>
                        <th>SL</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody>'.$html_donhang.'</tbody>
            </table>
            <p>Trân trọng, <strong>ZStyle</strong></p>
        </body>
        </html>';

        $mail->Body = $text;

        // Test: hiển thị email ra màn hình
        echo $text;

        // Sau khi gửi mail xong, hủy session liên quan
        unset($_SESSION['donhang'], $_SESSION['giohang'], $_SESSION['ngaylap'], $_SESSION['name'], $_SESSION['sdt'], $_SESSION['diachi'], $_SESSION['email'], $_SESSION['giamgia'], $_SESSION['username'], $_SESSION['password']);

        exit;

    } catch (Exception $e) {
        echo "Mail lỗi: {$mail->ErrorInfo}";
    }
}

// ====== GỬI MAIL QUÊN MẬT KHẨU ======
if (isset($_POST["guima"])) {

    $_SESSION['erremailxn']='';
    $_SESSION['emailxn'] = $_POST["emailxn"];

    if($_POST['emailxn']=='' || !filter_var($_POST['emailxn'], FILTER_VALIDATE_EMAIL)){
        $_SESSION['erremailxn']='*Email không hợp lệ';
    } else {
        $kt = 0;
        if(!empty($_SESSION['usertable'])){
            foreach ($_SESSION['usertable'] as $item) {
                if($item['email']==$_POST['emailxn']){
                    $kt=1; break;
                }
            }
        }
        if($kt==0){ $_SESSION['erremailxn']='*Email không tồn tại'; }
    }

    if($_SESSION['erremailxn']!=''){
        echo "<script>document.location.href='index.php?pg=forgetpass';</script>";
    } else {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->CharSet  = "utf-8";
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'myhong11a32004@gmail.com';
        $mail->Password   = 'zhuv uzbw gnrd ziop';
        $mail->SMTPSecure = 'ssl';
        $mail->Port       = 465;                                    

        $mail->setFrom('zstyleshopvn@gmail.com', 'ZStyle');
        $mail->addAddress($_POST["emailxn"]);

        $_SESSION['username'] = getusertoemail($_SESSION['emailxn'])['user'] ?? '';

        $_SESSION['code'] = creatcode();
        $text= '<html><head><meta charset="UTF-8"></head><body>
            <h2>Khôi phục tài khoản</h2>
            <p>Email: '.$_SESSION['emailxn'].'</p>
            <p>Tên đăng nhập: '.$_SESSION['username'].'</p>
            <p><b>Mã xác minh: '.$_SESSION['code'].'</b></p>
        </body></html>';

        $_SESSION['codedung'] = $_SESSION['code'];
        unset($_SESSION['code']);
        $mail->Body = $text;

        echo $text;
        exit;
    }
}
?>
