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

def test_student_login_and_profile(browser):
    # Credenciales del estudiante
    email = "estudiante1@escuela.edu"
    password = "est123"

    # Navegar a la página de inicio de sesión
    browser.get("http://localhost/GestiondeTareas/app/views/auth/login.php")

    # Esperar a que los campos estén visibles y llenarlos
    username_field = WebDriverWait(browser, 20).until(
        EC.presence_of_element_located((By.NAME, "email"))
    )
    password_field = browser.find_element(By.NAME, "password")

    username_field.send_keys(email)
    password_field.send_keys(password)

    # Hacer clic en el botón de inicio de sesión
    submit_button = browser.find_element(By.CSS_SELECTOR, "button.btn.btn-login[type='submit']")
    submit_button.click()

    # Validar que el inicio de sesión fue exitoso
    WebDriverWait(browser, 20).until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1.position-relative.header-page")))

    # Hacer clic en el enlace "Mi Perfil"
    profile_link = WebDriverWait(browser, 20).until(
        EC.element_to_be_clickable((By.XPATH, "//a[contains(@href, 'profile')]"))
    )
    profile_link.click()

    # Validar que se ha navegado a la página de perfil
    
    # Puedes agregar validaciones adicionales aquí, como verificar el título de la página

    print("Prueba de inicio de sesión y perfil del estudiante completada con éxito.")