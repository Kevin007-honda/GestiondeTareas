<?php
if (!defined('ROOT_PATH')) {
    include_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/config/dirs.php');
}
?>

<div class="sidebar bg-white position-relative">
    <div class="holder position-fixed">
        <h3 class="position-relative text-center fw-bold">Estudiante</h3>
        <div class="links">
            <ul>
                <li>
                    <a class="d-flex align-items-center" href="<?= BASE_URL ?>?page=task_visualization&role=student">
                        <i class="fa-solid fa-list-check fa-fw"></i>
                        <span>Tareas Pendientes</span>
                    </a>
                </li>
                <li>
                    <a class="d-flex align-items-center" href="<?= BASE_URL ?>?page=completed_tasks&role=student">
                        <i class="fa-solid fa-check-double fa-fw"></i>
                        <span>Tareas Completadas</span>
                    </a>
                </li>
                <li>
                    <a class="d-flex align-items-center" href="<?= BASE_URL ?>?page=notifications&role=student">
                        <i class="fa-solid fa-bell fa-fw"></i>
                        <span>Notificaciones</span>
                    </a>
                </li>
                <li>
                    <a class="d-flex align-items-center" href="<?= BASE_URL ?>?page=grades&role=student">
                        <i class="fa-solid fa-star fa-fw"></i>
                        <span>Calificaciones</span>
                    </a>
                </li>
                <li>
                    <a class="d-flex align-items-center" href="<?= BASE_URL ?>?page=profile&role=student">
                        <i class="fa-solid fa-user-graduate fa-fw"></i>
                        <span>Mi Perfil</span>
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
