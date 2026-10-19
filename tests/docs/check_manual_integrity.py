"""Verificar que las fuentes conservadas coinciden con el manifiesto de la auditoría."""
# Comentario: Usar únicamente librería estándar para ejecutar también en GitHub Actions.
import hashlib
import json
from pathlib import Path

# Comentario: Resolver rutas desde el archivo de prueba, independientemente del directorio actual.
ROOT = Path(__file__).resolve().parents[2]
MANUALS = ROOT / "docs/manuales"


def main() -> None:
    # Comentario: Exigir nombres únicos y locales antes de leer cada documento inventariado.
    manifest = json.loads((MANUALS / "manifest.json").read_text(encoding="utf-8"))
    names = set()
    for source in manifest["documentos"]:
        name = source["archivo"]
        if name in names or Path(name).name != name:
            raise ValueError("Nombre de fuente duplicado o fuera del directorio de manuales")
        names.add(name)
        # Comentario: Detectar alteraciones de cualquiera de los originales, incluido el contexto secundario.
        data = (MANUALS / name).read_bytes()
        if len(data) != source["bytes"] or hashlib.sha256(data).hexdigest() != source["sha256"]:
            raise ValueError(f"Integridad documental incorrecta: {name}")
        if name.endswith(".pdf") and not data.startswith(b"%PDF-"):
            raise ValueError(f"Cabecera PDF incorrecta: {name}")
    # Comentario: Informar solo del alcance comprobado; los hashes no acreditan contenido ni revisión instalada.
    print(f"Integridad documental: {len(names)} fuentes coinciden con sus SHA-256 y tamaños.")


# Comentario: Ejecutar la comprobación explícita y propagar errores a la puerta de calidad.
if __name__ == "__main__":
    main()
