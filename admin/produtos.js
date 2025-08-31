// ARQUIVO: admin/produtos.js (ATUALIZADO)
// Adicionado suporte para promoções e tags.
document.addEventListener('DOMContentLoaded', () => {
    const API_URL = '../api/api_admin_produtos.php';
    const tableBody = document.getElementById('productsTableBody');
    const modal = document.getElementById('productModal');
    const modalTitle = document.getElementById('modalTitle');
    const productForm = document.getElementById('productForm');
    const addProductBtn = document.getElementById('addProductBtn');
    const closeBtns = document.querySelectorAll('.close-btn');
    const sessaoSelect = document.getElementById('id_sessao');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');
    const imagePreview = document.getElementById('imagePreview');

    const fetchProducts = async () => {
        try {
            const response = await fetch(API_URL);
            if (!response.ok) throw new Error('Falha ao carregar produtos.');
            const products = await response.json();
            renderProducts(products);
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="6">${error.message}</td></tr>`;
        }
    };

    const fetchSessoes = async () => {
        const response = await fetch(`${API_URL}?sessoes=true`);
        const sessoes = await response.json();
        sessaoSelect.innerHTML = '<option value="">Selecione uma sessão</option>';
        sessoes.forEach(sessao => {
            const option = document.createElement('option');
            option.value = sessao.id_sessao;
            option.textContent = sessao.nome;
            sessaoSelect.appendChild(option);
        });
    };

    const renderProducts = (products) => {
        tableBody.innerHTML = '';
        if (!products || products.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6">Nenhum produto cadastrado.</td></tr>';
            return;
        }
        products.forEach(p => {
            const tagsHtml = p.tags.map(tag => `<span class="tag-admin">${tag.nome}</span>`).join(' ');
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><span class="status ${p.disponivel ? 'status-disponivel' : 'status-indisponivel'}">
                    ${p.disponivel ? 'Sim' : 'Não'}
                </span></td>
                <td>${p.nome} ${p.em_promocao ? '<span class="promo-tag-list">Promo</span>' : ''}</td>
                <td>R$ ${parseFloat(p.preco).toFixed(2)}</td>
                <td>${tagsHtml || 'N/A'}</td>
                <td>${p.sessao?.nome || 'N/A'}</td>
                <td class="actions-cell"></td>
            `;

            const actionsCell = row.querySelector('.actions-cell');
            const editBtn = document.createElement('button');
            editBtn.className = 'edit-btn';
            editBtn.dataset.id = p.id_produto;
            editBtn.innerHTML = '<i class="fa-solid fa-pencil"></i>';
            
            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'delete-btn';
            deleteBtn.dataset.id = p.id_produto;
            deleteBtn.innerHTML = '<i class="fa-solid fa-trash-can"></i>';

            actionsCell.appendChild(editBtn);
            actionsCell.appendChild(deleteBtn);
            tableBody.appendChild(row);
        });
    };

    const openModal = (product = null) => {
        productForm.reset();
        imagePreviewContainer.style.display = 'none';
        document.getElementById('disponivel').checked = true;
        document.getElementById('em_promocao').checked = false;

        if (product) {
            modalTitle.textContent = 'Editar Produto';
            document.getElementById('productId').value = product.id_produto;
            document.getElementById('nome').value = product.nome;
            document.getElementById('preco').value = product.preco;
            document.getElementById('id_sessao').value = product.id_sessao;
            document.getElementById('descricao').value = product.descricao;
            document.getElementById('unidade_medida').value = product.unidade_medida;
            document.getElementById('disponivel').checked = product.disponivel;
            document.getElementById('em_promocao').checked = product.em_promocao;
            document.getElementById('tags').value = product.tags.map(t => t.nome).join(', ');

            if (product.imagem_url) {
                document.getElementById('imagem_atual').value = product.imagem_url;
                imagePreview.src = `../${product.imagem_url}`;
                imagePreviewContainer.style.display = 'block';
            }
        } else {
            modalTitle.textContent = 'Adicionar Novo Produto';
        }
        modal.style.display = 'block';
    };

    const closeModal = () => modal.style.display = 'none';

    addProductBtn.addEventListener('click', () => openModal());
    closeBtns.forEach(btn => btn.addEventListener('click', closeModal));
    window.addEventListener('click', e => e.target == modal && closeModal());

    productForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(productForm);
        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.status === 'success') {
            closeModal();
            fetchProducts();
        } else {
            alert(result.message);
        }
    });

    tableBody.addEventListener('click', async (e) => {
        const targetButton = e.target.closest('button');
        if (!targetButton) return;
        const id = targetButton.dataset.id;

        if (targetButton.classList.contains('edit-btn')) {
            const response = await fetch(`${API_URL}?id=${id}`);
            const product = await response.json();
            openModal(product || null);
        }

        if (targetButton.classList.contains('delete-btn')) {
            if (confirm('Tem certeza que deseja remover este produto?')) {
                const response = await fetch(API_URL, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_produto: id })
                });
                const result = await response.json();
                if (result.status === 'success') fetchProducts();
                else alert(result.message);
            }
        }
    });

    fetchProducts();
    fetchSessoes();
});
