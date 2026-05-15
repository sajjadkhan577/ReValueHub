let currentEditingItemId = null;



document.addEventListener('DOMContentLoaded', () => {
    console.log('Admin Dashboard Initializing...');
    applyTheme();
    initAdminUI();
    fetchStats();
    fetchPendingItems();
    fetchRecentUsers();
});

function initAdminUI() {
    const activeLink = document.querySelector('.nav-link[onclick*="dashboard"]');
    if (activeLink) {
        activeLink.classList.add('text-blue-600', 'bg-blue-50', 'font-semibold');
        activeLink.classList.remove('text-slate-600');
    }
}

function showAdminSection(sectionId, element) {
    document.querySelectorAll('.admin-view').forEach(view => view.classList.add('hidden'));
    const target = document.getElementById(sectionId + '-view');
    if (target) {
        target.classList.remove('hidden');
        document.getElementById('header-title').textContent = sectionId.charAt(0).toUpperCase() + sectionId.slice(1) + ' Control';
    }
    
    if (element && element.classList.contains('nav-link')) {
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('text-blue-600', 'bg-blue-50', 'font-semibold');
            link.classList.add('text-slate-600');
        });
        element.classList.add('text-blue-600', 'bg-blue-50', 'font-semibold');
        element.classList.remove('text-slate-600');
    }

    if (sectionId === 'items') fetchAdminInventory();
    if (sectionId === 'requests') fetchAdminRequests();
}

function toggleDarkMode() {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('admin-theme', isDark ? 'dark' : 'light');
    updateThemeIcon(isDark);
}

function applyTheme() {
    const savedTheme = localStorage.getItem('admin-theme') || 'light';
    const isDark = savedTheme === 'dark';
    if (isDark) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
    updateThemeIcon(isDark);
}

function updateThemeIcon(isDark) {
    const icon = document.getElementById('theme-icon');
    if (icon) icon.textContent = isDark ? 'light_mode' : 'dark_mode';
}

function toggleNotifications() {
    alert('Notifications: \n1. New user registration\n2. 3 items pending approval\n3. System update completed');
    document.getElementById('notif-badge')?.classList.add('hidden');
}

async function fetchStats() {
    try {
        const response = await fetch(`api/stats.php?t=${Date.now()}`);
        const data = await response.json();
        
        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val;
        };

        setVal('admin-total-users', data.users || '0');
        setVal('admin-pending-items', data.items_pending || '0');
        setVal('admin-total-requests', data.requests || '0');
        setVal('admin-impact', data.impact || '0kg');
    } catch (error) {
        console.error('Error fetching stats:', error);
    }
}

async function fetchPendingItems() {
    try {
        const response = await fetch(`api/pending_items.php?t=${Date.now()}`);
        let dbItems = [];
        try { dbItems = await response.json(); } catch(e) {}
        
        const tbody = document.getElementById('pending-items-tbody');
        if (!tbody) return;

        const allItems = dbItems;

        if (allItems.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-slate-500">No pending items.</td></tr>';
            return;
        }

        tbody.innerHTML = allItems.map(item => `
            <tr class="hover:bg-slate-50/50 transition-colors cursor-pointer" onclick="openItemModal('${item.id}')">
                <td class="py-4">
                    <div class="flex items-center gap-3">
                        <img class="w-10 h-10 rounded-lg object-cover" src="${item.image_url || 'assets/placeholder.png'}">
                        <span class="font-bold text-sm">${item.title}</span>
                    </div>
                </td>
                <td class="py-4 text-sm">${item.donor_name}</td>
                <td class="py-4"><span class="px-2 py-1 bg-blue-50 text-blue-600 rounded text-xs font-bold">${item.category}</span></td>
                <td class="py-4">
                    <div class="flex gap-2">
                        <button onclick="event.stopPropagation(); updateItemStatus('${item.id}', 'approved')" class="text-secondary hover:scale-110 transition-transform"><span class="material-symbols-outlined">check_circle</span></button>
                        <button onclick="event.stopPropagation(); updateItemStatus('${item.id}', 'rejected')" class="text-error hover:scale-110 transition-transform"><span class="material-symbols-outlined">cancel</span></button>
                    </div>
                </td>
            </tr>
        `).join('');
    } catch (error) {
        console.error('Error fetching pending items:', error);
    }
}

