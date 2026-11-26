<?php
session_start();
include 'connection.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE username = '$username'");
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        header("Location: index.php");
    } else {
        $error = "Неверный логин или пароль!";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход - Трекер накоплений</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex justify-content-center align-items-center" style="height: 100vh;">
    <div class="card shadow-sm" style="width: 400px;">
        <div class="card-body p-4">
            <h3 class="text-center mb-4">Вход в Трекер</h3>
            
            <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Логин</label>
                    <input type="text" name="username" class="form-control" placeholder="Введите логин" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Пароль</label>
                    <input type="password" name="password" class="form-control" placeholder="Введите пароль" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary w-100 mb-3">Войти</button>
                <p class="text-center mb-0">Нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
            </form>
        </div>
    </div>
</body>
</html>
<!-- this is for login -->