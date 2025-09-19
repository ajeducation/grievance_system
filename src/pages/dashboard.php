<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/functions.php';
session_start();
require_login();
$user = $_SESSION['user'];
$page_title = 'Dashboard';
ob_start();
?>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card shadow-sm animate__animated animate__fadeInUp">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-list-task"></i> Total Grievances</h5>
                <p class="display-6">[total]</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm animate__animated animate__fadeInUp">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-hourglass-split"></i> Ongoing</h5>
                <p class="display-6">[ongoing]</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm animate__animated animate__fadeInUp">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-check-circle"></i> Completed</h5>
                <p class="display-6">[completed]</p>
            </div>
        </div>
    </div>
</div>
<!-- ...add more dashboard widgets/charts as needed... -->
<?php $content = ob_get_clean(); include __DIR__ . '/../includes/layout.php'; ?>
