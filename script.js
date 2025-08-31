// ARQUIVO: script.js
document.addEventListener('DOMContentLoaded', () => {
    const productGrid = document.getElementById('product-grid');
    const searchInput = document.getElementById('searchInput');
    const promoFilter = document.getElementById('promoFilter');
    const sortOptions = document.getElementById('sortOptions');
    const navContainer = document.querySelector('.main-nav');

    const API_URL = 'api/api_produtos.php';
    const SITE_INFO_API_URL = 'api/api_site_info.php';

    let queryParams = { q: '', promocao: false, ordenar: 'alfabetica_asc' };

    // --- LÓGICA DE AUTENTICAÇÃO DO CABEÇALHO ---
    // Alterado para localStorage para persistir o login
    const cliente = JSON.parse(localStorage.getItem('cliente'));

    if (cliente) {
        navContainer.innerHTML = `
            <a href="minha-conta.html" class="nav-link"><i class="fa-solid fa-user"></i> Minha Conta</a>
            <a href="#" id="logoutBtn" class="nav-link"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
            <a href="encomenda.html" class="nav-link cart-link">
                <i class="fa-solid fa-cart-shopping"></i> Encomenda
                <span id="cart-counter" class="cart-counter">0</span>
            </a>
        `;
        document.getElementById('logoutBtn').addEventListener('click', (e) => {
            e.preventDefault();
            localStorage.clear(); // Limpa toda a sessão (cliente e carrinho)
            sessionStorage.clear(); // Limpa também o session storage por garantia
            window.location.href = 'index.html';
        });
    } else {
        navContainer.innerHTML = `
            <a href="login.html" class="nav-link"><i class="fa-solid fa-right-to-bracket"></i> Entrar</a>
            <a href="encomenda.html" class="nav-link cart-link">
                <i class="fa-solid fa-cart-shopping"></i> Encomenda
                <span id="cart-counter" class="cart-counter">0</span>
            </a>
        `;
    }

    // --- LÓGICA DE PRODUTOS ---
    const fetchAndRenderProducts = async () => {
        if (!productGrid) return;
        showLoadingSpinner();

        const url = new URL(API_URL, window.location.href);
        if (queryParams.q) url.searchParams.set('q', queryParams.q);
        if (queryParams.promocao) url.searchParams.set('promocao', 'true');
        url.searchParams.set('ordenar', queryParams.ordenar);

        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error('Não foi possível carregar os produtos.');
            const products = await response.json();
            renderProducts(products);
            fetchStoreStatus();
        } catch (error) {
            productGrid.innerHTML = `<p class="error-message">${error.message}</p>`;
        }
    };

    const showLoadingSpinner = () => {
        productGrid.innerHTML = '<div class="spinner"></div>';
    };

    const renderProducts = (products) => {
        productGrid.innerHTML = '';
        if (!products || products.length === 0) {
            productGrid.innerHTML = `<p class="no-results-message">Nenhum produto encontrado com os filtros atuais.</p>`;
            return;
        }
        products.forEach((product, index) => {
            const card = document.createElement('div');
            card.className = 'product-card';
            card.style.animationDelay = `${index * 0.05}s`;

            const formattedPrice = parseFloat(product.preco).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
            const priceLabel = product.unidade_medida === 'kg' ? `${formattedPrice} / kg` : formattedPrice;
            
            let tagsHtml = '<div class="product-tags">';
            if(product.tags && product.tags.length > 0) {
                product.tags.forEach(tag => {
                    tagsHtml += `<span class="tag">${tag.nome}</span>`;
                });
            }
            tagsHtml += '</div>';

            card.innerHTML = `
                ${product.em_promocao ? '<div class="promo-badge">OFERTA</div>' : ''}
                <img src="${product.imagem_url || 'https://placehold.co/400x400'}" alt="${product.nome}" class="product-image" onerror="this.src='https://placehold.co/400x400/e53935/ffffff?text=X'">
                <div class="product-info">
                    ${tagsHtml}
                    <h3 class="product-name">${product.nome}</h3>
                    <p class="product-price" data-price="${product.preco}">${priceLabel}</p>
                    <button class="add-to-cart-btn" data-id="${product.id_produto}">
                       <i class="fa-solid fa-plus"></i> Adicionar
                   </button>
                </div>
            `;
            productGrid.appendChild(card);
        });
    };

    // --- LÓGICA DE STATUS DA LOJA ---
    const fetchStoreStatus = async () => {
        try {
            const response = await fetch(SITE_INFO_API_URL);
            const config = await response.json();

            if (config && !config.encomendas_ativas) {
                document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
                    btn.disabled = true;
                    btn.classList.add('disabled-btn');
                    btn.innerHTML = `<i class="fa-solid fa-ban"></i> ${config.mensagem_encomendas || 'Indisponível'}`;
                });
            }
        } catch (error) {
            console.error("Erro ao buscar status da loja:", error);
        }
    };

    // --- EVENT LISTENERS ---
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                queryParams.q = searchInput.value;
                fetchAndRenderProducts();
            }, 300);
        });
    }
    if (promoFilter) {
        promoFilter.addEventListener('change', () => {
            queryParams.promocao = promoFilter.checked;
            fetchAndRenderProducts();
        });
    }
    if (sortOptions) {
        sortOptions.addEventListener('change', () => {
            queryParams.ordenar = sortOptions.value;
            fetchAndRenderProducts();
        });
    }
    if (productGrid) {
        productGrid.addEventListener('click', (e) => {
            const btn = e.target.closest('.add-to-cart-btn');
            if (btn && !btn.disabled) {
                const card = btn.closest('.product-card');
                const productData = {
                    id: btn.dataset.id,
                    name: card.querySelector('.product-name').textContent,
                    price: card.querySelector('.product-price').dataset.price,
                    image: card.querySelector('.product-image').src,
                    unit: card.querySelector('.product-price').textContent.includes('/ kg') ? 'kg' : 'un',
                };
                addToCart(productData);
            }
        });
    }

    // --- INICIALIZAÇÃO ---
    fetchAndRenderProducts();
    updateCartCounter();
});

// --- FUNÇÕES GLOBAIS DO CARRINHO ---
function addToCart(product) {
    let cart = JSON.parse(sessionStorage.getItem('cart')) || [];
    const existingItem = cart.find(item => item.id == product.id);

    if (existingItem) {
        existingItem.quantity++;
    } else {
        product.quantity = 1;
        cart.push(product);
    }

    sessionStorage.setItem('cart', JSON.stringify(cart));
    updateCartCounter();
    showAddedToCartFeedback(product.id);
}

function showAddedToCartFeedback(productId) {
    const btn = document.querySelector(`.add-to-cart-btn[data-id='${productId}']`);
    if (btn) {
        const originalText = btn.innerHTML;
        btn.innerHTML = `<i class="fa-solid fa-check"></i> Adicionado!`;
        btn.style.backgroundColor = 'var(--success-green)';
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.style.backgroundColor = '';
        }, 1500);
    }
}

function updateCartCounter() {
    const cart = JSON.parse(sessionStorage.getItem('cart')) || [];
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    const cartCounter = document.getElementById('cart-counter');
    if (cartCounter) {
        cartCounter.textContent = totalItems;
        cartCounter.style.display = totalItems > 0 ? 'flex' : 'none';
    }
}
