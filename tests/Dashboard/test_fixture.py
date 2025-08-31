import pytest
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.webdriver import WebDriver
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.chrome.service import Service as chromeservice 
from webdriver_manager.chrome import ChromeDriverManager


@pytest.fixture(params=["Playwright","Selenium","Cyprest"])
def termino_de_busqueda(request: pytest.FixtureRequest):
    return request.param

@pytest.fixture
def browser():
    service = chromeservice(ChromeDriverManager().install())  
    driver = webdriver.Chrome(service=service)
    driver.get("https://www.google.com")
    yield driver
    driver.quit()
    
def test_google_busqueda(browser: WebDriver,termino_de_busqueda):
    search_box = browser.find_element("name","q")
    search_box.send_keys(termino_de_busqueda + Keys.RETURN)
    
    results = browser.find_element("id","search")
    assert (len (results.find_elements("xpath",".//div")) > 0), "Hay resultados de busqueda"
    
    
    
    