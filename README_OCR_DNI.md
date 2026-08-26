# OCR de DNI - imágenes y PDF escaneados

## Flujo implementado

- JPG, PNG y WEBP se preparan en el navegador y se envían al OCR existente.
- Los PDF se renderizan en el navegador con PDF.js 4.10.38 y se convierten temporalmente a JPG para OCR.
- Se analizan como máximo las primeras 2 páginas del PDF y se detiene la lectura en cuanto se obtienen nombres y DNI.
- El PDF original se conserva como archivo del registro; la conversión a imagen se usa únicamente para la lectura automática.
- El formulario sigue permitiendo corregir manualmente los datos antes de enviar.

## Dependencia PDF

La pantalla carga PDF.js 4.10.38 desde cdnjs únicamente cuando el usuario selecciona un PDF. Los bytes del DNI no se envían al CDN; el renderizado del PDF ocurre en el navegador del usuario. Si PDF.js no puede cargarse, el registro puede completarse manualmente o con una imagen.

## Formatos

- JPG
- JPEG
- PNG
- WEBP
- PDF
- Máximo del archivo original: 10 MB
