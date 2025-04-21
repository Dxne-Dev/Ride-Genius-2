document.addEventListener('DOMContentLoaded', function() {
    // Effet de scrolling pour la navbar
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
                navbar.classList.remove('bg-primary');
                navbar.classList.add('bg-white');
                navbar.classList.add('navbar-light');
                navbar.classList.remove('navbar-dark');
            } else {
                navbar.classList.remove('scrolled');
                navbar.classList.add('bg-primary');
                navbar.classList.remove('bg-white');
                navbar.classList.remove('navbar-light');
                navbar.classList.add('navbar-dark');
            }
        });
    }
    
    // Auto-fermeture des alertes après 5 secondes
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Activer tous les tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Confirmation des actions critiques
    const confirmBtns = document.querySelectorAll('[data-confirm]');
    confirmBtns.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm(this.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });
    
    // Formulaire de recherche - mise à jour de la date minimale
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(function(input) {
        const today = new Date().toISOString().split('T')[0];
        input.setAttribute('min', today);
        if (!input.value) {
            input.value = today;
        }
    });
    
    // Mise à jour du prix total dans le formulaire de réservation
    const seatsInput = document.getElementById('seats');
    if (seatsInput) {
        const totalPriceElement = document.getElementById('totalPrice');
        const pricePerSeat = parseFloat(totalPriceElement.textContent);
        
        seatsInput.addEventListener('input', function() {
            const seats = parseInt(this.value) || 0;
            const total = (seats * pricePerSeat).toFixed(2);
            totalPriceElement.textContent = `${total} FCFA`;
        });
    }
});