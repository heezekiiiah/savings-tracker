<?php
session_start();
include 'connection.php';

// Proteksi Halaman
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Variabel untuk menyimpan status popup
$show_success_modal = false;
$show_error_modal = false;
$modal_message = "";

// Proses Tambah Target
if (isset($_POST['add_goal'])) {
    $name = $_POST['name'];
    $target = $_POST['target_amount'];
    $conn->query("INSERT INTO goals (user_id, name, target_amount) VALUES ('$user_id', '$name', '$target')");
    $show_success_modal = true;
    $modal_message = "✅ Цель успешно создана!";
}

// Proses Transaksi (Deposit / Withdraw)
if (isset($_POST['transaction'])) {
    $goal_id = $_POST['goal_id'];
    $amount = $_POST['amount'];
    $type = $_POST['type'];
    
    // Hitung saldo saat ini
    $balance_query = $conn->query("
        SELECT 
            COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END), 0) - 
            COALESCE(SUM(CASE WHEN type = 'withdraw' THEN amount ELSE 0 END), 0) as current_balance 
        FROM transactions WHERE goal_id = $goal_id
    ");
    $balance = $balance_query->fetch_assoc()['current_balance'];

    // Ambil target amount untuk pengecekan
    $goal_data = $conn->query("SELECT target_amount FROM goals WHERE goal_id = $goal_id")->fetch_assoc();
    $target_amount = $goal_data['target_amount'];

    // Validasi
    if ($type == 'withdraw' && $amount > $balance) {
        $show_error_modal = true;
        $modal_message = "Ошибка: Недостаточно средств! Ваш текущий баланс: " . number_format($balance, 0, ',', ' ') . " руб.";
    } elseif ($type == 'deposit' && ($balance + $amount) > $target_amount) {
        // Validasi jika menabung melebihi target
        $show_error_modal = true;
        $modal_message = "Ошибка: Вы не можете накопить больше целевой суммы (" . number_format($target_amount, 0, ',', ' ') . " руб.)!";
    } else {
        $conn->query("INSERT INTO transactions (goal_id, amount, type) VALUES ('$goal_id', '$amount', '$type')");
        $show_success_modal = true;
        $modal_message = "✅ Транзакция успешно выполнена!";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Дашборд - Трекер накоплений</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">

<div class="container">
    <!-- Шапка -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Трекер накоплений</h1>
        <div>
            <a href="profile.php" class="btn btn-outline-dark me-2">👤 Профиль (<?= $username ?>)</a>
            <a href="logout.php" class="btn btn-danger">Выйти</a>
        </div>
    </div>

    <!-- Форма добавления цели -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <span>Добавить новую цель</span>
            <span class="text-white" style="cursor: help;" title="Введите название цели и сумму, которую хотите накопить.">❓</span>
        </div>
        <div class="card-body">
            <form method="POST" class="row g-2">
                <div class="col-md-5">
                    <input type="text" name="name" class="form-control" placeholder="Название цели (например: Ноутбук)" required>
                </div>
                <div class="col-md-4">
                    <input type="number" name="target_amount" class="form-control" placeholder="Целевая сумма (руб)" required min="1">
                </div>
                <div class="col-md-3">
                    <button type="submit" name="add_goal" class="btn btn-primary w-100">Сохранить цель</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Список целей -->
    <h3 class="mb-3">Мои цели</h3>
    <?php
    $goals = $conn->query("SELECT * FROM goals WHERE user_id = '$user_id'");
    if ($goals->num_rows > 0) {
        while ($row = $goals->fetch_assoc()) {
            $goal_id = $row['goal_id'];
            $target = $row['target_amount'];
            
            // Hitung saldo riil (Deposit - Withdraw)
            $sum_result = $conn->query("
                SELECT 
                    COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END), 0) - 
                    COALESCE(SUM(CASE WHEN type = 'withdraw' THEN amount ELSE 0 END), 0) as total 
                FROM transactions WHERE goal_id = $goal_id
            ");
            $sum_data = $sum_result->fetch_assoc();
            $saved = $sum_data['total'] ? $sum_data['total'] : 0;
            
            // Hitung persentase
            $percent = ($target > 0) ? ($saved / $target) * 100 : 0;
            if ($percent < 0) $percent = 0;
            if ($percent > 100) $percent = 100;
            
            // Cek apakah target sudah tercapai
            $is_completed = ($saved >= $target);
            ?>
            <div class="card mb-3 shadow-sm">
                <div class="card-body">
                    <!-- Judul dan Tombol Hapus -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="card-title mb-0">
                            <?= $row['name'] ?>
                            <?php if ($is_completed): ?>
                                <span class="badge bg-success ms-2">Цель достигнута!</span>
                            <?php endif; ?>
                        </h5>
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                data-bs-toggle="modal" 
                                data-bs-target="#deleteModal" 
                                data-goal-id="<?= $goal_id ?>" 
                                data-goal-name="<?= $row['name'] ?>">
                            Удалить
                        </button>
                    </div>

                    <p class="card-text">
                        Накоплено: <strong><?= number_format($saved, 0, ',', ' ') ?> руб.</strong> / 
                        Цель: <strong><?= number_format($target, 0, ',', ' ') ?> руб.</strong>
                    </p>
                    
                    <!-- Progress Bar -->
                    <div class="progress mb-3" style="height: 25px;">
                        <div class="progress-bar <?= $is_completed ? 'bg-info' : 'bg-success' ?> progress-bar-striped progress-bar-animated" 
                             role="progressbar" 
                             style="width: <?= $percent ?>%" 
                             aria-valuenow="<?= $percent ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                             <?= round($percent) ?>%
                        </div>
                    </div>

                    <!-- FORM TRANSAKSI (Disembunyikan jika target tercapai) -->
                    <?php if ($is_completed): ?>
                        <div class="alert alert-success mb-0">
                            🎉 Поздравляем! Вы полностью накопили на эту цель.
                        </div>
                    <?php else: ?>
                        <form method="POST" class="row g-2 align-items-center">
                            <input type="hidden" name="goal_id" value="<?= $goal_id ?>">
                            <div class="col-auto">
                                <input type="number" name="amount" class="form-control" placeholder="Сумма (руб)" required min="1">
                            </div>
                            <div class="col-auto">
                                <select name="type" class="form-select">
                                    <option value="deposit">Пополнить</option>
                                    <option value="withdraw">Снять</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <button type="submit" name="transaction" class="btn btn-success">Выполнить</button>
                            </div>
                        </form>
                        <div class="mt-2">
                            <small class="text-muted">Введите сумму и выберите "Пополнить" или "Снять"</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php } 
    } else { ?>
        <div class="alert alert-info">У вас пока нет целей. Добавьте новую цель выше.</div>
    <?php } ?>
