// ARQUIVO: auth_cliente.js (CRIE ESTE NOVO ARQUIVO na pasta raiz)
document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const cadastroForm = document.getElementById('cadastroForm');

    // Se o formulário de login existir na página
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const errorMessageEl = document.getElementById('errorMessage');
            errorMessageEl.textContent = '';

            const formData = new FormData(loginForm);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('api/api_cliente_login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (response.ok) {
                    // Salva os dados do cliente no sessionStorage
                    sessionStorage.setItem('cliente', JSON.stringify(result.cliente));
                    window.location.href = 'index.html'; // Redireciona para a página principal
                } else {
                    errorMessageEl.textContent = result.message;
                }
            } catch (error) {
                errorMessageEl.textContent = 'Erro de conexão. Tente novamente.';
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

            try {
                const response = await fetch('api/api_cliente_cadastro.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (response.ok) {
                    alert('Cadastro realizado com sucesso! Você será redirecionado para a página de login.');
                    window.location.href = 'login.html';
                } else {
                    errorMessageEl.textContent = result.message;
                }
            } catch (error) {
                errorMessageEl.textContent = 'Erro de conexão. Tente novamente.';
            }
        });
    }
});
