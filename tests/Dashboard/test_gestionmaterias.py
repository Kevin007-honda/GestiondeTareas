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
    username_field.send_keys("admin2@escuela.edu")
    password_field.send_keys("admin456")
    submit_button = browser.find_element(By.CSS_SELECTOR, "button.btn.btn-login[type='submit']")
    submit_button.click()
    WebDriverWait(browser, 30).until(EC.visibility_of_element_located((By.LINK_TEXT, "Panel Principal")))
    materias_button = browser.find_element(By.LINK_TEXT, "Gestión de Materias")
    materias_button.click()
    WebDriverWait(browser, 50).until(EC.presence_of_element_located((By.XPATH, "//h1[@class='mt-4' and contains(text(), 'Gestión de Materias')]")))
    pickle.dump(browser.get_cookies(), open("cookies.pkl", "wb"))
    
def test_crear_materia(browser):
    browser.get("http://localhost/GestiondeTareas/?page=subject_management&role=admin")
    cookies = pickle.load(open("cookies.pkl", "rb"))
    for cookie in cookies:
        browser.add_cookie(cookie)
    browser.get("http://localhost/GestiondeTareas/?page=subject_management&role=admin")
    WebDriverWait(browser, 30).until(EC.presence_of_element_located((By.XPATH, "//label[@class='form-label' and text()='Nombre de la Materia']")))

    WebDriverWait(browser, 30).until(EC.presence_of_element_located((By.ID, "nombre"))) 
    nombre_materia_field = browser.find_element(By.ID, "nombre")   
    codigo_materia_field = browser.find_element(By.ID, "codigo")
    descripcion_materia_field = browser.find_element(By.ID, "descripcion") 
    crear_materia_button = browser.find_element(By.XPATH, "//button[@type='submit' and @class='btn btn-primary']") 

    nombre_materia_field.send_keys("Materia de Prueba Automatizado")
    descripcion_materia_field.send_keys("Esta es una materia creada automáticamente.")
    codigo_materia_field.send_keys("1234")
    browser.execute_script("arguments[0].scrollIntoView(true);", crear_materia_button)
    browser.execute_script("arguments[0].click();", crear_materia_button)
    
    try:
        WebDriverWait(browser, 20).until(EC.visibility_of_element_located((By.ID, "swal2-title")))
        ok_button = WebDriverWait(browser, 20).until(EC.element_to_be_clickable((By.CLASS_NAME, "swal2-confirm")))
        ok_button.click()

    except TimeoutException:
        print("La alerta o el botón 'OK' no se encontraron dentro del tiempo de espera.")
        raise
    except Exception as e:
        print(f"Ocurrió un error: {e}")
        raise
        time.sleep(2)
    
def teardown_module(module):
    global driver
    if driver is not None:
        driver.quit()