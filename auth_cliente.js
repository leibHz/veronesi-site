// ARQUIVO: auth_cliente.js (COM TRATAMENTO DE ERRO MELHORADO)
document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const cadastroForm = document.getElementById('cadastroForm');

    // Se o formulário de login existir na página
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const errorMessageEl = document.getElementById('errorMessage');
            errorMessageEl.textContent = '';

            try {
                const response = await fetch('api/api_cliente_login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(Object.fromEntries(new FormData(loginForm).entries()))
                });
                const result = await response.json();
                if (!response.ok) throw result;

                sessionStorage.setItem('cliente', JSON.stringify(result.cliente));
                window.location.href = 'index.html';
            } catch (error) {
                errorMessageEl.textContent = error.message || 'Erro de conexão. Tente novamente.';
            }
        });
    }

    // Se o formulário de cadastro existir na página
    if (cadastroForm) {
        cadastroForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const errorMessageEl = document.getElementById('errorMessage');
            errorMessageEl.textContent = '';
            const formData = new FormData(cadastroForm);
            const data = Object.fromEntries(formData.entries());

            if (data.senha !== data.confirmar_senha) {
                errorMessageEl.textContent = 'As senhas não coincidem.';
                return;
            }

            try {
                const response = await fetch('api/api_cliente_cadastro.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                // Tenta analisar a resposta como JSON, independentemente do status
                const result = await response.json();

                // Se a resposta NÃO for OK (ex: erro 409, 500), lança o erro com a mensagem da API
                if (!response.ok) {
                    throw result;
                }

                // Se a resposta for OK
                sessionStorage.setItem('email_para_verificacao', data.email);
                window.location.href = 'verificacao.html';

            } catch (error) {
                // Agora, o 'error' pode ser o objeto JSON da API ou um erro de rede
                console.error('Falha no cadastro - Detalhes:', error);
                // Se o erro tiver uma mensagem (vindo da API), mostra-a. Senão, mostra a mensagem genérica.
                let displayMessage = 'Ocorreu um erro. Tente novamente.';
                if (error && error.message) {
                    displayMessage = error.message;
                    // Se houver detalhes extras (como no erro crítico), adiciona-os para depuração
                    if (error.error_details) {
                        displayMessage += ` (Detalhe: ${error.error_details})`;
                    }
                }
                errorMessageEl.textContent = displayMessage;
            }
        });
    }
});

