import os
from PIL import Image

# 📁 Ruta base de las imágenes
BASE_DIR = "resources/js/Assets/Images/Logos"

# 🔧 Calidad y configuración
QUALITY = 90  # entre 80 y 100 es óptimo
SUPPORTED_FORMATS = (".png", ".jpg", ".jpeg")

# 🔁 Recorre todos los archivos en la carpeta
for root, _, files in os.walk(BASE_DIR):
    for filename in files:
        if filename.lower().endswith(SUPPORTED_FORMATS):
            input_path = os.path.join(root, filename)
            output_path = os.path.splitext(input_path)[0] + ".webp"

            # Evitar sobrescribir si ya existe un .webp
            if os.path.exists(output_path):
                print(f"🟡 Ya existe: {output_path}")
                continue

            try:
                with Image.open(input_path) as img:
                    img = img.convert("RGBA")  # Mantiene transparencia si la hay
                    img.save(output_path, "WEBP", quality=QUALITY, method=6, lossless=True)
                    print(f"✅ Convertido: {output_path}")
            except Exception as e:
                print(f"❌ Error al convertir {input_path}: {e}")
