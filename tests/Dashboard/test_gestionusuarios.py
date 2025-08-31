import pytest
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.chrome.service import Service as ChromeService
from selenium.webdriver.support.ui import Select
import pickle
from selenium.common.exceptions import TimeoutException

driver = None

@pytest.fixture
def browser():
    global driver
    if driver is None:
        service = ChromeService(ChromeDriverManager().install())
        driver = webdriver.Chrome(service=service)
        yield driver
    else:
        yield driver

def test_login_success(browser):
    browser.get("http://localhost/GestiondeTareas/app/views/auth/login.php")
    username_field = browser.find_element(By.NAME, "email")
    password_field = browser.find_element(By.NAME, "password")
    username_field.send_keys("admin@escuela.edu")
    password_field.send_keys("admin123")
    submit_button = browser.find_element(By.CSS_SELECTOR, "button.btn.btn-login[type='submit']")
    submit_button.click()
    WebDriverWait(browser, 30).until(EC.visibility_of_element_located((By.LINK_TEXT, "Panel Principal")))
    usuarios_button = browser.find_element(By.LINK_TEXT, "Gestión de Usuarios")
    usuarios_button.click()
    WebDriverWait(browser, 30).until(EC.presence_of_element_located((By.XPATH, "//button[contains(@class, 'btn-primary') and contains(., 'Nuevo Usuario')]")))
    pickle.dump(browser.get_cookies(), open("cookies.pkl","wb"))

def test_crear_usuario(browser):
    browser.get("http://localhost/GestiondeTareas/?page=user_management&role=admin")
    cookies = pickle.load(open("cookies.pkl", "rb"))
    for cookie in cookies:
        browser.add_cookie(cookie)
    browser.get("http://localhost/GestiondeTareas/?page=user_management&role=admin")
    WebDriverWait(browser, 30).until(EC.presence_of_element_located((By.XPATH, "//h5[text()='Lista de Usuarios']")))

    nuevo_usuario_button = browser.find_element(By.XPATH, "//button[contains(@class, 'btn-primary') and contains(., 'Nuevo Usuario')]")

    browser.execute_script("arguments[0].scrollIntoView(true);", nuevo_usuario_button)

    WebDriverWait(browser, 10).until(EC.element_to_be_clickable((By.XPATH, "//button[contains(@class, 'btn-primary') and contains(., 'Nuevo Usuario')]")))

    browser.execute_script("arguments[0].click();", nuevo_usuario_button)

    WebDriverWait(browser, 10).until(EC.element_to_be_clickable((By.NAME, "username")))
    WebDriverWait(browser, 10).until(EC.element_to_be_clickable((By.NAME, "email")))
    WebDriverWait(browser, 10).until(EC.element_to_be_clickable((By.NAME, "nombre")))
    WebDriverWait(browser, 10).until(EC.element_to_be_clickable((By.NAME, "apellidos")))
    WebDriverWait(browser, 10).until(EC.element_to_be_clickable((By.NAME, "password")))

    browser.find_element(By.NAME, "username").send_keys("usuario_prueba")
    browser.find_element(By.NAME, "email").send_keys("usuario_prueba@example.com")
    browser.find_element(By.NAME, "nombre").send_keys("Goku")
    browser.find_element(By.NAME, "apellidos").send_keys("Quinto")
    browser.find_element(By.NAME, "password").send_keys("contrasena_prueba")
    rol_select = Select(browser.find_element(By.NAME, "rol_id"))
    rol_select.select_by_visible_text("estudiante")
    browser.find_element(By.CSS_SELECTOR, "button.btn.btn-primary[type='submit']").click()
    WebDriverWait(browser, 20).until(EC.presence_of_element_located((By.XPATH, "//td[text()='usuario_prueba']")))

