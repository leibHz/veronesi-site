document.addEventListener('DOMContentLoaded', () => {
    const productGrid = document.getElementById('product-grid');
    const searchInput = document.getElementById('search-input');
    const sortSelect = document.getElementById('sort-select');
    const promocaoFilter = document.getElementById('promocao-filter');
    const sessoesContainer = document.getElementById('filtros-sessoes');
    const tagFiltersContainer = document.getElementById('filtros-tags');
    const statusTexto = document.getElementById('status-texto');
    const statusMercadoDiv = document.getElementById('status-mercado');
    const loginBtn = document.getElementById('login-btn-public');
    const logoutBtn = document.getElementById('logout-btn');
    const userMenu = document.getElementById('user-menu');
    const userNameEl = document.getElementById('user-name');
    const myAccountBtn = document.getElementById('my-account-btn');
    const encomendarBtn = document.getElementById('encomendar-btn');
    const authModal = document.getElementById('auth-modal');
    const authContent = document.getElementById('auth-content');
    const cartModal = document.getElementById('cart-modal');
    const cartContent = document.getElementById('cart-content');
    const ordersModal = document.getElementById('orders-modal');
    const ordersContent = document.getElementById('orders-content');

    let state = { search: '', sort: 'nome_asc', promocao: false, sessao_id: 'all', tag_id: 'all', user: null, cart: [] };

    const API_URL = './api';
    const AUTH_API_URL = `${API_URL}/auth_handler.php`;
    const ENCOMENDA_API_URL = `${API_URL}/encomenda_handler.php`;

    const checkSession = async () => { try { const response = await fetch(`${AUTH_API_URL}?action=check_session`); const data = await response.json(); if (data.loggedIn) { state.user = data.cliente; updateUIAfterLogin(); } } catch (error) { console.error("Erro ao verificar sessão:", error); } };
    const updateUIAfterLogin = () => { if (state.user) { loginBtn.classList.add('hidden'); userMenu.classList.remove('hidden'); userMenu.classList.add('flex'); userNameEl.textContent = state.user.nome.split(' ')[0]; } };
    const updateUIAfterLogout = () => { loginBtn.classList.remove('hidden'); userMenu.classList.add('hidden'); userMenu.classList.remove('flex'); userNameEl.textContent = ''; state.user = null; state.cart = []; updateCartCount(); };
    const handleLogout = async () => { await fetch(`${AUTH_API_URL}?action=logout`, { method: 'POST' }); updateUIAfterLogout(); };
    
    const showAuthModal = (isLogin = true) => { authContent.innerHTML = isLogin ? getLoginFormHTML() : getRegisterFormHTML(); authModal.classList.remove('hidden'); attachAuthFormListeners(isLogin); };
    const hideAuthModal = () => authModal.classList.add('hidden');
    const attachAuthFormListeners = (isLogin) => { const form = document.getElementById(isLogin ? 'login-form' : 'register-form'); form.addEventListener('submit', (e) => handleAuthSubmit(e, isLogin)); document.getElementById('switch-link').addEventListener('click', (e) => { e.preventDefault(); showAuthModal(!isLogin); }); authModal.addEventListener('click', (e) => { if (e.target === authModal) hideAuthModal(); }); };
    const handleAuthSubmit = async (e, isLogin) => { e.preventDefault(); const form = e.target; const data = Object.fromEntries(new FormData(form).entries()); const action = isLogin ? 'login' : 'register'; try { const response = await fetch(`${AUTH_API_URL}?action=${action}`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) }); const result = await response.json(); if (result.success) { if (isLogin) { state.user = result.cliente; updateUIAfterLogin(); } hideAuthModal(); alert(result.message); } else { throw new Error(result.error); } } catch (error) { alert(`Erro: ${error.message}`); } };
    const getLoginFormHTML = () => `<h2 class="text-2xl font-bold text-center mb-6">Acessar Conta</h2><form id="login-form"><div class="mb-4"><label class="block mb-1 font-medium">E-mail</label><input type="email" name="email" class="w-full p-2 border rounded" required></div><div class="mb-6"><label class="block mb-1 font-medium">Senha</label><input type="password" name="senha" class="w-full p-2 border rounded" required></div><button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700">Entrar</button></form><p class="text-center text-sm mt-4">Não tem uma conta? <a href="#" id="switch-link" class="text-blue-600 font-semibold">Cadastre-se</a></p>`;
    const getRegisterFormHTML = () => `<h2 class="text-2xl font-bold text-center mb-6">Criar Conta</h2><form id="register-form"><div class="mb-4"><label class="block mb-1 font-medium">Nome Completo</label><input type="text" name="nome_completo" class="w-full p-2 border rounded" required></div><div class="mb-4"><label class="block mb-1 font-medium">E-mail</label><input type="email" name="email" class="w-full p-2 border rounded" required></div><div class="mb-4"><label class="block mb-1 font-medium">Senha</label><input type="password" name="senha" class="w-full p-2 border rounded" required></div><button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700">Criar Conta</button></form><p class="text-center text-sm mt-4">Já tem uma conta? <a href="#" id="switch-link" class="text-blue-600 font-semibold">Faça Login</a></p>`;
    
    const updateCartCount = () => { document.getElementById('cart-count').textContent = state.cart.reduce((sum, item) => sum + item.quantity, 0); };
    const handleAddToCart = (product) => { if (!state.user) { alert("Você precisa fazer login para adicionar produtos à sua encomenda."); showAuthModal(); return; } const existingItem = state.cart.find(item => item.id === product.id); if (existingItem) { existingItem.quantity++; } else { state.cart.push({ ...product, quantity: 1 }); } updateCartCount(); };
    const showCartModal = () => { if (state.cart.length === 0) { alert("Sua sacola de encomendas está vazia."); return; } cartModal.classList.remove('hidden'); renderCartContent(); };
    const hideCartModal = () => cartModal.classList.add('hidden');
    const renderCartContent = () => { let total = 0; let itemsHTML = state.cart.map(item => { const subtotal = item.preco * item.quantity; total += subtotal; return `<div class="flex items-center justify-between py-3 border-b"><div class="flex items-center gap-4"><img src="${item.imagem_url || 'https://placehold.co/100x100/EFEFEF/333?text=Img'}" class="w-16 h-16 rounded-md object-cover"><div><p class="font-semibold">${item.nome}</p><p class="text-sm text-gray-600">${parseFloat(item.preco).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}</p></div></div><div class="flex items-center gap-4"><input type="number" min="1" value="${item.quantity}" data-id="${item.id}" class="cart-item-qty w-16 p-1 border rounded text-center"><p class="font-semibold w-24 text-right">${subtotal.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}</p><button data-id="${item.id}" class="remove-cart-item text-red-500 hover:text-red-700">&times;</button></div></div>`; }).join(''); cartContent.innerHTML = `<h2 class="text-2xl font-bold mb-4">Minha Encomenda</h2><div class="max-h-96 overflow-y-auto pr-2">${itemsHTML}</div><div class="mt-6 text-right"><p class="text-lg">Total: <span class="font-bold text-2xl">${total.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}</span></p><p class="text-xs text-gray-500 mt-1">O pagamento será realizado na retirada.</p><div class="mt-6 flex justify-end gap-4"><button id="close-cart-btn" class="bg-gray-200 px-6 py-2 rounded-lg font-semibold hover:bg-gray-300">Continuar Comprando</button><button id="finalize-btn" class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700">Finalizar Encomenda</button></div></div>`; document.getElementById('close-cart-btn').addEventListener('click', hideCartModal); document.getElementById('finalize-btn').addEventListener('click', handleFinalizeOrder); document.querySelectorAll('.cart-item-qty').forEach(input => input.addEventListener('change', handleQtyChange)); document.querySelectorAll('.remove-cart-item').forEach(btn => btn.addEventListener('click', handleRemoveItem)); cartModal.addEventListener('click', (e) => { if (e.target === cartModal) hideCartModal(); }); };
    const handleQtyChange = (e) => { const productId = e.target.dataset.id; const newQty = parseInt(e.target.value); const itemInCart = state.cart.find(item => item.id == productId); if (itemInCart && newQty > 0) { itemInCart.quantity = newQty; updateCartCount(); renderCartContent(); } };
    const handleRemoveItem = (e) => { const productId = e.target.dataset.id; state.cart = state.cart.filter(item => item.id != productId); updateCartCount(); if (state.cart.length === 0) hideCartModal(); else renderCartContent(); };
    const handleFinalizeOrder = async () => { const finalizeBtn = document.getElementById('finalize-btn'); finalizeBtn.disabled = true; finalizeBtn.textContent = 'Enviando...'; const orderData = { cart: state.cart.map(item => ({ id: item.id, quantity: item.quantity })) }; try { const response = await fetch(`${ENCOMENDA_API_URL}?action=create_encomenda`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(orderData) }); const result = await response.json(); if (result.success) { alert('Encomenda realizada com sucesso!'); state.cart = []; updateCartCount(); hideCartModal(); } else { throw new Error(result.error); } } catch (error) { alert(`Erro ao finalizar a encomenda: ${error.message}`); } finally { finalizeBtn.disabled = false; finalizeBtn.textContent = 'Finalizar Encomenda'; } };
    
    const showOrdersModal = () => { ordersModal.classList.remove('hidden'); fetchAndRenderOrders(); };
    const hideOrdersModal = () => ordersModal.classList.add('hidden');
    const fetchAndRenderOrders = async () => {
        ordersContent.innerHTML = `<div class="text-center p-8"><div class="loader inline-block"></div><p class="mt-4">Buscando seu histórico...</p></div>`;
        try {
            const response = await fetch(`${ENCOMENDA_API_URL}?action=get_my_orders`);
            if (!response.ok) throw new Error('Não foi possível buscar seu histórico.');
            const orders = await response.json();
            let html = `<div class="flex justify-between items-center mb-6"><h2 class="text-2xl font-bold">Meu Histórico de Encomendas</h2><button id="close-orders-btn" class="text-2xl text-gray-500 hover:text-gray-800">&times;</button></div>`;
            if (orders.length === 0) {
                html += `<p class="text-center text-gray-600 py-12">Você ainda não fez nenhuma encomenda.</p>`;
            } else {
                html += `<div class="space-y-6 max-h-[70vh] overflow-y-auto pr-4">`;
                orders.forEach(order => {
                    const orderDate = new Date(order.data_encomenda).toLocaleString('pt-BR');
                    const total = order.itens.reduce((sum, item) => sum + (item.preco_unitario * item.quantidade), 0);
                    const statusMap = { 'nova': { text: 'Nova', class: 'bg-blue-100 text-blue-800' }, 'em_separacao': { text: 'Em Separação', class: 'bg-yellow-100 text-yellow-800' }, 'pronta_retirada': { text: 'Pronta para Retirada', class: 'bg-green-100 text-green-800' }, 'concluida': { text: 'Concluída', class: 'bg-gray-100 text-gray-800' }, 'cancelada': { text: 'Cancelada', class: 'bg-red-100 text-red-800' } };
                    const statusInfo = statusMap[order.status] || { text: order.status, class: 'bg-gray-200' };
                    html += `<div class="bg-gray-50 rounded-lg p-4 border"><div class="flex justify-between items-center border-b pb-3 mb-3"><div><p class="font-bold text-lg">Pedido #${order.id.toString().padStart(4, '0')}</p><p class="text-sm text-gray-500">${orderDate}</p></div><div class="text-right"><p class="font-bold text-lg">${total.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}</p><span class="px-3 py-1 text-sm font-medium rounded-full ${statusInfo.class}">${statusInfo.text}</span></div></div><div class="space-y-2">`;
                    order.itens.forEach(item => { html += `<div class="flex items-center justify-between text-sm"><div class="flex items-center gap-3"><img src="${item.imagem_url || 'https://placehold.co/100x100/EFEFEF/333?text=Img'}" class="w-10 h-10 rounded object-cover"><span>${item.quantidade}x ${item.nome}</span></div><span>${(item.preco_unitario * item.quantidade).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}</span></div>`; });
                    html += `</div></div>`;
                });
                html += `</div>`;
            }
            ordersContent.innerHTML = html;
            document.getElementById('close-orders-btn').addEventListener('click', hideOrdersModal);
            ordersModal.addEventListener('click', (e) => { if (e.target === ordersModal) hideOrdersModal(); });
        } catch (error) {
            console.error("Erro ao buscar histórico:", error);
            ordersContent.innerHTML = `<div class="flex justify-between items-center mb-6"><h2 class="text-2xl font-bold">Meu Histórico de Encomendas</h2><button id="close-orders-btn" class="text-2xl text-gray-500 hover:text-gray-800">&times;</button></div><p class="text-center text-red-500 py-12">Ocorreu um erro ao buscar seu histórico. Tente novamente mais tarde.</p>`;
            document.getElementById('close-orders-btn').addEventListener('click', hideOrdersModal);
        }
    };

    const fetchSiteConfig = async () => { try { const response = await fetch(`${API_URL}/config.php`); if (!response.ok) throw new Error('Erro ao buscar configurações.'); const config = await response.json(); if (config && config.status_calculado) { statusTexto.textContent = config.status_calculado.texto; statusMercadoDiv.className = `flex items-center gap-2 text-sm font-medium px-3 py-1 rounded-full ${config.status_calculado.cor === 'green' ? 'aberto' : 'fechado'}`; } } catch (error) { console.error("Falha na API de Config:", error); statusTexto.textContent = "Erro de conexão"; statusMercadoDiv.classList.add('fechado'); } };
    const fetchProducts = async () => { productGrid.innerHTML = `<div id="loading-spinner" class="col-span-full flex justify-center items-center py-16"><div class="loader"></div></div>`; const params = new URLSearchParams({ sort: state.sort }); if (state.search) params.append('search', state.search); if (state.promocao) params.append('promocao', 'true'); if (state.sessao_id !== 'all') params.append('sessao_id', state.sessao_id); if (state.tag_id !== 'all') params.append('tag_id', state.tag_id); try { const response = await fetch(`${API_URL}/produtos.php?${params.toString()}`); if (!response.ok) throw new Error('A resposta da rede não foi OK'); const products = await response.json(); renderProducts(products); } catch (error) { console.error("Falha ao buscar produtos:", error); productGrid.innerHTML = `<p class="col-span-full text-center text-red-600">Não foi possível carregar os produtos.</p>`; } };
    const renderProducts = (products) => { productGrid.innerHTML = ''; if (products.length === 0) { productGrid.innerHTML = `<p class="col-span-full text-center text-gray-600">Nenhum produto encontrado.</p>`; return; } products.forEach(product => { const card = document.createElement('div'); card.className = 'product-card flex flex-col'; const precoFormatado = parseFloat(product.preco).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }); card.innerHTML = `<div class="relative"><img src="${product.imagem_url || 'https://placehold.co/400x400/EFEFEF/333?text=Produto'}" alt="${product.nome}" class="w-full h-48 object-cover" onerror="this.onerror=null;this.src='https://placehold.co/400x400/EFEFEF/333?text=Erro';">${product.em_promocao == 1 ? '<span class="absolute top-2 right-2 bg-red-600 text-white text-xs font-bold px-2 py-1 rounded-full">PROMO</span>' : ''}</div><div class="p-4 flex flex-col flex-grow"><h3 class="font-semibold text-gray-800 truncate" title="${product.nome}">${product.nome}</h3><p class="text-sm text-gray-500 mb-2">${product.sessao_nome || 'Sem Categoria'}</p><div class="mt-auto"><p class="text-xl font-bold text-gray-900">${precoFormatado}</p><button class="add-to-cart-btn w-full mt-3 bg-blue-600 text-white font-semibold py-2 rounded-lg hover:bg-blue-700 transition">Adicionar</button></div></div>`; card.querySelector('.add-to-cart-btn').addEventListener('click', () => handleAddToCart(product)); productGrid.appendChild(card); }); };
    
    const fetchAndRenderTags = async () => { try { const response = await fetch(`${API_URL}/produtos.php?action=fetch_tags`); if (!response.ok) { const errorText = await response.text(); throw new Error(`Falha ao buscar tags: ${response.status} ${errorText}`); } const tags = await response.json(); tagFiltersContainer.innerHTML = ''; const allBtn = document.createElement('button'); allBtn.className = 'tag-btn active'; allBtn.textContent = 'Todas'; allBtn.dataset.tagId = 'all'; tagFiltersContainer.appendChild(allBtn); if (tags.length > 0) { tags.forEach(tag => { const tagBtn = document.createElement('button'); tagBtn.className = 'tag-btn'; tagBtn.textContent = tag.nome; tagBtn.dataset.tagId = tag.id; tagFiltersContainer.appendChild(tagBtn); }); } else { tagFiltersContainer.innerHTML += '<span class="text-xs text-gray-400">Nenhuma tag encontrada.</span>'; } } catch (error) { console.error("Erro ao buscar tags:", error); tagFiltersContainer.innerHTML = '<span class="text-red-500">Erro ao carregar tags.</span>'; } };

    let debounceTimer;
    searchInput.addEventListener('keyup', () => { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => { state.search = searchInput.value; fetchProducts(); }, 300); });
    sortSelect.addEventListener('change', () => { state.sort = sortSelect.value; fetchProducts(); });
    promocaoFilter.addEventListener('change', () => { state.promocao = promocaoFilter.checked; fetchProducts(); });
    
    sessoesContainer.addEventListener('click', (e) => {
        if (e.target.classList.contains('filter-btn')) {
            sessoesContainer.querySelector('.active').classList.remove('active');
            e.target.classList.add('active');
            state.sessao_id = e.target.dataset.sessaoId;
            state.tag_id = 'all';
            const currentActiveTag = tagFiltersContainer.querySelector('.tag-btn.active');
            if (currentActiveTag) {
                currentActiveTag.classList.remove('active');
            }
            const allTagsButton = tagFiltersContainer.querySelector('.tag-btn[data-tag-id="all"]');
            if (allTagsButton) {
                allTagsButton.classList.add('active');
            }
            fetchProducts();
        }
    });

    tagFiltersContainer.addEventListener('click', (e) => { if (e.target.classList.contains('tag-btn')) { tagFiltersContainer.querySelector('.active').classList.remove('active'); e.target.classList.add('active'); state.tag_id = e.target.dataset.tagId; fetchProducts(); } });
    loginBtn.addEventListener('click', () => showAuthModal());
    logoutBtn.addEventListener('click', handleLogout);
    myAccountBtn.addEventListener('click', showOrdersModal);
    encomendarBtn.addEventListener('click', showCartModal);

    checkSession();
    fetchSiteConfig();
    fetchProducts();
    fetchAndRenderTags();
});
