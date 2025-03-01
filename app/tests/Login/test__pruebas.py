import pytest
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.chrome.service import Service as ChromeService

@pytest.fixture
def browser():
    service = ChromeService(ChromeDriverManager().install())
    driver = webdriver.Chrome(service=service)
    driver.maximize_window()
    yield driver
    driver.quit()

# Lista de credenciales de los usuarios obtenidos de la imagen
usuarios = [
    ("admin@escuela.edu", "admin123"),    # Administrador Principal
    ("admin2@escuela.edu", "admin456"),   # Administrador Secundario
    ("profesor1@escuela.edu", "prof123"),  # Profesor 1
    ("profesor2@escuela.edu", "prof456"),  # Profesor 2
    ("profesor3@escuela.edu", "prof789"),  # Profesor 3
    ("estudiante1@escuela.edu", "est123"),  # Estudiante 1
    ("estudiante2@escuela.edu", "est456"),  # Estudiante 2
    ("estudiante3@escuela.edu", "est789"),  # Estudiante 3
    ("estudiante4@escuela.edu", "est101"),  # Estudiante 4
    ("estudiante5@escuela.edu", "est102"),  # Estudiante 5
]

@pytest.mark.parametrize("email, password", usuarios)
def test_login_success(browser, email, password):
    browser.get("http://localhost/GestiondeTareas/app/views/auth/login.php")

    # Esperar hasta que los campos estén visibles
    username_field = WebDriverWait(browser, 10).until(
        EC.presence_of_element_located((By.NAME, "email"))
    )
    password_field = browser.find_element(By.NAME, "password")

    username_field.send_keys(email)
    password_field.send_keys(password)

    submit_button = browser.find_element(By.CSS_SELECTOR, "button.btn.btn-login[type='submit']")
    submit_button.click()

    # Validar si el usuario accede correctamente según su tipo
    try:
        if "admin" in email:
            WebDriverWait(browser, 10).until(EC.presence_of_element_located((By.LINK_TEXT, "Panel Principal")))
        elif "profesor" in email:
            WebDriverWait(browser, 10).until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1.position-relative.header-page")))
        elif "estudiante" in email:
            WebDriverWait(browser, 10).until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1.position-relative.header-page")))
        assert True  # La prueba pasa si encuentra el elemento correcto
    except:
        pytest.fail(f"Error: {email} no pudo iniciar sesión correctamente.")

@pytest.mark.parametrize("email, password", [
    ("fakeuser@escuela.edu", "wrongpass"),    # Usuario incorrecto
    ("admin@escuela.edu", "wrongpass"),      # Contraseña incorrecta
])
def test_login_failure(browser, email, password):
    browser.get("http://localhost/GestiondeTareas/app/views/auth/login.php")

    username_field = WebDriverWait(browser, 10).until(
        EC.presence_of_element_located((By.NAME, "email"))
    )
    password_field = browser.find_element(By.NAME, "password")

    username_field.send_keys(email)
    password_field.send_keys(password)

    submit_button = browser.find_element(By.CSS_SELECTOR, "button.btn.btn-login[type='submit']")
    submit_button.click()

    # Validar si aparece el mensaje de error
    try:
        error_message = WebDriverWait(browser, 10).until(
            EC.presence_of_element_located((By.ID, "swal2-html-container"))
        )
        assert error_message.is_displayed()
    except:
        pytest.fail(f"No se mostró mensaje de error al intentar login con {email}.")