def test_editar_usuario(browser):
    browser.get("http://localhost/GestiondeTareas/?page=user_management&role=admin")
    cookies = pickle.load(open("cookies.pkl", "rb"))

    for cookie in cookies:
        browser.add_cookie(cookie)
    browser.get("http://localhost/GestiondeTareas/?page=user_management&role=admin")
    WebDriverWait(browser, 30).until(EC.presence_of_element_located((By.XPATH, "//th[text()='Acciones']")))

    editar_button = browser.find_element(By.XPATH, "//td[contains(text(), 'admin@escuela.edu')]/following-sibling::td/div/button[contains(@class, 'btn-outline-primary')]")
    browser.execute_script("arguments[0].scrollIntoView(true);", editar_button)

    WebDriverWait(browser, 10).until(EC.element_to_be_clickable((By.XPATH, "//td[contains(text(), 'admin@escuela.edu')]/following-sibling::td/div/button[contains(@class, 'btn-outline-primary')]")))

    browser.execute_script("arguments[0].click();", editar_button)

    WebDriverWait(browser, 20).until(EC.visibility_of_element_located((By.XPATH, "//h5[text()='Gestionar Usuario']")))

    username_field = browser.find_element(By.NAME, "username")
    assert username_field.get_attribute("value") == "admin"

    email_field = browser.find_element(By.NAME, "email")
    assert email_field.get_attribute("value") == "admin@escuela.edu"

    cancelar_button = browser.find_element(By.XPATH, "//button[text()='Cancelar']")
    cancelar_button.click()
    WebDriverWait(browser, 10).until(EC.invisibility_of_element_located((By.XPATH, "//h5[text()='Gestionar Usuario']")))

def test_desactivar_usuario(browser):
    browser.get("http://localhost/GestiondeTareas/?page=user_management&role=admin")
    cookies = pickle.load(open("cookies.pkl", "rb"))
    for cookie in cookies:
        browser.add_cookie(cookie)
    browser.get("http://localhost/GestiondeTareas/?page=user_management&role=admin")
    
    WebDriverWait(browser, 20).until(EC.presence_of_element_located((By.XPATH, "//td[text()='usuario_prueba@example.com']")))
    cuadro_contenedor = WebDriverWait(browser, 20).until(
        EC.visibility_of_element_located((By.XPATH, "//div[contains(@class, 'btn-group-sm')]")))

    editar_button = cuadro_contenedor.find_element(By.XPATH, ".//button[contains(@class, 'btn-outline-primary')]")
    desactivar_button = cuadro_contenedor.find_element(By.XPATH, ".//button[contains(@class, 'btn-outline-danger')]")

    
    browser.execute_script("arguments[0].click();", desactivar_button)
    modal = WebDriverWait(browser, 10).until(
        EC.visibility_of_element_located((By.XPATH, "//div[contains(@class, 'swal2-modal')]")))

    mensaje_modal = modal.find_element(By.XPATH, ".//div[contains(@class, 'swal2-html-container')]")
    assert mensaje_modal.is_displayed()

    confirmar_desactivacion_button = modal.find_element(By.XPATH, ".//button[text()='Sí, desactivar']")
    browser.execute_script("arguments[0].click();", confirmar_desactivacion_button)

    mensaje_exito = WebDriverWait(browser, 10).until(EC.visibility_of_element_located((By.XPATH, "//div[contains(@class, 'swal2-success')]")))

    titulo_exito = browser.find_element(By.XPATH, "//h2[text()='¡Éxito!']")
    mensaje_exito_texto = browser.find_element(By.XPATH, "//div[contains(text(), 'Usuario desactivado correctamente')]")
    assert titulo_exito.is_displayed()
    assert mensaje_exito_texto.is_displayed()

    boton_ok = WebDriverWait(browser, 10).until(EC.visibility_of_element_located((By.XPATH, "//button[contains(@class, 'swal2-confirm')]")))
    browser.execute_script("arguments[0].click();", boton_ok)


def teardown_module(module):
    global driver
    if driver is not None:
        driver.quit()