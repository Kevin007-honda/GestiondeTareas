import pytest
from selenium import webdriver
from selenium.webdriver.chrome.service import Service as chromeservice 
from webdriver_manager.chrome import ChromeDriverManager

driver = webdriver.Chrome(service=chromeservice(ChromeDriverManager().install()))

def test_Prueba():
    driver.get("https://www.freerangetesters.com")
    titulo = driver.title
    assert titulo == "Free Range Tuster"