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
    yield driver
    driver.quit()

def test_login_success(browser):
    browser.get("http://localhost/GestiondeTareas/app/views/auth/login.php") 

    username_field = browser.find_element(By.NAME, "email") 
    password_field = browser.find_element(By.NAME, "password") 
    username_field.send_keys("admin2@escuela.edu") 
    password_field.send_keys("admin456") 

   
    submit_button = browser.find_element(By.CSS_SELECTOR, "button.btn.btn-login[type='submit']") 
    submit_button.click()

    WebDriverWait(browser, 20).until(EC.presence_of_element_located((By.LINK_TEXT, "Panel Principal")))


def test_login_failure(browser):
    browser.get("http://localhost/GestiondeTareas/app/views/auth/login.php") 
    
    username_field = browser.find_element(By.NAME, "email") 
    password_field = browser.find_element(By.NAME, "password") 
    username_field.send_keys("admin02@escuela.edu") 
    password_field.send_keys("admin467") 
    

    submit_button = browser.find_element(By.CSS_SELECTOR, "button.btn.btn-login[type='submit']") 
    submit_button.click()

    error_message = browser.find_element(By.ID, "swal2-html-container") 
    assert error_message.is_displayed()