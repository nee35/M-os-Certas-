<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'profissional') {
    header('Location: login_profissional.php');
    exit;
}
?>