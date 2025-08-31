document.addEventListener("DOMContentLoaded", function () {
  // Verificar si hay un resultado de operación almacenado
  const storedResult = sessionStorage.getItem("operationResult");
  if (storedResult) {
    try {
      const result = JSON.parse(storedResult);
      if (result.success) {
        showAlert("Éxito", result.success, "success");
      } else if (result.error) {
        showAlert("Error", result.error, "error");
      }
    } catch (e) {
      console.error("Error al parsear el resultado:", e);
    }

    // Limpiar el storage después de mostrar
    sessionStorage.removeItem("operationResult");
  }
});

/**
 * Abre el modal para crear un nuevo usuario
 */
function openUserModal() {
  // Resetear el formulario
  document.getElementById("userForm").reset();

  // Configurar el formulario para creación
  document.querySelector('#userForm input[name="accion"]').value = "crear";
  document.querySelector('#userForm input[name="id"]').value = "";

  // Mostrar el campo de contraseña y hacerlo requerido
  const passwordField = document.querySelector(".password-field");
  passwordField.style.display = "block";
  passwordField.querySelector("input").setAttribute("required", "required");

  // Cambiar el título del modal
  document.querySelector("#userModal .modal-title").textContent =
    "Nuevo Usuario";

  // Mostrar el modal
  const modal = new bootstrap.Modal(document.getElementById("userModal"));
  modal.show();
}

/**
 * Abre el modal para editar un usuario existente
 * @param {Object} user - Datos del usuario a editar
 */
function editUser(user) {
  // Configurar el formulario para edición
  document.querySelector('#userForm input[name="accion"]').value = "actualizar";
  document.querySelector('#userForm input[name="id"]').value = user.id;
  document.querySelector('#userForm input[name="username"]').value =
    user.username;
  document.querySelector('#userForm input[name="email"]').value = user.email;
  document.querySelector('#userForm input[name="nombre"]').value = user.nombre;
  document.querySelector('#userForm input[name="apellidos"]').value =
    user.apellidos;

  // Seleccionar el rol correcto
  const rolSelect = document.querySelector('#userForm select[name="rol_id"]');
  for (let i = 0; i < rolSelect.options.length; i++) {
    if (rolSelect.options[i].value == user.rol_id) {
      rolSelect.selectedIndex = i;
      break;
    }
  }

  // Ocultar el campo de contraseña y hacerlo opcional
  const passwordField = document.querySelector(".password-field");
  passwordField.style.display = "block";
  passwordField.querySelector("input").removeAttribute("required");

  // Cambiar el título del modal
  document.querySelector("#userModal .modal-title").textContent =
    "Editar Usuario";

  // Mostrar el modal
  const modal = new bootstrap.Modal(document.getElementById("userModal"));
  modal.show();
}

/**
 * Cambia el estado de activación de un usuario
 * @param {number} userId - ID del usuario
 * @param {boolean} activate - True para activar, False para desactivar
 */
function toggleActivation(userId, activate) {
  const action = activate ? "activar" : "desactivar";
  const message = `¿Está seguro de que desea ${action} este usuario?`;

  // Usar SweetAlert2 para la confirmación
  confirmAction("Confirmar acción", message, "warning").then((result) => {
    if (result.isConfirmed) {
      const formData = new FormData();
      formData.append("accion", activate ? "activar" : "desactivar"); // CORREGIDO: usar 'desactivar'
      formData.append("id", userId);

      // CORREGIDO: Agregar header AJAX y usar window.location.href
      fetch(window.location.href, {
        method: "POST",
        body: formData,
        headers: {
          "X-Requested-With": "XMLHttpRequest", // AÑADIDO: Header para identificar AJAX
        },
      })
        .then((response) => {
          // Agregar logging para debug
          console.log("Response status:", response.status);
          console.log(
            "Response headers:",
            response.headers.get("content-type")
          );

          const contentType = response.headers.get("content-type");
          if (contentType && contentType.indexOf("application/json") !== -1) {
            return response.json();
          } else {
            // Si no es JSON, intentar leer como texto para debug
            return response.text().then((text) => {
              console.log("Respuesta no JSON:", text);
              location.reload();
              throw new Error("Respuesta no JSON esperada, recargando página.");
            });
          }
        })
        .then((data) => {
          console.log("Datos recibidos:", data); // Debug
          if (data.success) {
            showAlert("Éxito", data.success, "success");
            setTimeout(() => {
              location.reload();
            }, 1500);
          } else if (data.error) {
            showAlert("Error", data.error, "error");
          } else {
            showAlert("Error", "Respuesta inesperada del servidor", "error");
          }
        })
        .catch((error) => {
          console.error("Error en fetch:", error);
          if (
            error.message !== "Respuesta no JSON esperada, recargando página."
          ) {
            showAlert(
              "Error",
              "Ocurrió un error al procesar la solicitud: " + error.message,
              "error"
            );
          }
        });
    }
  });
}

/**
 * Función auxiliar para mostrar alertas estilizadas
 */
function showAlert(title, text, icon) {
  if (typeof Swal !== "undefined") {
    Swal.fire({
      title: title,
      text: text,
      icon: icon,
      confirmButtonColor: "#0075ff",
    });
  } else {
    alert(title + ": " + text);
  }
}

/**
 * Función auxiliar para mostrar diálogos de confirmación
 */
function confirmAction(title, text, icon) {
  if (typeof Swal !== "undefined") {
    return Swal.fire({
      title: title,
      text: text,
      icon: icon,
      showCancelButton: true,
      confirmButtonColor: "#0075ff",
      cancelButtonColor: "#d33",
      confirmButtonText: "Confirmar",
      cancelButtonText: "Cancelar",
    });
  } else {
    const confirmed = confirm(text);
    // Emular la estructura de retorno de SweetAlert2
    return Promise.resolve({
      isConfirmed: confirmed,
    });
  }
}
