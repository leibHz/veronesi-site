document.addEventListener('DOMContentLoaded', () => {
    const API_URL = '../api/api_admin_encomendas.php';
    const tableBody = document.getElementById('ordersTableBody');
    const modal = document.getElementById('orderModal');
    const closeBtns = document.querySelectorAll('.close-btn');
    const orderIdSpan = document.getElementById('orderId');
    const orderDetailsContainer = document.getElementById('orderDetails');
    const statusSelect = document.getElementById('statusSelect');
    const saveStatusBtn = document.getElementById('saveStatusBtn');
    let currentOrderId = null;

    const fetchOrders = async () => {
        try {
            const response = await fetch(API_URL);
            const orders = await response.json();
            renderOrders(orders);
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="6">Erro ao carregar encomendas.</td></tr>`;
        }
    };

    const renderOrders = (orders) => {
        tableBody.innerHTML = '';
        if (!orders || orders.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6">Nenhuma encomenda encontrada.</td></tr>';
            return;
        }
        orders.forEach(order => {
            const row = `
                <tr>
                    <td>#${order.id_encomenda}</td>
                    <td>${order.clientes?.nome_completo || 'Cliente Removido'}</td>
                    <td>${new Date(order.data_encomenda).toLocaleDateString('pt-BR')}</td>
                    <td>R$ ${parseFloat(order.valor_total).toFixed(2)}</td>
                    <td><span class="status status-${order.status}">${order.status.replace('_', ' ')}</span></td>
                    <td class="actions-cell">
                        <!-- ÍCONE DO BOTÃO DE VISUALIZAR TROCADO -->
                        <button class="view-btn" data-id="${order.id_encomenda}"><i class="fa-solid fa-pencil"></i></button>
                    </td>
                </tr>
            `;
            tableBody.insertAdjacentHTML('beforeend', row);
        });
    };

    const openModal = async (orderId) => {
        currentOrderId = orderId;
        try {
            const response = await fetch(`${API_URL}?id=${orderId}`);
            const order = await response.json();
            
            if (!order) {
                alert('Encomenda não encontrada.');
                return;
            }

            orderIdSpan.textContent = order.id_encomenda;
            statusSelect.value = order.status;
            
            let detailsHtml = `
                <p><strong>Cliente:</strong> ${order.clientes?.nome_completo || 'N/A'}</p>
                <p><strong>Data:</strong> ${new Date(order.data_encomenda).toLocaleString('pt-BR')}</p>
                <h4>Itens da Encomenda:</h4>
                <ul>
            `;
            order.encomenda_itens.forEach(item => {
                detailsHtml += `<li>${item.quantidade}x ${item.produtos?.nome || 'Produto Removido'} - R$ ${parseFloat(item.preco_unitario).toFixed(2)}</li>`;
            });
            detailsHtml += '</ul>';
            
            orderDetailsContainer.innerHTML = detailsHtml;
            modal.style.display = 'block';

        } catch (error) {
            alert('Erro ao buscar detalhes da encomenda.');
        }
    };

    const closeModal = () => {
        modal.style.display = 'none';
        currentOrderId = null;
    };

    const saveStatus = async () => {
        if (!currentOrderId) return;
        
        const newStatus = statusSelect.value;
        try {
            const response = await fetch(API_URL, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_encomenda: currentOrderId, status: newStatus })
            });
            const result = await response.json();
            if (result.status === 'success') {
                closeModal();
                fetchOrders(); 
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            alert('Erro ao atualizar status: ' + error.message);
        }
    };

    tableBody.addEventListener('click', (e) => {
        const viewBtn = e.target.closest('.view-btn');
        if (viewBtn) {
            const id = viewBtn.dataset.id;
            openModal(id);
        }
    });

    closeBtns.forEach(btn => btn.addEventListener('click', closeModal));
    saveStatusBtn.addEventListener('click', saveStatus);
    window.addEventListener('click', e => e.target == modal && closeModal());

    fetchOrders();
});
