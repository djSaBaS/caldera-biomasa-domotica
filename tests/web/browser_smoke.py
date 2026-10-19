"""Comprobar login y navegación DOM en Chromium contra una API local de pruebas."""
# Comentario: Mantener configuración, procesos y perfil del navegador aislados del usuario.
import asyncio
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import time
import urllib.parse
import urllib.request

# Comentario: Esta prueba opcional usa la versión fijada en requirements.txt, fuera del runner nativo.
import websockets

ROOT = Path(__file__).resolve().parents[2]
API_URL = os.environ.get("API_BASE_URL", "http://127.0.0.1:8092/api")
# Comentario: Limitar el recorrido a servicios locales; las credenciales son las públicas del seed de pruebas.
if urllib.parse.urlparse(API_URL).hostname not in {"localhost", "127.0.0.1"}:
    raise ValueError("La prueba de navegador exige una API local de pruebas")
HTTP = urllib.request.build_opener(urllib.request.ProxyHandler({}))


async def check_page(debug_url: str) -> None:
    # Comentario: Conectar solo al puerto CDP del perfil efímero creado por esta ejecución.
    async with websockets.connect(debug_url, max_size=4000000) as channel:
        sequence = 0
        errors = []

        async def rpc(method: str, parameters: dict | None = None) -> dict:
            # Comentario: Correlacionar respuestas y recoger excepciones reales sin confundirlas con eventos CDP.
            nonlocal sequence
            sequence += 1
            current = sequence
            await channel.send(json.dumps({"id": current, "method": method, "params": parameters or {}}))
            async with asyncio.timeout(10):
                while True:
                    response = json.loads(await channel.recv())
                    if response.get("method") == "Runtime.exceptionThrown":
                        errors.append(response["params"]["exceptionDetails"].get("text"))
                    if response.get("id") == current:
                        if "error" in response:
                            raise RuntimeError("Error del protocolo CDP")
                        return response.get("result", {})

        async def evaluate(expression: str):
            # Comentario: Ejecutar acciones del DOM real y esperar las promesas de fetch cuando corresponda.
            value = await rpc("Runtime.evaluate", {"expression": expression, "returnByValue": True, "awaitPromise": True})
            if "exceptionDetails" in value:
                raise RuntimeError("Excepción al ejecutar el recorrido del navegador")
            return value.get("result", {}).get("value")

        async def until(expression: str) -> None:
            # Comentario: Esperar una condición observable con plazo acotado, sin pausas largas.
            for _ in range(60):
                if await evaluate(expression):
                    return
                await asyncio.sleep(0.2)
            raise RuntimeError("No se cumplió la condición de interfaz: " + expression)

        # Comentario: Configurar la URL del backend sin alterar los archivos del panel.
        await rpc("Page.enable")
        await rpc("Runtime.enable")
        await rpc("Network.enable")
        await rpc("Page.addScriptToEvaluateOnNewDocument", {"source": "window.APP_API_BASE_URL=" + json.dumps(API_URL) + ";"})
        # Comentario: Probar funcionamiento básico sin CDN; no acreditar diseño visual ni gráficas con esta ejecución.
        await rpc("Network.setBlockedURLs", {"urls": ["https://cdn.jsdelivr.net/*"]})
        await rpc("Page.navigate", {"url": "http://127.0.0.1:8080"})
        checks = 0

        def check(value: bool, message: str) -> None:
            # Comentario: Detenerse en el primer comportamiento funcional incorrecto.
            nonlocal checks
            if not value:
                raise RuntimeError(message)
            checks += 1

        # Comentario: Verificar inicialización sin sesión, contenido de ejemplo y cambio de sección.
        await until("document.querySelector('#dashboardEstadoApi')?.textContent.toLowerCase().includes('sesión')")
        check(await evaluate("document.title==='Caldera Biomasa Domótica'"), "Título incorrecto")
        check(await evaluate("document.querySelector('#kpiGrid').children.length>0"), "Faltan KPIs de ejemplo")
        await evaluate("document.querySelector('[data-section=estado]').click()")
        check(await evaluate("!document.querySelector('#estado').classList.contains('d-none') && document.querySelector('#dashboard').classList.contains('d-none')"), "Navegación DOM incorrecta")
        # Comentario: Ejercitar el formulario y su error con una contraseña ficticia incorrecta.
        await evaluate("document.querySelector('#usuario').value='demo_admin';document.querySelector('#contrasena').value='incorrecta';document.querySelector('#formLogin').requestSubmit()")
        await until("document.querySelector('#loginResultado').classList.contains('alert-danger')")
        check(await evaluate("!document.querySelector('#loginResultado').textContent.includes('SQL')"), "El error expone SQL")
        # Comentario: Autenticar con el seed CI y comprobar CSRF y cookies en peticiones reales del navegador.
        await evaluate("document.querySelector('#contrasena').value='DemoAdmin2026!NoProductiva';document.querySelector('#formLogin').requestSubmit()")
        await until("document.querySelector('#loginResultado').textContent.includes('Sesión iniciada')")
        check(await evaluate("document.querySelector('#loginResultado').textContent.includes('CSRF preparado')"), "Falta CSRF tras login")
        await until("document.querySelector('#dashboardEstadoApi').textContent.toLowerCase().includes('muestra')")
        check(await evaluate("document.querySelector('#kpiGrid').textContent.includes('°C')"), "Dashboard sin temperaturas")
        check(await evaluate("document.body.textContent.includes('Simulación segura')"), "Falta identificación de simulación")
        check(await evaluate("fetch(window.APP_API_BASE_URL+'/dashboard.php?device_id=caldera-01',{credentials:'include'}).then(r=>r.json()).then(d=>d.success && d.data.source==='database')"), "Cookie o dashboard MySQL incorrectos")
        check(not errors, "Se detectaron excepciones JavaScript")
        print(f"Navegador Chromium: {checks} comprobaciones funcionales correctas; CDN bloqueado, sin validación visual ni gráficas.")


