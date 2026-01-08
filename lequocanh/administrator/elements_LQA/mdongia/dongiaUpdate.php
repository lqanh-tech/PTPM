<?php

$dongiaPaths = [
    '../../elements_LQA/mod/dongiaCls.php',
    '../mod/dongiaCls.php',
    './elements_LQA/mod/dongiaCls.php',
    './administrator/elements_LQA/mod/dongiaCls.php',
    __DIR__ . '/../mod/dongiaCls.php'
];

$foundDongia = false;
foreach ($dongiaPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $foundDongia = true;
        break;
    }
}

if (!$foundDongia) {
    if (class_exists('Logger')) {
        Logger::error("Could not find dongiaCls.php file", ['paths_checked' => $dongiaPaths]);
    } else {
        if (class_exists('Logger')) {
        Logger::error("Cannot find dongiaCls.php file", ['paths_checked' => $dongiaPaths]);
    } else {
        error_log("Không thể tìm thấy file dongiaCls.php");
    }
    }
    die("Không thể tải file dongiaCls.php");
}
$idDongia = $_REQUEST['idDongia'];
$dg = new Dongia();
$dongia = $dg->dongiaGetbyId($idDongia);
?>

<div align="center">Cập nhật đơn giá</div>
<hr>
<div>
    <form name="updatedongia" id="formupdatelh" method="post" action='./elements_LQA/mdongia/dongiaAct.php?reqact=updatedongia'>
        <input type="hidden" name="idDongia" value="<?php echo $dongia->idDongia; ?>" />
        <table>
            <tr>
                <td>ID Hàng hóa</td>
                <td><input type="text" name="idhanghoa" value="<?php echo $dongia->idhanghoa; ?>" /></td>
            </tr>
            <tr>
                <td>Ngày cập nhật</td>
                <td><input type="date" name="ngaycapnhat" value="<?php echo $dongia->ngaycapnhat; ?>" /></td>
            </tr>
            <tr>
                <td>Đơn giá</td>
                <td><input type="number" name="dongia" value="<?php echo $dongia->dongia; ?>" /></td>
            </tr>
            <tr>
                <td><input type="submit" id="btnsubmit" value="Cập nhật" /></td>
                <td><b id="noteForm"></b></td>
            </tr>
        </table>
    </form>
</div>