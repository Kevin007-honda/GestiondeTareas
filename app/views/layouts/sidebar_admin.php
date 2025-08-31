<?php
if (!defined('ROOT_PATH')) {
    include_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/config/dirs.php');
}
?>

<div class="sidebar bg-white position-relative">
    <div class="holder position-fixed">
        <h3 class="position-relative text-center fw-bold">Administrador</h3>
        <div class="links">
            <ul>
                <li>
                    <a class="d-flex align-items-center" href="<?= BASE_URL ?>?page=dashboard&role=admin">
                        <i class="fa-solid fa-gauge-high fa-fw"></i>
                        <span>Panel Principal</span>
                    </a>
                </li>
                <li>
                    <a class="d-flex align-items-center" href="<?= BASE_URL ?>?page=user_management&role=admin">
                        <i class="fa-solid fa-users-gear fa-fw"></i>
                        <span>Gestión de Usuarios</span>
                    </a>
                </li>
                <li>
                    <a class="d-flex align-items-center" href="<?= BASE_URL ?>?page=group_management&role=admin">
                        <i class="fa-solid fa-user-group fa-fw"></i>
                        <span>Gestión de Grupos</span>
                    </a>
                </li>
                <li>
                    <a class="d-flex align-items-center" href="<?= BASE_URL ?>?page=subject_management&role=admin">
                        <i class="fa-solid fa-book fa-fw"></i>
                        <span>Gestión de Materias</span>
                    </a>
                </li>
                <li>
                    <a class="d-flex align-items-center" href="<?= BASE_URL ?>?page=notifications&role=admin">
                        <i class="fa-solid fa-bell fa-fw"></i>
                        <span>Notificaciones</span>
                    </a>
                </li>
            </ul>
        </div>
        <div class="log-out">
            <span onclick="window.location.href='<?= BASE_URL ?>/app/controllers/logout.php'">
                <i class="fa-solid fa-arrow-right-from-bracket" style="color: #e10000;"></i>
                <a href="<?= BASE_URL ?>/app/controllers/logout.php">Cerrar Sesión</a>
            </span>
        </div>
    </div>
</div>
