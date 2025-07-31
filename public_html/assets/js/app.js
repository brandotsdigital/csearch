// Custom JavaScript for Product Discount Monitor

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-submit filter form on change
    const filterForm = document.querySelector('.filter-section form');
    if (filterForm) {
        const filterInputs = filterForm.querySelectorAll('select, input');
        filterInputs.forEach(input => {
            if (input.type !== 'submit') {
                input.addEventListener('change', function() {
                    // Add loading state
                    const submitBtn = filterForm.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Loading...';
                        submitBtn.disabled = true;
                    }
                    
                    // Submit form after short delay
                    setTimeout(() => {
                        filterForm.submit();
                    }, 300);
                });
            }
        });
    }

    // Lazy loading for images
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }

    // Product card animations
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('fade-in');
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Track outbound clicks
    document.querySelectorAll('a[target="_blank"]').forEach(link => {
        link.addEventListener('click', function() {
            // Track the click (you can integrate with Google Analytics here)
            console.log('Outbound click:', this.href);
        });
    });

    // Real-time clock for last updated
    function updateClock() {
        const clockElements = document.querySelectorAll('.live-clock');
        const now = new Date();
        const timeString = now.toLocaleString();
        
        clockElements.forEach(element => {
            element.textContent = timeString;
        });
    }

    // Update clock every minute
    setInterval(updateClock, 60000);

    // Price comparison highlight
    function highlightBestDeals() {
        const productCards = document.querySelectorAll('.product-card');
        let highestDiscount = 0;
        let bestDealCard = null;

        productCards.forEach(card => {
            const discountBadge = card.querySelector('.discount-badge');
            if (discountBadge) {
                const discount = parseInt(discountBadge.textContent);
                if (discount > highestDiscount) {
                    highestDiscount = discount;
                    bestDealCard = card;
                }
            }
        });

        if (bestDealCard && highestDiscount >= 50) {
            bestDealCard.classList.add('best-deal');
            const badge = document.createElement('div');
            badge.className = 'best-deal-badge';
            badge.innerHTML = '<i class="fas fa-crown"></i> Best Deal!';
            bestDealCard.querySelector('.product-image-container').appendChild(badge);
        }
    }

    highlightBestDeals();

    // Search functionality (if search input exists)
    const searchInput = document.querySelector('#search-input');
    if (searchInput) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(this.value);
            }, 500);
        });
    }

    function performSearch(query) {
        if (query.length < 3) return;
        
        const productCards = document.querySelectorAll('.product-card');
        productCards.forEach(card => {
            const title = card.querySelector('.product-title').textContent.toLowerCase();
            const brand = card.querySelector('.product-meta')?.textContent.toLowerCase() || '';
            const category = card.querySelector('.category-badge')?.textContent.toLowerCase() || '';
            
            const searchText = (title + ' ' + brand + ' ' + category).toLowerCase();
            
            if (searchText.includes(query.toLowerCase())) {
                card.style.display = 'block';
                card.classList.add('search-match');
            } else {
                card.style.display = 'none';
                card.classList.remove('search-match');
            }
        });
        
        // Update results count
        const visibleCards = document.querySelectorAll('.product-card[style*="block"]').length;
        updateResultsCount(visibleCards);
    }

    function updateResultsCount(count) {
        let resultsInfo = document.querySelector('.results-info');
        if (!resultsInfo) {
            resultsInfo = document.createElement('div');
            resultsInfo.className = 'results-info alert alert-info';
            document.querySelector('.container .row').before(resultsInfo);
        }
        resultsInfo.textContent = `Showing ${count} matching deals`;
    }

    // Add to favorites functionality
    window.addToFavorites = function(productId) {
        let favorites = JSON.parse(localStorage.getItem('favorites') || '[]');
        
        if (!favorites.includes(productId)) {
            favorites.push(productId);
            localStorage.setItem('favorites', JSON.stringify(favorites));
            
            // Update UI
            const button = document.querySelector(`[onclick="addToFavorites(${productId})"]`);
            if (button) {
                button.innerHTML = '<i class="fas fa-heart text-danger"></i>';
                button.onclick = () => removeFromFavorites(productId);
            }
            
            showNotification('Added to favorites!', 'success');
        }
    };

    window.removeFromFavorites = function(productId) {
        let favorites = JSON.parse(localStorage.getItem('favorites') || '[]');
        favorites = favorites.filter(id => id !== productId);
        localStorage.setItem('favorites', JSON.stringify(favorites));
        
        // Update UI
        const button = document.querySelector(`[onclick="removeFromFavorites(${productId})"]`);
        if (button) {
            button.innerHTML = '<i class="far fa-heart"></i>';
            button.onclick = () => addToFavorites(productId);
        }
        
        showNotification('Removed from favorites', 'info');
    };

    // Load favorite states
    function loadFavoriteStates() {
        const favorites = JSON.parse(localStorage.getItem('favorites') || '[]');
        favorites.forEach(productId => {
            const button = document.querySelector(`[data-product-id="${productId}"]`);
            if (button) {
                button.innerHTML = '<i class="fas fa-heart text-danger"></i>';
                button.onclick = () => removeFromFavorites(productId);
            }
        });
    }

    loadFavoriteStates();

    // Notification system
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} notification-toast`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 250px;
            animation: slideInRight 0.3s ease;
        `;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }

    // Price alert functionality
    window.setPriceAlert = function(productId, currentPrice) {
        const targetPrice = prompt(`Set price alert for this product:\nCurrent price: $${currentPrice}\n\nNotify me when price drops below:`);
        
        if (targetPrice && !isNaN(targetPrice) && parseFloat(targetPrice) > 0) {
            let alerts = JSON.parse(localStorage.getItem('priceAlerts') || '{}');
            alerts[productId] = {
                targetPrice: parseFloat(targetPrice),
                currentPrice: currentPrice,
                setAt: new Date().toISOString()
            };
            localStorage.setItem('priceAlerts', JSON.stringify(alerts));
            
            showNotification(`Price alert set for $${targetPrice}`, 'success');
        }
    };

    // Check price alerts
    function checkPriceAlerts() {
        const alerts = JSON.parse(localStorage.getItem('priceAlerts') || '{}');
        const productCards = document.querySelectorAll('.product-card');
        
        productCards.forEach(card => {
            const productId = card.dataset.productId;
            if (alerts[productId]) {
                const currentPriceElement = card.querySelector('.current-price');
                if (currentPriceElement) {
                    const currentPrice = parseFloat(currentPriceElement.textContent.replace('$', ''));
                    const alert = alerts[productId];
                    
                    if (currentPrice <= alert.targetPrice && currentPrice < alert.currentPrice) {
                        showNotification(`Price alert! ${card.querySelector('.product-title').textContent} is now $${currentPrice}`, 'warning');
                        
                        // Update the stored current price
                        alert.currentPrice = currentPrice;
                        localStorage.setItem('priceAlerts', JSON.stringify(alerts));
                    }
                }
            }
        });
    }

    // Check price alerts on page load
    checkPriceAlerts();

    // Auto-refresh functionality
    let autoRefreshInterval;
    
    window.toggleAutoRefresh = function() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
            showNotification('Auto-refresh disabled', 'info');
        } else {
            autoRefreshInterval = setInterval(() => {
                window.location.reload();
            }, 300000); // 5 minutes
            showNotification('Auto-refresh enabled (5 min)', 'success');
        }
    };

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl + R for refresh
        if (e.ctrlKey && e.key === 'r') {
            e.preventDefault();
            window.location.reload();
        }
        
        // Ctrl + F for search (if search input exists)
        if (e.ctrlKey && e.key === 'f' && searchInput) {
            e.preventDefault();
            searchInput.focus();
        }
        
        // Escape to clear search
        if (e.key === 'Escape' && searchInput) {
            searchInput.value = '';
            performSearch('');
        }
    });

    // Performance monitoring
    if ('performance' in window) {
        window.addEventListener('load', function() {
            setTimeout(() => {
                const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
                console.log(`Page loaded in ${loadTime}ms`);
                
                // You can send this data to analytics
                if (loadTime > 5000) {
                    console.warn('Page load time is slow');
                }
            }, 0);
        });
    }
});

// CSS for animations (add to style.css)
const additionalCSS = `
@keyframes slideInRight {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes slideOutRight {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
}

.best-deal {
    border: 2px solid #ffd700 !important;
    box-shadow: 0 0 20px rgba(255, 215, 0, 0.3) !important;
}

.best-deal-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: #ffd700;
    color: #333;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.search-match {
    animation: highlight 0.5s ease;
}

@keyframes highlight {
    0% { background-color: #fff3cd; }
    100% { background-color: transparent; }
}

.notification-toast {
    animation: slideInRight 0.3s ease;
}

.lazy {
    opacity: 0;
    transition: opacity 0.3s;
}

.lazy.loaded {
    opacity: 1;
}
`;

// Inject additional CSS
const style = document.createElement('style');
style.textContent = additionalCSS;
document.head.appendChild(style);