</div>

<!-- MODAL HAPUS -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h3 class="modal-title fw-bold" id="deleteModalLabel">Удалить цель?</h3>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-secondary">
        Цель <strong id="goalNameModal" class="text-dark"></strong> и все её накопления будут удалены. Это действие нельзя отменить.
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Отмена</button>
        <a href="#" id="deleteButton" class="btn btn-danger" style="background-color: #b91c1c; border-color: #b91c1c;">Удалить</a>
      </div>
    </div>
  </div>
</div>

<!-- MODAL SUKSES/ERROR -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-center">
      <div class="modal-body p-5">
        <div class="mb-3 display-4" id="modalIcon"></div>
        <h4 class="mb-4" id="modalText"></h4>
        <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal" onclick="window.location.href='index.php';">Вернуться на главную</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    var deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var goalId = button.getAttribute('data-goal-id');
        var goalName = button.getAttribute('data-goal-name');
        var modalBodyStrong = deleteModal.querySelector('#goalNameModal');
        modalBodyStrong.textContent = goalName;
        var deleteBtn = deleteModal.querySelector('#deleteButton');
        deleteBtn.href = 'delete_goal.php?id=' + goalId;
    });

    document.addEventListener("DOMContentLoaded", function() {
        <?php if ($show_success_modal): ?>
            document.getElementById('modalIcon').innerHTML = '✅';
            document.getElementById('modalText').innerHTML = '<?= $modal_message ?>';
            var successModal = new bootstrap.Modal(document.getElementById('statusModal'));
            successModal.show();
        <?php elseif ($show_error_modal): ?>
            document.getElementById('modalIcon').innerHTML = '❌';
            document.getElementById('modalText').innerHTML = '<?= $modal_message ?>';
            var errorModal = new bootstrap.Modal(document.getElementById('statusModal'));
            errorModal.show();
        <?php endif; ?>
    });
</script>

</body>
</html>