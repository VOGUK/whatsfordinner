document.addEventListener('DOMContentLoaded', () => {

    // --- Master List Functionality ---
    const masterList = document.getElementById('master-list');
    const shoppingList = document.getElementById('shopping-list');

    masterList.addEventListener('click', (e) => {
        const li = e.target.closest('li');

        // Master List: Delete
        if (e.target.classList.contains('delete-btn')) {
            li.remove();
        }

        // Master List: Edit
        if (e.target.classList.contains('edit-btn')) {
            const span = li.querySelector('.item-name');
            const newName = prompt("Edit item name:", span.textContent);
            if (newName) span.textContent = newName;
        }

        // Master List: Add to Shopping List (Mobile Only)
        if (e.target.classList.contains('add-shop-btn')) {
            const itemName = li.querySelector('.item-name').textContent;
            addShoppingListItem(itemName);
        }
    });

    // --- Shopping List Functionality ---
    shoppingList.addEventListener('click', (e) => {
        const li = e.target.closest('li');

        // Shopping List: Delete (Mobile Only)
        if (e.target.classList.contains('delete-btn')) {
            li.remove();
        }

        // Shopping List: Move Up (Mobile Only)
        if (e.target.classList.contains('move-up-btn')) {
            const prev = li.previousElementSibling;
            if (prev) {
                shoppingList.insertBefore(li, prev);
            }
        }

        // Shopping List: Move Down (Mobile Only)
        if (e.target.classList.contains('move-down-btn')) {
            const next = li.nextElementSibling;
            if (next) {
                shoppingList.insertBefore(next, li);
            }
        }
    });

    // Helper function to create a new Shopping List item
    function addShoppingListItem(name) {
        const li = document.createElement('li');
        li.innerHTML = `
            <span class="item-name">${name}</span>
            <div class="action-buttons">
                <button class="mobile-only move-up-btn">↑</button>
                <button class="mobile-only move-down-btn">↓</button>
                <button class="mobile-only delete-btn">Delete</button>
            </div>
        `;
        shoppingList.appendChild(li);
    }
});