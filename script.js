// ARQUIVO: script.js (CORRIGIDO E MELHORADO)
document.addEventListener('DOMContentLoaded', () => {
    const productGrid = document.getElementById('product-grid');
    const searchInput = document.getElementById('searchInput');
    const promoFilter = document.getElementById('promoFilter');
    const sortOptions = document.getElementById('sortOptions');
    const navContainer = document.querySelector('.main-nav');

    const API_URL = 'api/api_produtos.php';
    const SITE_INFO_API_URL = 'api/api_site_info.php';

    let queryParams = { q: '', promocao: false, ordenar: 'alfabetica_asc' };
    let siteConfig = null; // Guarda a configuração do site

    // --- LÓGICA DE AUTENTICAÇÃO DO CABEÇALHO ---
    const cliente = JSON.parse(sessionStorage.getItem('cliente'));

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
            sessionStorage.clear();
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

    // --- LÓGICA DE PRODUTOS E STATUS DA LOJA ---
    const fetchSiteInfo = async () => {
        try {
            const response = await fetch(SITE_INFO_API_URL);
            if (!response.ok) throw new Error('Falha ao buscar informações do site.');
            siteConfig = await response.json();
            displayStoreStatus(siteConfig);
        } catch (error) {
            console.error(error.message);
        }
    };

    const displayStoreStatus = (config) => {
        const banner = document.getElementById('store-status-banner');
        if (!banner || !config) return;
        
        banner.textContent = config.mensagem_loja_real || (config.pode_encomendar ? 'Loja aberta!' : 'Loja fechada.');
        banner.className = config.pode_encomendar ? 'status-aberto' : 'status-fechado';
        banner.style.display = 'block';
    };

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
            renderProducts(products, siteConfig);
        } catch (error) {
            productGrid.innerHTML = `<p class="error-message">${error.message}</p>`;
        }
    };

    const showLoadingSpinner = () => {
        productGrid.innerHTML = '<div class="spinner"></div>';
    };

    const renderProducts = (products, config) => {
        productGrid.innerHTML = '';
        if (!products || products.length === 0) {
            productGrid.innerHTML = `<p class="no-results-message">Nenhum produto encontrado com os filtros atuais.</p>`;
            return;
        }

        const podeEncomendar = config ? config.pode_encomendar : false;
        const mensagemFechado = config ? config.mensagem_loja_real : 'Indisponível';

        products.forEach((product, index) => {
            const card = document.createElement('div');
            card.className = 'product-card';
            card.style.animationDelay = `${index * 0.05}s`;

            const formattedPrice = parseFloat(product.preco).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
            const priceLabel = product.unidade_medida === 'kg' ? `${formattedPrice} / kg` : formattedPrice;

            card.innerHTML = `
                ${product.em_promocao ? '<div class="promo-badge">OFERTA</div>' : ''}
                <img src="${product.imagem_url || 'https://placehold.co/400x400'}" alt="${product.nome}" class="product-image" onerror="this.src='https://placehold.co/400x400/e53935/ffffff?text=X'">
                <div class="product-info">
                    <h3 class="product-name">${product.nome}</h3>
                    <p class="product-price" data-price="${product.preco}">${priceLabel}</p>
                    <button class="add-to-cart-btn ${!podeEncomendar ? 'disabled-btn' : ''}" 
                            data-id="${product.id_produto}" 
                            ${!podeEncomendar ? 'disabled' : ''}>
                        ${podeEncomendar ? '<i class="fa-solid fa-plus"></i> Adicionar' : `<i class="fa-solid fa-ban"></i> ${mensagemFechado}`}
                    </button>
                </div>
            `;
            productGrid.appendChild(card);
        });
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
    const initPage = async () => {
        await fetchSiteInfo();
        await fetchAndRenderProducts();
        updateCartCounter();
    };

    initPage();
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
