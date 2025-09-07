// Ficheiro: veronesi-site/service-worker.js

// Este ficheiro é executado em segundo plano pelo navegador
// e é responsável por receber e exibir as notificações push.

self.addEventListener('push', function (event) {
    const data = event.data.json(); // Recebe os dados da notificação (título, corpo, etc.)

    const title = data.title || 'Supermercado Veronesi';
    const options = {
        body: data.body,
        icon: './admin/logo.png', // Caminho para um ícone (opcional)
        badge: './admin/logo.png' // Ícone para Android
    };

    event.waitUntil(self.registration.showNotification(title, options));
});
