<?php
if (!defined('ROOT_PATH')) {
    include_once($_SERVER['DOCUMENT_ROOT'] . '/GestiondeTareas/app/config/dirs.php');
}
?>

<div class="sidebar bg-white position-relative">
    <div class="holder position-fixed">
        <h3 class="position-relative text-center fw-bold">Profesor</h3>
        <div class="links">
            <ul>
                <li>
                    <a class="d-flex align-items-center" href="<?= BASE_URL ?>?page=dashboard&role=profesor">
                        <i class="fa-solid fa-gauge-high fa-fw"></i>
                        <span>Panel Principal</span>
                    </a>
                </li>
                <li>
                    <a class="d-flex align-items-center" href="<?= BASE_URL ?>?page=task_management&role=profesor">
                        <i class="fa-solid fa-list-check fa-fw"></i>
                        <span>Gestión de Tareas</span>
                    </a>
                </li>
                <li>
                    <a class="d-flex align-items-center" href="<?= BASE_URL ?>?page=assigned_tasks&role=profesor">
                        <i class="fa-solid fa-clipboard-list fa-fw"></i>
                        <span>Tareas Asignadas</span>
                    </a>
                </li>
                <li>
                    <a class="d-flex align-items-center" href="<?= BASE_URL ?>?page=task_submissions&role=profesor">
                        <i class="fa-solid fa-file-circle-check fa-fw"></i>
                        <span>Entregas de Tareas</span>
                        <?php 
                        // Mostrar contador de entregas pendientes si está disponible
                        if (class_exists('TareaController')) {
                            try {
                                $tareaController = new TareaController();
                                $entregasPendientes = $tareaController->contarEntregasPendientes($_SESSION['user_id'] ?? 0);
                                if ($entregasPendientes > 0):
                        ?>
                        <span class="badge bg-warning rounded-pill ms-auto"><?= $entregasPendientes ?></span>
                        <?php 
                                endif;
                            } catch (Exception $e) {
                                // Silenciar errores para evitar problemas en la interfaz
                            }
                        }
                        ?>
                    </a>
                </li>
                <li>
                    <a class="d-flex align-items-center" href="<?= BASE_URL ?>?page=notifications&role=profesor">
                        <i class="fa-solid fa-bell fa-fw"></i>
                        <span>Notificaciones</span>
                        <?php 
                        if (isset($_SESSION['user_id']) && class_exists('TareaController')) {
                            try {
                                $tareaController = new TareaController();
                                $notificacionesNoLeidas = $tareaController->contarNotificacionesNoLeidas($_SESSION['user_id']);
                                if ($notificacionesNoLeidas > 0):
                        ?>
                        <span class="badge bg-danger rounded-pill ms-auto"><?= $notificacionesNoLeidas ?></span>
                        <?php 
                                endif;
                            } catch (Exception $e) {
                                // Silenciar errores
                            }
                        }
                        ?>
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