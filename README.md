# Plataforma de Monitoreo de Pautas

Interfaz web estática (HTML + JavaScript) para gestionar la programación y seguimiento de pautas publicitarias conectada a MongoDB Atlas mediante la Data API. Está pensada para funcionar en GitHub Pages o cualquier hosting estático, por lo que toda la lógica se ejecuta en el navegador.

> ⚠️ **Importante:** Al consumir directamente la Data API desde el navegador deberás exponer una clave de API. Configura reglas restrictivas en Atlas para limitar qué operaciones se permiten y desde qué dominios pueden ejecutarse.

## Configuración

1. Abre el archivo [`assets/config.js`](assets/config.js) y reemplaza los valores de ejemplo por los de tu proyecto de Atlas:

   ```js
   window.APP_CONFIG = {
       dataApi: {
           baseUrl: 'https://data.mongodb-api.com/app/<tu-app-id>/endpoint/data/v1',
           apiKey: '<tu-api-key>',
           dataSource: 'Cluster0',
           database: 'tv_monitor',
           collection: 'campaigns',
       },
   };
   ```

   - `baseUrl`: URL base entregada por Atlas al habilitar la Data API.
   - `apiKey`: clave de API con permisos para la colección.
   - `dataSource`: nombre del cluster configurado en la Data API.
   - `database`: nombre de la base de datos donde vive la colección.
   - `collection`: colección que almacenará las pautas.

2. En la sección **App Services &rarr; Data API** de tu proyecto de Atlas habilita CORS para el dominio donde publicarás la página (por ejemplo, `https://tuusuario.github.io`).

3. Asegúrate de que la colección especificada acepte los campos utilizados por la interfaz (`title`, `channel`, `scheduledAt`, `durationSeconds`, `assetUrl`, `notes`, `status`, `createdAt`, `updatedAt`). La Data API es schemaless, por lo que no necesitas definirlos previamente.

## Uso

1. Publica el repositorio en GitHub Pages (o abre `index.html` en tu equipo con un servidor estático como `npx serve`).
2. Completa el formulario para registrar una pauta. Los datos se envían directamente a MongoDB Atlas usando la Data API.
3. Utiliza los botones de la tabla para filtrar por canal, marcar una pauta como completada o eliminarla.

Los cambios se reflejarán inmediatamente en tu colección de Atlas.

## Recomendaciones de seguridad

- Crea una clave de API con permisos limitados únicamente sobre la colección de pautas.
- Restringe el dominio de origen en la configuración de CORS de Atlas.
- Considera crear reglas de validación en Atlas App Services para impedir operaciones no deseadas.
- Si necesitas proteger credenciales, despliega un backend intermedio en lugar de usar la Data API directamente desde el navegador.
