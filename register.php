<?php
include 'connection.php';

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); 

    $sql = "INSERT INTO users (username, password_hash) VALUES ('$username', '$password')";
    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Регистрация успешна! Теперь войдите.'); window.location='login.php';</script>";
    } else {
        $error = "Ошибка: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация - Трекер накоплений</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex justify-content-center align-items-center" style="height: 100vh;">
    <div class="card shadow-sm" style="width: 400px;">
        <div class="card-body p-4">
            <h3 class="text-center mb-4">Создать аккаунт</h3>
            
            <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Логин</label>
                    <input type="text" name="username" class="form-control" placeholder="Введите логин" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Пароль</label>
                    <input type="password" name="password" class="form-control" placeholder="Придумайте пароль" required>
                </div>
                <button type="submit" name="register" class="btn btn-success w-100 mb-3">Зарегистрироваться</button>
                <p class="text-center mb-0">Уже есть аккаунт? <a href="login.php">Войти</a></p>
            </form>
        </div>
    </div>
</body>
</html>