async function fetchRecentUsers() {
    try {
        const response = await fetch(`api/recent_users.php?t=${Date.now()}`);
        const users = await response.json();
        const tbody = document.getElementById('admin-users-tbody');
        if (!tbody) return;

        tbody.innerHTML = users.map(user => `
            <tr>
                <td class="px-6 py-4 font-bold text-sm">${user.name}</td>
                <td class="px-6 py-4 text-sm">${user.email}</td>
                <td class="px-6 py-4"><span class="px-2 py-1 bg-green-50 text-green-600 rounded text-xs font-bold">${user.status}</span></td>
                <td class="px-6 py-4 text-sm text-slate-500">${new Date(user.joined_at).toLocaleDateString()}</td>
            </tr>
        `).join('');
    } catch (error) {
        console.error('Error fetching recent users:', error);
    }
}

async function updateItemStatus(targetId, status) {
    alert(`Attempting to ${status} item ${targetId}`);
    console.log(`Updating item ${targetId} to status ${status}`);
    try {
        const response = await fetch('api/update_item_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: targetId, status: status })
        });
        const result = await response.json();
        if (response.ok) {
            alert(result.message || `Item ${status}!`);
            closeItemModal();
            fetchPendingItems();
            fetchAdminInventory();
            fetchStats();
        } else {
            alert('Error: ' + (result.message || 'Unknown error occurred'));
        }
    } catch (error) {
        console.error('Error updating status:', error);
        alert('Network error while updating status.');
    }
}

async function fetchAdminInventory() {
    const grid = document.getElementById('admin-items-grid');
    if (!grid) return;

    try {
        const res = await fetch(`api/items.php?status=all&t=${Date.now()}`);
        let dbItems = [];
        try { dbItems = await res.json(); } catch(e) {}
        
        const allItems = dbItems;

        grid.innerHTML = allItems.map(item => `
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm cursor-pointer hover:shadow-md transition-all" onclick="openItemModal('${item.id}')">
                <div class="relative h-32">
                    <img class="w-full h-full object-cover" src="${item.image_url || 'assets/placeholder.png'}">
                    <div class="absolute top-2 right-2 px-2 py-0.5 rounded text-[10px] font-bold uppercase ${item.status === 'approved' ? 'bg-green-500 text-white' : 'bg-blue-500 text-white'}">
                        ${item.status}
                    </div>
                </div>
                <div class="p-3">
                    <h5 class="font-bold text-sm truncate">${item.title}</h5>
                    <p class="text-xs text-slate-500">${item.category}</p>
                </div>
            </div>
        `).join('');
    } catch (err) {
        grid.innerHTML = '<p class="col-span-full text-center text-error">Failed to load items.</p>';
    }
}

