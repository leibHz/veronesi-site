// ARQUIVO: cart.js (CORRIGIDO)
document.addEventListener('DOMContentLoaded', () => {
    const cartContainer = document.getElementById('cart-container');
    const cartSummary = document.getElementById('cart-summary');
    const totalPriceEl = document.getElementById('total-price');
    const checkoutBtn = document.getElementById('checkout-btn');

    // **CORREÇÃO:** Caminho relativo para a API, tornando o projeto mais portável.
    const API_CRIAR_ENCOMENDA = 'api/api_encomenda_criar.php';

    // Função para renderizar o carrinho na tela
    const renderCart = () => {
        const cart = JSON.parse(sessionStorage.getItem('cart')) || [];
        cartContainer.innerHTML = '';

        if (cart.length === 0) {
            cartContainer.innerHTML = '<p class="empty-cart-message">Sua encomenda está vazia.</p>';
            cartSummary.style.display = 'none';
            return;
        }

        cartSummary.style.display = 'block';
        let total = 0;

        cart.forEach(item => {
            const itemTotal = item.price * item.quantity;
            total += itemTotal;

            const cartItem = document.createElement('div');
            cartItem.className = 'cart-item';
            cartItem.innerHTML = `
                <img src="${item.image || 'https://placehold.co/80x80/1e1e1e/ffffff?text=Produto'}" alt="${item.name}" class="cart-item-img">
                <div class="cart-item-info">
                    <h4>${item.name}</h4>
                    <p class="cart-item-price">${formatCurrency(item.price)} / ${item.unit}</p>
                </div>
                <div class="cart-item-actions">
                    <input type="number" class="quantity-input" value="${item.quantity}" min="1" step="${item.unit === 'kg' ? '0.1' : '1'}" data-id="${item.id}">
                    <button class="remove-btn" data-id="${item.id}"><i class="fa-solid fa-trash-can"></i></button>
                </div>
            `;
            cartContainer.appendChild(cartItem);
        });

        totalPriceEl.textContent = formatCurrency(total);
    };

    // Função para formatar o valor como moeda brasileira
    const formatCurrency = (value) => {
        return parseFloat(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    };

    // Lida com eventos no container do carrinho
    cartContainer.addEventListener('click', (e) => {
        const removeBtn = e.target.closest('.remove-btn');
        if (removeBtn) {
            const id = removeBtn.dataset.id;
            removeFromCart(id);
        }
    });

    cartContainer.addEventListener('change', (e) => {
        const quantityInput = e.target.closest('.quantity-input');
        if (quantityInput) {
            const id = quantityInput.dataset.id;
            const newQuantity = parseFloat(quantityInput.value);
            updateQuantity(id, newQuantity);
        }
    });
    
    // Funções de manipulação do carrinho
    const updateQuantity = (id, quantity) => {
        let cart = JSON.parse(sessionStorage.getItem('cart')) || [];
        const itemIndex = cart.findIndex(item => item.id == id);
        if (itemIndex > -1 && quantity > 0) {
            cart[itemIndex].quantity = quantity;
        }
        sessionStorage.setItem('cart', JSON.stringify(cart));
        renderCart();
        updateCartCounter();
    };

    const removeFromCart = (id) => {
        let cart = JSON.parse(sessionStorage.getItem('cart')) || [];
        cart = cart.filter(item => item.id != id);
        sessionStorage.setItem('cart', JSON.stringify(cart));
        renderCart();
        updateCartCounter();
    };
    
    // Finalizar a encomenda
    checkoutBtn.addEventListener('click', async () => {
        const cliente = JSON.parse(sessionStorage.getItem('cliente'));
        const cart = JSON.parse(sessionStorage.getItem('cart')) || [];

        if (!cliente) {
            alert('Você precisa estar logado para finalizar a encomenda.');
            window.location.href = 'login.html?redirect=encomenda.html'; // Redireciona para o login
            return;
        }

        if (cart.length === 0) {
            alert('Sua encomenda está vazia.');
            return;
        }

        // Prepara os dados para enviar para a API
        const orderData = {
            id_cliente: cliente.id_cliente,
            itens: cart.map(item => ({ id: item.id, quantity: item.quantity, price: item.price }))
        };
        
        try {
            checkoutBtn.disabled = true;
            checkoutBtn.textContent = 'Processando...';

            const response = await fetch(API_CRIAR_ENCOMENDA, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(orderData)
            });

            const result = await response.json();
            if (response.ok) {
                alert('Encomenda realizada com sucesso!');
                sessionStorage.removeItem('cart'); // Limpa o carrinho
                window.location.href = 'minha-conta.html'; // Redireciona para o histórico
            } else {
                // Lança um erro com a mensagem vinda da API
                throw new Error(result.message || 'Ocorreu um erro desconhecido.');
            }

        } catch (error) {
            alert(`Erro ao finalizar a encomenda: ${error.message}`);
            checkoutBtn.disabled = false;
            checkoutBtn.textContent = 'Finalizar Encomenda';
        }
    });

    // Inicialização
    renderCart();
});
