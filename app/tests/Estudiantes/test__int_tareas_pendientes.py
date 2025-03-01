import pytest
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.chrome.service import Service as ChromeService
from selenium.webdriver.support.ui import Select

@pytest.fixture
def browser():
    service = ChromeService(ChromeDriverManager().install())
    driver = webdriver.Chrome(service=service)
    driver.maximize_window()
    yield driver
    driver.quit()

def test_student_login_and_filter_tasks(browser):
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

    # Hacer clic en el enlace de "Mis Tareas"
    my_tasks_link = WebDriverWait(browser, 20).until(
        EC.element_to_be_clickable((By.XPATH, "//a[contains(@href, 'task_visualization')]"))
    )
    my_tasks_link.click()

    # Desplazarse hacia abajo manualmente
    browser.execute_script("window.scrollBy(0, 500);")

    # Hacer clic en el menú desplegable "Todos los Estados"
    status_dropdown = WebDriverWait(browser, 20).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, "#filter-estado"))
    )
    status_dropdown.click()

    # Validar que las opciones del menú desplegable se despliegan
    options = WebDriverWait(browser, 20).until(
        EC.presence_of_all_elements_located((By.XPATH, "//select[@id='filter-estado']/option"))
    )

    # Imprimir las opciones para verificar
    for option in options:
        print(option.text)

    # Puedes agregar validaciones adicionales aquí, como verificar el número de opciones

    print("Prueba de inicio de sesión, 'Mis Tareas' y despliegue de estados completada con éxito.")