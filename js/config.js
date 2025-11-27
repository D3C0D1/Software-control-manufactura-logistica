// Configuración de rutas para JavaScript
class Config {
    constructor() {
        this.baseUrl = this.detectBaseUrl();
        this.basePath = this.detectBasePath();
    }

    detectBaseUrl() {
        const protocol = window.location.protocol;
        const host = window.location.host;
        return `${protocol}//${host}`;
    }

    detectBasePath() {
        const pathname = window.location.pathname;
        // Si estamos en localhost, probablemente tenemos /glamcity/ en la ruta
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            // Extraer el path base desde la URL actual
            const pathParts = pathname.split('/');
            if (pathParts.length > 1 && pathParts[1] === 'glamcity') {
                return '/glamcity';
            }
            return '/glamcity'; // Por defecto en local
        }
        // En producción (Hostinger), normalmente está en la raíz
        return '';
    }

    getApiUrl(endpoint) {
        // Remover la barra inicial si existe
        endpoint = endpoint.replace(/^\//, '');
        return `${this.baseUrl}${this.basePath}/${endpoint}`;
    }

    isLocalEnvironment() {
        return window.location.hostname === 'localhost' || 
               window.location.hostname === '127.0.0.1' || 
               window.location.hostname.includes('.local');
    }
}

// Crear instancia global
window.appConfig = new Config();

// Función helper para obtener URLs de API
window.getApiUrl = function(endpoint) {
    return window.appConfig.getApiUrl(endpoint);
};

// Log de configuración para debugging (solo en local)
if (window.appConfig.isLocalEnvironment()) {
    console.log('App Config:', {
        baseUrl: window.appConfig.baseUrl,
        basePath: window.appConfig.basePath,
        isLocal: window.appConfig.isLocalEnvironment()
    });
}