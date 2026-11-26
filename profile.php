<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// 1. Hitung total target
$total_goals = $conn->query("SELECT COUNT(*) as total FROM goals WHERE user_id = '$user_id'")->fetch_assoc()['total'];

// 2. Hitung total saldo riil (Deposit - Withdraw)
$total_saved_result = $conn->query("
    SELECT 
        COALESCE(SUM(CASE WHEN t.type = 'deposit' THEN t.amount ELSE 0 END), 0) - 
        COALESCE(SUM(CASE WHEN t.type = 'withdraw' THEN t.amount ELSE 0 END), 0) as total 
    FROM transactions t 
    JOIN goals g ON t.goal_id = g.goal_id 
    WHERE g.user_id = '$user_id'
");
$total_saved_data = $total_saved_result->fetch_assoc();
$total_saved = $total_saved_data['total'] ? $total_saved_data['total'] : 0;

// 3. Ambil Riwayat Transaksi (History)
$history_query = $conn->query("
    SELECT t.amount, t.type, g.name as goal_name 
    FROM transactions t 
    JOIN goals g ON t.goal_id = g.goal_id 
    WHERE g.user_id = '$user_id' 
    ORDER BY t.transaction_id DESC
");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Профиль - Трекер накоплений</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">

<div class="container">
    <a href="index.php" class="btn btn-secondary mb-4">← Назад на главную</a>

    <div class="card text-center shadow-sm mb-4">
        <div class="card-body p-5">
            <div class="rounded-circle bg-secondary d-inline-flex justify-content-center align-items-center mb-3" style="width: 100px; height: 100px;">
                <span class="text-white fs-1">👤</span>
            </div>
            
            <h1 class="display-6">Пользователь: <?= $username ?></h1>
            <p class="text-muted">Статус: <?= $total_goals ?> целей в трекере</p>
            
            <hr class="my-4">
            
            <h3 class="mb-4">Мои достижения</h3>
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <div class="card bg-warning text-dark mb-3">
                        <div class="card-body">
                            <h5 class="card-title">⭐ Первый шаг</h5>
                            <p class="card-text">Всего накоплено: <?= number_format($total_saved, 0, ',', ' ') ?> руб.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-warning text-dark mb-3">
                        <div class="card-body">
                            <h5 class="card-title">⭐ Стабильность</h5>
                            <p class="card-text">Создано <?= $total_goals ?> целей накопления</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BAGIAN RIWAYAT TRANSAKSI (BARU) -->
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">История транзакций</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0 text-center">
                <thead class="table-light">
                    <tr>
                        <th>Цель</th>
                        <th>Тип операции</th>
                        <th>Сумма</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($history_query->num_rows > 0): ?>
                        <?php while($row = $history_query->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['goal_name']) ?></td>
                                <td>
                                    <?php if($row['type'] == 'deposit'): ?>
                                        <span class="badge bg-success">Пополнение</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Снятие</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($row['type'] == 'deposit'): ?>
                                        <span class="text-success fw-bold">+ <?= number_format($row['amount'], 0, ',', ' ') ?> руб.</span>
                                    <?php else: ?>
                                        <span class="text-danger fw-bold">- <?= number_format($row['amount'], 0, ',', ' ') ?> руб.</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-muted py-3">История пуста. Вы еще не совершали транзакций.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>