async function openItemModal(itemId) {
    currentEditingItemId = itemId;
    let item = null;

    try {
        const res = await fetch(`api/items.php?id=${itemId}&t=${Date.now()}`);
        if (res.ok) {
            item = await res.json();
        } else {
            alert('Item not found or already deleted.');
            fetchAdminInventory();
            return;
        }
    } catch (err) {
        console.error('Error fetching item details:', err);
        alert('Error loading item details.');
        return;
    }

    try {
        if (item) {
            document.getElementById('modal-item-image').src = item.image_url || 'assets/placeholder.png';
            document.getElementById('modal-item-title').textContent = item.title;
            document.getElementById('modal-item-category').textContent = item.category;
            document.getElementById('modal-item-desc').value = item.description || '';
            document.getElementById('modal-item-location').value = item.location || '';
            document.getElementById('modal-item-donor').textContent = item.donor_name || 'System User';
            
            const statusBadge = document.getElementById('modal-item-status-badge');
            if (statusBadge) {
                statusBadge.textContent = item.status;
                statusBadge.className = `px-3 py-1 rounded-full text-xs font-bold uppercase ${item.status === 'approved' ? 'bg-green-50 text-green-600' : 'bg-blue-50 text-blue-600'}`;
            }
            
            const approveBtn = document.getElementById('modal-approve-btn');
            if (approveBtn) {
                approveBtn.className = item.status === 'approved' ? 'hidden' : 'flex-1 bg-secondary text-white py-3 rounded-xl font-bold hover:opacity-90 transition-all shadow-md shadow-secondary/20';
                approveBtn.onclick = () => {
                    console.log('Approve button clicked');
                    updateItemStatus(itemId, 'approved');
                };
            }

            const deleteBtn = document.getElementById('modal-delete-btn');
            if (deleteBtn) {
                deleteBtn.onclick = () => deleteAdminItem(itemId);
            }

            const saveBtn = document.getElementById('modal-save-btn');
            if (saveBtn) {
                saveBtn.onclick = () => saveAdminItemEdit(itemId);
            }

            document.getElementById('item-modal').classList.remove('hidden');
        }
    } catch (err) {
        console.error('Error rendering modal:', err);
        alert('Internal UI Error: ' + err.message);
    }
}

function closeItemModal() {
    document.getElementById('item-modal').classList.add('hidden');
    currentEditingItemId = null;
}

async function saveAdminItemEdit(itemId) {
    alert('Saving changes for item ' + itemId);
    const desc = document.getElementById('modal-item-desc').value;
    const location = document.getElementById('modal-item-location').value;



    try {
        const res = await fetch('api/update_item_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: itemId, description: desc, location: location, action: 'edit' })
        });
        const result = await res.json();
        if (res.ok) {
            alert(result.message || 'Changes saved!');
            fetchAdminInventory();
            fetchPendingItems();
            closeItemModal();
        } else {
            alert('Error: ' + (result.message || 'Failed to save changes'));
        }
    } catch (err) {
        console.error('Error saving edit:', err);
        alert('Network error while saving changes.');
    }
}

async function deleteAdminItem(itemId) {
    alert('Attempting to delete item ' + itemId);
    try {
        const res = await fetch('api/update_item_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: itemId, action: 'delete' })
        });
        const result = await res.json();
        if (res.ok) {
            alert(result.message || 'Item deleted!');
            fetchAdminInventory();
            fetchPendingItems();
            fetchStats();
            closeItemModal();
        } else {
            alert('Error: ' + (result.message || 'Failed to delete item'));
        }
    } catch (err) {
        console.error('Error deleting item:', err);
        alert('Network error while deleting item.');
    }
}

async function fetchAdminRequests() {
    const tbody = document.getElementById('user-requests-tbody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center">Loading requests...</td></tr>';
    try {
        const response = await fetch(`api/requests.php?t=${Date.now()}`, {
            headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
        });
        const requests = await response.json();
        
        if (requests.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-slate-500">No requests found.</td></tr>';
            return;
        }

        tbody.innerHTML = requests.map(req => `
            <tr>
                <td class="px-6 py-4">
                    <p class="text-sm font-bold">${req.requester_name || 'User #' + req.requester_id}</p>
                </td>
                <td class="px-6 py-4">
                    <p class="text-sm">${req.item_title}</p>
                </td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 bg-blue-50 text-blue-600 rounded-full text-xs font-bold uppercase">${req.status}</span>
                </td>
                <td class="px-6 py-4 text-right">
                    <button class="text-primary hover:underline text-xs font-bold">Manage</button>
                </td>
            </tr>
        `).join('');
    } catch (error) {
        console.error('Error fetching requests:', error);
        tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-error">Failed to load requests.</td></tr>';
    }
}

function logout() {
    localStorage.removeItem('token');
    window.location.href = 'landing.html';
}

window.showAdminSection = showAdminSection;
window.toggleDarkMode = toggleDarkMode;
window.toggleNotifications = toggleNotifications;
window.logout = logout;
window.updateItemStatus = updateItemStatus;
window.openItemModal = openItemModal;
window.closeItemModal = closeItemModal;
