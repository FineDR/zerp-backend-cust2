document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('GlobalSearch');
    const partsForm = document.getElementById('SelectParts');

    // Add to Cart AJAX
    if (partsForm) {
        partsForm.addEventListener('click', function(e) {
            const addBtn = e.target.closest('button[name="SelectingOrderItems"]');
            if (addBtn) {
                e.preventDefault();
                const card = addBtn.closest('.db-product-card');
                if (!card) return;

                const stockID = card.querySelector('input[name^="StockID"]').value;
                const qty = card.querySelector('input[name^="OrderQty"]').value;
                const identifierInput = document.querySelector('input[name="identifier"]');
                const identifier = identifierInput ? identifierInput.value : new URLSearchParams(window.location.search).get('identifier');

                if (parseFloat(qty) <= 0) {
                    alert('Please enter a quantity greater than zero.');
                    return;
                }

                addBtn.disabled = true;
                addBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                fetch(`${window.location.pathname}?Ajax=AddToCart&StockID=${encodeURIComponent(stockID)}&Qty=${qty}&identifier=${identifier}`)
                    .then(response => response.text())
                    .then(data => {
                        if (data.trim().includes('SUCCESS')) {
                            // Professional feedback: Just reload for now to ensure all PHP logic (discounts, etc.) is applied
                            // In a true headless SPA we would return JSON and update the DOM
                            window.location.reload();
                        } else {
                            alert('Error adding item to cart.');
                            addBtn.disabled = false;
                            addBtn.innerHTML = '<i class="fas fa-plus"></i> Add';
                        }
                    })
                    .catch(err => {
                        console.error('AJAX Error:', err);
                        addBtn.disabled = false;
                        addBtn.innerHTML = '<i class="fas fa-plus"></i> Add';
                    });
            }
        });
    }

    // Live Search Debounce
    let debounceTimer;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                // For now, we submit the form to maintain standard PHP search logic
                // but we could make this AJAX too in a future phase.
                partsForm.submit();
            }, 800);
        });
    }
});
