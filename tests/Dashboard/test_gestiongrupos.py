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
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC

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
    username_field.send_keys("admin2@escuela.edu")
    password_field.send_keys("admin456")
    submit_button = browser.find_element(By.CSS_SELECTOR, "button.btn.btn-login[type='submit']")
    submit_button.click()
    WebDriverWait(browser, 30).until(EC.visibility_of_element_located((By.LINK_TEXT, "Panel Principal")))
    grupos_button = browser.find_element(By.LINK_TEXT, "Gestión de Grupos")
    grupos_button.click()
    WebDriverWait(browser, 50).until(EC.presence_of_element_located((By.XPATH, "//h1[@class='mt-4' and contains(text(), 'Gestión de Grupos')]")))
    pickle.dump(browser.get_cookies(), open("cookies.pkl", "wb"))
    
def test_crear_grupo(browser):
    browser.get("http://localhost/GestiondeTareas/?page=group_management&role=admin")
    cookies = pickle.load(open("cookies.pkl", "rb"))
    for cookie in cookies:
        browser.add_cookie(cookie)
    browser.get("http://localhost/GestiondeTareas/?page=group_management&role=admin")
    WebDriverWait(browser, 30).until(EC.presence_of_element_located((By.XPATH, "//label[@class='form-label' and text()='Nombre del Grupo']")))

    WebDriverWait(browser, 30).until(EC.presence_of_element_located((By.ID, "nombre"))) 
    nombre_grupo_field = browser.find_element(By.ID, "nombre")   
    descripcion_grupo_field = browser.find_element(By.ID, "descripcion") 
    profesor_titular_select = Select(browser.find_element(By.ID, "profesor_id"))
    crear_grupo_button = browser.find_element(By.XPATH, "//button[@type='submit' and @class='btn btn-primary']") 

    nombre_grupo_field.send_keys("Grupo de Prueba Automatizado")
    descripcion_grupo_field.send_keys("Este es un grupo de prueba creado automáticamente.")
    profesor_titular_select.select_by_visible_text("Carlos Rodríguez") 
    browser.execute_script("arguments[0].scrollIntoView(true);", crear_grupo_button)
    browser.execute_script("arguments[0].click();", crear_grupo_button)
    
    ok_button = browser.find_element(By.CLASS_NAME, "swal2-confirm")
    ok_button.click()
    
def teardown_module(module):
    global driver
    if driver is not None:
        driver.quit()
