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

def test_student_login_and_notifications(browser):
    # Credenciales del estudiante
    email = "estudiante1@escuela.edu"  # Reemplaza con el correo del estudiante
    password = "est123"  # Reemplaza con la contraseña del estudiante

    # Navegar a la página de inicio de sesión
    browser.get("http://localhost/GestiondeTareas/app/views/auth/login.php")

    # Esperar a que los campos estén visibles y llenarlos
    username_field = WebDriverWait(browser, 10).until(
        EC.presence_of_element_located((By.NAME, "email"))
    )
    password_field = browser.find_element(By.NAME, "password")

    username_field.send_keys(email)
    password_field.send_keys(password)

    # Hacer clic en el botón de inicio de sesión
    submit_button = browser.find_element(By.CSS_SELECTOR, "button.btn.btn-login[type='submit']")
    submit_button.click()

    # Validar que el inicio de sesión fue exitoso
    WebDriverWait(browser, 10).until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1.position-relative.header-page")))

    # Hacer clic en el botón de notificaciones
    notifications_button = WebDriverWait(browser, 10).until(
        EC.element_to_be_clickable((By.XPATH, "//a[contains(@href, 'notifications')]"))
    )
    notifications_button.click()

    # Validar que se ha navegado a la página de notificaciones y que el título está presente
    WebDriverWait(browser, 10).until(EC.presence_of_element_located((By.XPATH, "//h1[text()='Notificaciones']")))

    print("Prueba de inicio de sesión y notificaciones del estudiante completada con éxito.")