def main() -> None:
    # Comentario: Localizar un navegador del sistema sin descargar binarios durante esta prueba opcional.
    binary = shutil.which("chromium") or shutil.which("google-chrome")
    if binary is None:
        raise RuntimeError("Instalar Chromium o Google Chrome para este recorrido opcional")
    with tempfile.TemporaryDirectory(prefix="caldera-browser-") as profile:
        # Comentario: Servir solo web en el origen permitido por CORS y recoger únicamente nuestros procesos.
        server = subprocess.Popen(["python3", "-m", "http.server", "8080", "--bind", "127.0.0.1", "--directory", str(ROOT / "web")], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        browser = None
        try:
            # Comentario: El contenedor de pruebas exige no-sandbox; usar perfil efímero y CDP solo en loopback.
            browser = subprocess.Popen([binary, "--headless", "--no-sandbox", "--disable-gpu", "--disable-dev-shm-usage", "--no-first-run", "--no-default-browser-check", "--no-proxy-server", "--remote-debugging-port=0", "--user-data-dir=" + profile, "about:blank"], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
            active_port = Path(profile) / "DevToolsActivePort"
            # Comentario: Esperar la apertura del puerto propio sin reutilizar un navegador preexistente.
            for _ in range(40):
                if server.poll() is not None:
                    raise RuntimeError("El puerto web 8080 debe estar libre")
                if active_port.exists():
                    break
                time.sleep(0.2)
            else:
                raise RuntimeError("Chromium no abrió CDP")
            port = int(active_port.read_text().splitlines()[0])
            with HTTP.open(f"http://127.0.0.1:{port}/json", timeout=2) as response:
                tabs = json.load(response)
            asyncio.run(check_page(tabs[0]["webSocketDebuggerUrl"]))
        finally:
            # Comentario: Detener solo los procesos creados por la prueba, incluso cuando una aserción falla.
            if browser is not None:
                browser.terminate()
                browser.wait(timeout=10)
            server.terminate()
            server.wait(timeout=10)


# Comentario: Exigir ejecución explícita contra el seed CI; no se integra implícitamente en despliegues.
if __name__ == "__main__":
    main()
