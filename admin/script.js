// admin/script.js
document.addEventListener('DOMContentLoaded', () => {
    const mainContent = document.getElementById('main-content');
    const navLinks = document.querySelectorAll('.nav-link');

    const API_HANDLER_URL = '../api/admin_handler.php';

    // --- ROTEAMENTO ---
    const routes = {
        '#dashboard': renderDashboard,
        '#produtos': renderProdutos,
        '#encomendas': renderEncomendas,
        '#funcionarios': renderFuncionarios,
        '#configuracoes': renderConfiguracoes
    };

    function router() {
        const hash = window.location.hash || '#dashboard';
        const routeHandler = routes[hash];
        
        if (routeHandler) {
            navLinks.forEach(link => link.classList.toggle('active', link.getAttribute('href') === hash));
            routeHandler();
        } else {
            mainContent.innerHTML = '<h1>Página não encontrada</h1>';
        }
    }

    // --- FUNÇÕES DE RENDERIZAÇÃO DE PÁGINA ---

    function renderDashboard() {
        mainContent.innerHTML = `
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Dashboard</h1>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-lg shadow"><h2 class="text-lg font-semibold text-gray-600">Total de Produtos</h2><p id="total-produtos" class="text-3xl font-bold text-blue-600">...</p></div>
                <div class="bg-white p-6 rounded-lg shadow"><h2 class="text-lg font-semibold text-gray-600">Encomendas Novas</h2><p id="encomendas-novas" class="text-3xl font-bold text-red-600">...</p></div>
                <div class="bg-white p-6 rounded-lg shadow"><h2 class="text-lg font-semibold text-gray-600">Clientes Cadastrados</h2><p id="total-clientes" class="text-3xl font-bold text-green-600">...</p></div>
            </div>`;
        
        fetch(`${API_HANDLER_URL}?action=get_dashboard_stats`)
            .then(res => {
                if (!res.ok) throw new Error('Falha na resposta da API de estatísticas.');
                return res.json();
            })
            .then(data => {
                document.getElementById('total-produtos').textContent = data.total_produtos || 0;
                document.getElementById('encomendas-novas').textContent = data.encomendas_novas || 0;
                document.getElementById('total-clientes').textContent = data.total_clientes || 0;
            })
            .catch(err => {
                console.error("Falha ao carregar estatísticas do dashboard:", err);
                document.getElementById('total-produtos').textContent = 'Erro';
                document.getElementById('encomendas-novas').textContent = 'Erro';
                document.getElementById('total-clientes').textContent = 'Erro';
            });
    }

    async function renderProdutos() {
        mainContent.innerHTML = `
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Gerenciamento de Produtos</h1>
                <button id="add-product-btn" class="bg-blue-600 text-white font-semibold px-4 py-2 rounded-lg hover:bg-blue-700">Adicionar Produto</button>
            </div>
            <div class="bg-white p-6 rounded-lg shadow overflow-x-auto">
                <table class="table-auto">
                    <thead><tr><th>Imagem</th><th>ID</th><th>Nome</th><th>Código de Barras</th><th>Tags</th><th>Ações</th></tr></thead>
                    <tbody id="products-table-body"></tbody>
                </table>
            </div>`;
        await loadProductsTable();
        document.getElementById('add-product-btn').addEventListener('click', () => showProductModal());
    }
    
    async function renderEncomendas() {
        mainContent.innerHTML = `
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Gerenciamento de Encomendas</h1>
            </div>
            <div class="bg-white p-6 rounded-lg shadow overflow-x-auto">
                <table class="table-auto">
                    <thead><tr><th>ID</th><th>Cliente</th><th>Data</th><th>Itens</th><th>Valor Total</th><th>Status</th></tr></thead>
                    <tbody id="encomendas-table-body"></tbody>
                </table>
            </div>`;

        await loadEncomendasTable();
    }

    async function renderFuncionarios() {
        mainContent.innerHTML = `
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Gerenciamento de Funcionários</h1>
                <button id="add-funcionario-btn" class="bg-blue-600 text-white font-semibold px-4 py-2 rounded-lg hover:bg-blue-700">Adicionar Funcionário</button>
            </div>
            <div class="bg-white p-6 rounded-lg shadow overflow-x-auto">
                <table class="table-auto">
                    <thead><tr><th>ID</th><th>Nome</th><th>Email</th><th>Cargo</th><th>Status</th><th>Ações</th></tr></thead>
                    <tbody id="funcionarios-table-body"></tbody>
                </table>
            </div>`;

        await loadFuncionariosTable();
        document.getElementById('add-funcionario-btn').addEventListener('click', () => showFuncionarioModal());
    }

    async function renderConfiguracoes() {
        mainContent.innerHTML = `
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Configurações Gerais do Site</h1>
            <div id="config-form-container" class="bg-white p-8 rounded-lg shadow max-w-4xl">
                <p>Carregando configurações...</p>
            </div>`;

        try {
            const response = await fetch(`${API_HANDLER_URL}?action=get_site_config`);
            if (!response.ok) throw new Error('Falha ao buscar configurações.');
            const config = await response.json();

            const formContainer = document.getElementById('config-form-container');
            formContainer.innerHTML = `
                <form id="site-config-form">
                    <div class="space-y-8">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-700 border-b pb-2 mb-4">Horário de Funcionamento</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div><label for="horario_abertura" class="block text-sm font-medium text-gray-700">Horário de Abertura</label><input type="time" id="horario_abertura" name="horario_abertura" value="${config.horario_abertura || ''}" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm"></div>
                                <div><label for="horario_fechamento" class="block text-sm font-medium text-gray-700">Horário de Fechamento</label><input type="time" id="horario_fechamento" name="horario_fechamento" value="${config.horario_fechamento || ''}" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm"></div>
                            </div>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-gray-700 border-b pb-2 mb-4">Status da Loja</h2>
                            <p class="text-sm text-gray-500 mb-3">Use para forçar o status de "Aberto" ou "Fechado" fora do horário normal.</p>
                            <div class="flex items-center space-x-6">
                                <label class="flex items-center"><input type="radio" name="status_manual" value="automatico" class="h-4 w-4 text-blue-600 border-gray-300" ${config.status_manual === 'automatico' ? 'checked' : ''}> <span class="ml-2">Automático</span></label>
                                <label class="flex items-center"><input type="radio" name="status_manual" value="aberto_manual" class="h-4 w-4 text-blue-600 border-gray-300" ${config.status_manual === 'aberto_manual' ? 'checked' : ''}> <span class="ml-2">Forçar Aberto</span></label>
                                <label class="flex items-center"><input type="radio" name="status_manual" value="fechado_manual" class="h-4 w-4 text-blue-600 border-gray-300" ${config.status_manual === 'fechado_manual' ? 'checked' : ''}> <span class="ml-2">Forçar Fechado</span></label>
                            </div>
                            <div class="mt-4"><label for="mensagem_status" class="block text-sm font-medium text-gray-700">Mensagem customizada (opcional)</label><input type="text" id="mensagem_status" name="mensagem_status" value="${config.mensagem_status || ''}" placeholder="Ex: Fechado para balanço" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm"></div>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-gray-700 border-b pb-2 mb-4">Serviço de Encomendas</h2>
                             <label class="flex items-center"><input type="checkbox" id="encomendas_ativas" name="encomendas_ativas" class="h-5 w-5 text-blue-600 border-gray-300 rounded" ${config.encomendas_ativas == 1 ? 'checked' : ''}><span class="ml-3 text-lg font-medium text-gray-800">Serviço de encomendas ATIVO</span></label>
                            <div class="mt-4"><label for="justificativa_encomendas" class="block text-sm font-medium text-gray-700">Justificativa para serviço inativo (opcional)</label><input type="text" id="justificativa_encomendas" name="justificativa_encomendas" value="${config.justificativa_encomendas || ''}" placeholder="Ex: Em breve, Serviço em manutenção" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm"></div>
                        </div>
                    </div>
                    <div class="mt-8 pt-5 border-t border-gray-200 flex justify-end"><button type="submit" class="bg-blue-600 text-white font-semibold px-6 py-2 rounded-lg hover:bg-blue-700 transition shadow-sm">Salvar Alterações</button></div>
                </form>
            `;
            document.getElementById('site-config-form').addEventListener('submit', handleConfigSubmit);
        } catch (error) {
            document.getElementById('config-form-container').innerHTML = `<p class="text-red-500">Erro ao carregar as configurações.</p>`;
            console.error(error);
        }
    }

    async function handleConfigSubmit(event) {
        event.preventDefault();
        const form = event.target;
        const data = Object.fromEntries(new FormData(form).entries());
        data.encomendas_ativas = document.getElementById('encomendas_ativas').checked ? 1 : 0;
        try {
            const response = await fetch(`${API_HANDLER_URL}?action=update_site_config`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (!result.success) throw new Error(result.error);
            alert('Configurações salvas com sucesso!');
        } catch (error) {
            alert(`Erro ao salvar configurações: ${error.message}`);
        }
    }
    
    // --- LÓGICA DE PRODUTOS (CRUD) ---
    async function loadProductsTable() {
        const tableBody = document.getElementById('products-table-body');
        tableBody.innerHTML = `<tr><td colspan="6" class="text-center p-8">Carregando...</td></tr>`;
        try {
            const response = await fetch(`${API_HANDLER_URL}?action=get_products`);
            const products = await response.json();
            tableBody.innerHTML = '';
            products.forEach(p => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><img src="../${p.imagem_url || 'https://placehold.co/100x100/EFEFEF/333?text=S/Img'}" class="w-16 h-16 rounded-md object-cover"></td>
                    <td>${p.id}</td>
                    <td>${p.nome}</td>
                    <td>${p.codigo_barras || 'N/A'}</td>
                    <td class="max-w-xs truncate" title="${p.tags || ''}">${p.tags || 'Nenhuma'}</td>
                    <td class="flex gap-4">
                        <button data-id="${p.id}" class="edit-product-btn text-blue-600 hover:underline">Editar</button>
                        <button data-id="${p.id}" data-name="${p.nome}" class="delete-product-btn text-red-600 hover:underline">Excluir</button>
                    </td>`;
                tableBody.appendChild(row);
            });
            document.querySelectorAll('.edit-product-btn').forEach(btn => btn.addEventListener('click', handleProductEdit));
            document.querySelectorAll('.delete-product-btn').forEach(btn => btn.addEventListener('click', handleProductDelete));
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center p-8 text-red-500">Falha ao carregar.</td></tr>`;
        }
    }
    
    async function showProductModal(prod = null) {
        const isEditing = prod !== null;
        const modalHTML = `
            <div id="product-modal" class="modal-overlay">
                <div class="modal-content max-h-[90vh] overflow-y-auto">
                    <h2 class="text-2xl font-bold mb-6">${isEditing ? 'Editar Produto' : 'Adicionar Produto'}</h2>
                    <form id="product-form" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="${isEditing ? prod.id : ''}">
                        <input type="hidden" name="existing_image_url" value="${isEditing ? prod.imagem_url || '' : ''}">
                        
                        <div class="mb-4"><label class="block font-medium">Nome do Produto</label><input type="text" name="nome" class="w-full mt-1 p-2 border rounded" value="${isEditing ? prod.nome : ''}" required></div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div><label class="block font-medium">Preço</label><input type="number" step="0.01" name="preco" class="w-full mt-1 p-2 border rounded" value="${isEditing ? prod.preco : ''}" required></div>
                            <div><label class="block font-medium">Estoque</label><input type="number" name="estoque" class="w-full mt-1 p-2 border rounded" value="${isEditing ? prod.estoque || '0' : '0'}" required></div>
                        </div>

                        <div class="mb-4"><label class="block font-medium">Categoria (Sessão)</label><select name="sessao_id" class="w-full mt-1 p-2 border rounded bg-white"></select></div>

                        <div class="mb-4"><label class="block font-medium">Código de Barras</label><input type="text" name="codigo_barras" class="w-full mt-1 p-2 border rounded" value="${isEditing ? prod.codigo_barras || '' : ''}"></div>
                        <div class="mb-4"><label class="block font-medium">Tags (separadas por vírgula)</label><input type="text" name="tags" class="w-full mt-1 p-2 border rounded" value="${isEditing ? prod.tags || '' : ''}"></div>
                        <div class="mb-4"><label class="block font-medium">Descrição</label><textarea name="descricao" class="w-full mt-1 p-2 border rounded">${isEditing ? prod.descricao || '' : ''}</textarea></div>
                        <div class="mb-4"><label class="block font-medium">Imagem (JPG, PNG)</label><input type="file" name="imagem" class="w-full mt-1 p-2 border rounded" accept=".jpg,.jpeg,.png"></div>

                        <div class="flex justify-end gap-4 mt-8">
                            <button type="button" class="cancel-btn bg-gray-200 px-4 py-2 rounded-lg">Cancelar</button>
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>`;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        setTimeout(() => document.getElementById('product-modal').classList.add('visible'), 10);
        
        const closeModal = () => {
             const modal = document.getElementById('product-modal');
             if(modal) {
                modal.classList.remove('visible');
                setTimeout(() => modal.remove(), 200);
             }
        };
        document.querySelector('#product-modal .cancel-btn').addEventListener('click', closeModal);
        document.getElementById('product-form').addEventListener('submit', (e) => handleProductSubmit(e, closeModal));

        // Lógica para carregar as sessões no select
        const sessoesSelect = document.querySelector('select[name="sessao_id"]');
        sessoesSelect.innerHTML = '<option>Carregando...</option>';
        try {
            const sessoesResponse = await fetch(`${API_HANDLER_URL}?action=get_sessoes`);
            const sessoes = await sessoesResponse.json();
            sessoesSelect.innerHTML = '<option value="">Selecione uma categoria</option>';
            sessoes.forEach(s => { sessoesSelect.innerHTML += `<option value="${s.id}">${s.nome}</option>`; });
            if (isEditing) sessoesSelect.value = prod.sessao_id;
        } catch (error) {
            sessoesSelect.innerHTML = '<option value="">Erro ao carregar</option>';
        }
    }

    async function handleProductSubmit(event, closeModalCallback) {
        event.preventDefault();
        const formData = new FormData(event.target);
        const isEditing = formData.get('id') !== '';
        const url = `${API_HANDLER_URL}?action=${isEditing ? 'update_product' : 'add_product'}`;
        try {
            const response = await fetch(url, { method: 'POST', body: formData });
            const result = await response.json();
            if (!result.success) throw new Error(result.error || 'Erro desconhecido no servidor.');
            closeModalCallback();
            await loadProductsTable();
        } catch (error) {
            alert(`Erro ao salvar produto: ${error.message}`);
        }
    }

    async function handleProductEdit(event) {
        const prodId = event.target.dataset.id;
        const response = await fetch(`${API_HANDLER_URL}?action=get_products`);
        const products = await response.json();
        const prodToEdit = products.find(p => p.id == prodId);
        if (prodToEdit) showProductModal(prodToEdit);
    }
    
    async function handleProductDelete(event) {
        const productId = event.target.dataset.id;
        const productName = event.target.dataset.name;
        if (confirm(`Tem certeza que deseja excluir o produto "${productName}"?`)) {
            try {
                const response = await fetch(`${API_HANDLER_URL}?action=delete_product`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: productId }) });
                const result = await response.json();
                if (!result.success) throw new Error(result.error);
                await loadProductsTable();
            } catch (error) {
                alert("Não foi possível excluir o produto.");
            }
        }
    }
    
    // --- LÓGICA DE ENCOMENDAS ---
    async function loadEncomendasTable() {
        const tableBody = document.getElementById('encomendas-table-body');
        tableBody.innerHTML = `<tr><td colspan="6" class="text-center p-8">Carregando encomendas...</td></tr>`;
        try {
            const response = await fetch(`${API_HANDLER_URL}?action=get_encomendas`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const encomendas = await response.json();
            tableBody.innerHTML = '';
            if (encomendas.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="6" class="text-center p-8">Nenhuma encomenda encontrada.</td></tr>`;
                return;
            }
            const statusOptions = { 'nova': 'Nova', 'em_separacao': 'Em Separação', 'pronta_retirada': 'Pronta para Retirada', 'concluida': 'Concluída', 'cancelada': 'Cancelada' };
            encomendas.forEach(e => {
                const row = document.createElement('tr');
                const formattedDate = new Date(e.data_encomenda).toLocaleString('pt-BR');
                const formattedValue = parseFloat(e.valor_total).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                let optionsHTML = Object.entries(statusOptions).map(([key, value]) => `<option value="${key}" ${e.status === key ? 'selected' : ''}>${value}</option>`).join('');
                row.innerHTML = `
                    <td>#${e.id.toString().padStart(4, '0')}</td>
                    <td>${e.cliente_nome}</td>
                    <td>${formattedDate}</td>
                    <td>${e.total_itens}</td>
                    <td>${formattedValue}</td>
                    <td><select data-encomenda-id="${e.id}" class="status-select status-${e.status}">${optionsHTML}</select></td>`;
                tableBody.appendChild(row);
            });
            document.querySelectorAll('.status-select').forEach(select => select.addEventListener('change', handleStatusChange));
        } catch (error) {
            console.error("Erro ao carregar encomendas:", error);
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center p-8 text-red-500">Falha ao carregar as encomendas.</td></tr>`;
        }
    }

    async function handleStatusChange(event) {
        const select = event.target;
        select.className = `status-select status-${select.value}`;
        try {
            const response = await fetch(`${API_HANDLER_URL}?action=update_encomenda_status`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ encomenda_id: select.dataset.encomendaId, status: select.value })
            });
            const result = await response.json();
            if (!result.success) throw new Error(result.message);
        } catch (error) {
            alert('Não foi possível atualizar o status da encomenda.');
            loadEncomendasTable(); 
        }
    }

    // --- LÓGICA DE FUNCIONÁRIOS (CRUD) ---
    async function loadFuncionariosTable() {
        const tableBody = document.getElementById('funcionarios-table-body');
        tableBody.innerHTML = `<tr><td colspan="6" class="text-center p-8">Carregando...</td></tr>`;
        try {
            const response = await fetch(`${API_HANDLER_URL}?action=get_funcionarios`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const funcionarios = await response.json();
            tableBody.innerHTML = '';
            funcionarios.forEach(f => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${f.id}</td>
                    <td>${f.nome_completo}</td>
                    <td>${f.email}</td>
                    <td>${f.cargo}</td>
                    <td><span class="px-2 py-1 font-semibold leading-tight rounded-full ${f.ativo == 1 ? 'text-green-700 bg-green-100' : 'text-red-700 bg-red-100'}">${f.ativo == 1 ? 'Ativo' : 'Inativo'}</span></td>
                    <td class="flex gap-4">
                        <button data-id="${f.id}" class="edit-funcionario-btn text-blue-600 hover:underline">Editar</button>
                        <button data-id="${f.id}" data-name="${f.nome_completo}" class="delete-funcionario-btn text-red-600 hover:underline">Excluir</button>
                    </td>`;
                tableBody.appendChild(row);
            });
            document.querySelectorAll('.edit-funcionario-btn').forEach(btn => btn.addEventListener('click', handleFuncionarioEdit));
            document.querySelectorAll('.delete-funcionario-btn').forEach(btn => btn.addEventListener('click', handleFuncionarioDelete));
        } catch (error) {
            console.error("Erro ao carregar funcionários:", error);
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center p-8 text-red-500">Falha ao carregar funcionários.</td></tr>`;
        }
    }

    async function showFuncionarioModal(func = null) {
        const isEditing = func !== null;
        const modalHTML = `
            <div id="funcionario-modal" class="modal-overlay">
                <div class="modal-content">
                    <h2 class="text-2xl font-bold mb-6">${isEditing ? 'Editar Funcionário' : 'Adicionar Funcionário'}</h2>
                    <form id="funcionario-form">
                        <input type="hidden" name="id" value="${isEditing ? func.id : ''}">
                        <div class="mb-4"><label class="block font-medium">Nome Completo</label><input type="text" name="nome_completo" class="w-full mt-1 p-2 border rounded" value="${isEditing ? func.nome_completo : ''}" required></div>
                        <div class="mb-4"><label class="block font-medium">Email</label><input type="email" name="email" class="w-full mt-1 p-2 border rounded" value="${isEditing ? func.email : ''}" required></div>
                        <div class="mb-4"><label class="block font-medium">Cargo</label><input type="text" name="cargo" class="w-full mt-1 p-2 border rounded" value="${isEditing ? func.cargo : ''}"></div>
                        <div class="mb-4"><label class="block font-medium">Senha</label><input type="password" name="senha" class="w-full mt-1 p-2 border rounded" ${isEditing ? 'placeholder="Deixe em branco para não alterar"' : 'required'}></div>
                        <div class="mb-4"><label class="block font-medium">Status</label><select name="ativo" class="w-full mt-1 p-2 border rounded bg-white"><option value="1">Ativo</option><option value="0">Inativo</option></select></div>
                        <div class="flex justify-end gap-4 mt-8">
                            <button type="button" class="cancel-btn bg-gray-200 px-4 py-2 rounded-lg font-semibold hover:bg-gray-300">Cancelar</button>
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>`;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        if (isEditing) document.querySelector('select[name="ativo"]').value = func.ativo;

        setTimeout(() => document.getElementById('funcionario-modal').classList.add('visible'), 10);
        const closeModal = () => {
            const modal = document.getElementById('funcionario-modal');
            modal.classList.remove('visible');
            setTimeout(() => modal.remove(), 200);
        };
        document.querySelector('#funcionario-modal .cancel-btn').addEventListener('click', closeModal);
        document.getElementById('funcionario-form').addEventListener('submit', (e) => handleFuncionarioSubmit(e, closeModal));
    }
    
    async function handleFuncionarioSubmit(event, closeModalCallback) {
        event.preventDefault();
        const form = event.target;
        const data = Object.fromEntries(new FormData(form).entries());
        const isEditing = data.id !== '';
        const url = `${API_HANDLER_URL}?action=${isEditing ? 'update_funcionario' : 'add_funcionario'}`;
        try {
            const response = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
            const result = await response.json();
            if (!result.success) throw new Error(result.error);
            closeModalCallback();
            await loadFuncionariosTable();
        } catch (error) {
            alert(`Erro: ${error.message}`);
        }
    }

    async function handleFuncionarioEdit(event) {
        const funcId = event.target.dataset.id;
        const response = await fetch(`${API_HANDLER_URL}?action=get_funcionarios`);
        const funcionarios = await response.json();
        const funcToEdit = funcionarios.find(f => f.id == funcId);
        if (funcToEdit) showFuncionarioModal(funcToEdit);
    }

    async function handleFuncionarioDelete(event) {
        const funcId = event.target.dataset.id;
        const funcName = event.target.dataset.name;
        if (confirm(`Tem certeza que deseja excluir "${funcName}"?`)) {
            try {
                const response = await fetch(`${API_HANDLER_URL}?action=delete_funcionario`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: funcId })
                });
                const result = await response.json();
                if (!result.success) throw new Error(result.error);
                await loadFuncionariosTable();
            } catch (error) {
                alert(`Erro: ${error.message}`);
            }
        }
    }
    
    // --- INICIALIZAÇÃO ---
    window.addEventListener('hashchange', router);
    router();
});