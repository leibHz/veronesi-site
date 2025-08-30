// ARQUIVO: admin/dashboard.js
// -----------------------------------------------------------------
// Lógica específica da página do dashboard.

document.addEventListener('DOMContentLoaded', () => {
    // Só executa se não estiver na página de login
    if (window.location.pathname.endsWith('index.html') || window.location.pathname.endsWith('admin/')) {
        return;
    }

    const adminNameEl = document.getElementById('adminName');
    const logoutBtn = document.getElementById('logoutBtn');
    
    // Pega o nome do admin do sessionStorage e exibe na página
    const adminName = sessionStorage.getItem('admin_nome');
    if (adminName) {
        adminNameEl.textContent = `Olá, ${adminName}`;
    }

    // --- LOGOUT ---
    logoutBtn.addEventListener('click', () => {
        sessionStorage.removeItem('admin_nome'); // Remove o estado de login
        window.location.href = 'index.html'; // Redireciona para o login
    });

    // --- CARREGAR ESTATÍSTICAS ---
    const fetchDashboardStats = async () => {
        const statsUrl = '../api/api_admin_dashboard.php';
        try {
            const response = await fetch(statsUrl);
            const stats = await response.json();

            document.getElementById('totalProdutos').textContent = stats.total_produtos;
            document.getElementById('totalClientes').textContent = stats.total_clientes;
            document.getElementById('totalEncomendas').textContent = stats.total_encomendas;

        } catch (error) {
            console.error('Erro ao buscar estatísticas:', error);
            // Poderia adicionar uma mensagem de erro na tela
        }
    };

    // Chama a função para carregar os dados
    fetchDashboardStats();
});
