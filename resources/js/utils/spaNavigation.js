/**
 * SPA Navigation Handler
 * Handles navigation within the Sales SPA without page refresh
 */

export function initSpaNavigation() {
    // Listen for clicks on SPA links
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a[data-spa-link="true"]');
        
        if (link) {
            e.preventDefault();
            
            const route = link.getAttribute('data-spa-route');
            const url = link.getAttribute('href');
            
            // Update browser URL without refresh
            window.history.pushState({}, '', url);
            
            // Dispatch custom event for React Router
            const navigationEvent = new CustomEvent('spa-navigate', {
                detail: {
                    route: route,
                    url: url
                }
            });
            
            window.dispatchEvent(navigationEvent);
            
            // Update active states in sidebar
            updateActiveStates(route);
        }
    });
    
    // Handle browser back/forward buttons
    window.addEventListener('popstate', function(e) {
        const route = window.location.pathname;
        
        const navigationEvent = new CustomEvent('spa-navigate', {
            detail: {
                route: route,
                url: route
            }
        });
        
        window.dispatchEvent(navigationEvent);
        updateActiveStates(route);
    });
}

/**
 * Update active states in sidebar
 */
function updateActiveStates(currentRoute) {
    // Remove all active classes
    document.querySelectorAll('a[data-spa-link="true"]').forEach(link => {
        link.classList.remove('active');
    });
    
    // Add active class to current route
    const activeLink = document.querySelector(`a[data-spa-route="${currentRoute}"]`);
    if (activeLink) {
        activeLink.classList.add('active');
    }
}

/**
 * Navigate programmatically
 */
export function navigateTo(route) {
    const url = route;
    
    // Update browser URL without refresh
    window.history.pushState({}, '', url);
    
    // Dispatch custom event
    const navigationEvent = new CustomEvent('spa-navigate', {
        detail: {
            route: route,
            url: url
        }
    });
    
    window.dispatchEvent(navigationEvent);
    updateActiveStates(route);
}


