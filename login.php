<?php
session_start();
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['admin_id'];
    $pw = $_POST['admin_pw'];

    if ($id === 'deok22' && $pw === 'misteam!01') {
        $_SESSION['logged_in'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = '아이디 또는 비밀번호가 일치하지 않습니다.';
    }
}
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>로그인 | 텐트깔끄미 할인권 시스템</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Pretendard:wght@400;600;800&display=swap" rel="stylesheet">
</head>

<body class="login-page">
    <div class="login-card">
        <div class="login-header">
            <a href="/discount/" class="logo">⛺</a>
            <h1>텐트깔끄미</h1>
            <p>할인권 발행 시스템</p>
        </div>
        <form method="POST" action="login.php" class="login-form">
            <div class="form-group">
                <label>관리자 ID</label>
                <input type="text" name="admin_id" id="admin_id" required autofocus placeholder="아이디를 입력하세요">
            </div>
            <div class="form-group">
                <label>비밀번호</label>
                <input type="password" name="admin_pw" id="admin_pw" required placeholder="비밀번호를 입력하세요">
            </div>
            <?php if ($error): ?>
                <div class="error-msg">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary btn-large">시스템 접속하기</button>
        </form>
        <div class="login-footer">
            &copy; 2026 텐트깔끄미 All Rights Reserved.
        </div>
    </div>
</body>

</html>