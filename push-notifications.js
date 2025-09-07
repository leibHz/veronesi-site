// Ficheiro: veronesi-site/push-notifications.js

// Chave pública VAPID (deve ser a mesma do config.php)
const VAPID_PUBLIC_KEY = 'BL-VAB4fZOhyco0eMUvU1uUevvs0ctR5mSI-kRHrMLmyIS2BoUb4iGwZ_l2bCct8JdxwI5XMKqPoG2a_eA2UjBY';

// Função para converter a chave VAPID para o formato necessário
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

// Função principal para iniciar o processo de subscrição
async function subscribeUser() {
    console.log('Botão clicado, a tentar inscrever...');
    try {
        console.log('A registar o Service Worker...');
        const registration = await navigator.serviceWorker.register('./service-worker.js');
        console.log('Service Worker registado:', registration);
        
        await navigator.serviceWorker.ready; 
        console.log('Service Worker pronto.');

        console.log('A pedir permissão ao usuário...');
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
        });

        console.log('Usuário inscrito com sucesso:', subscription);

        // CORREÇÃO: Prepara o corpo da requisição e verifica se o cliente está logado
        // para enviar o ID junto.
        const cliente = JSON.parse(sessionStorage.getItem('cliente'));
        const postData = {
            subscription: subscription,
            id_cliente: cliente ? cliente.id_cliente : null
        };

        console.log('A enviar inscrição para o servidor...', postData);
        // O caminho para a API deve ser relativo à raiz do site
        const response = await fetch('api/api_salvar_inscricao.php', {
            method: 'POST',
            body: JSON.stringify(postData),
            headers: {
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            const result = await response.json();
            throw new Error(result.message || 'Falha ao guardar a inscrição no servidor.');
        }

        console.log('Inscrição guardada no servidor.');
        alert('Notificações ativadas com sucesso!');
        document.getElementById('notification-button').style.display = 'none';

    } catch (error) {
        console.error('Falha ao inscrever o usuário: ', error);
        if (Notification.permission === 'denied') {
            alert('Você bloqueou as notificações. Para ativá-las, precisa de alterar as permissões do site no seu navegador.');
        } else {
            alert(`Não foi possível ativar as notificações: ${error.message}`);
        }
    }
}


// Adiciona o evento de clique ao botão e verifica o estado da permissão quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', () => {
    const notificationButton = document.getElementById('notification-button');
    if (!notificationButton) return;

    if (!('serviceWorker' in navigator && 'PushManager' in window)) {
        notificationButton.style.display = 'none'; 
        return;
    }
    
    // Se o navegador já tem a permissão (garantida ou negada), esconde o botão
    navigator.serviceWorker.ready.then(reg => {
        reg.pushManager.getSubscription().then(subscription => {
            if (subscription || Notification.permission === 'denied') {
                notificationButton.style.display = 'none';
            }
        });
    });

    notificationButton.addEventListener('click', subscribeUser);
});
