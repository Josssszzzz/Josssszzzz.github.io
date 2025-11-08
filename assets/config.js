window.APP_CONFIG = {
    dataApi: {
        /**
         * URL base de la Data API. Ejemplo:
         * https://data.mongodb-api.com/app/<tu-app-id>/endpoint/data/v1
         */
        baseUrl: 'https://data.mongodb-api.com/app/<tu-app-id>/endpoint/data/v1',
        /**
         * Clave de API generada en MongoDB Atlas. Recuerda que al exponerla en un sitio público
         * cualquier persona con acceso podrá interactuar con la colección configurada.
         */
        apiKey: '<tu-api-key>',
        /** Nombre del cluster configurado en la Data API. */
        dataSource: 'Cluster0',
        /** Base de datos que contiene la colección de pautas. */
        database: 'tv_monitor',
        /** Nombre de la colección donde se almacenan las pautas. */
        collection: 'campaigns',
    },
};
