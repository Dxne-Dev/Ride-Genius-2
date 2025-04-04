class BookingManager {
    constructor() {
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        // Gestionnaire pour la création de réservation
        document.querySelectorAll('.create-booking-form').forEach(form => {
            form.addEventListener('submit', (e) => this.handleCreateBooking(e));
        });

        // Gestionnaire pour l'annulation de réservation
        document.querySelectorAll('.cancel-booking-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleCancelBooking(e));
        });

        // Gestionnaire pour la complétion de réservation
        document.querySelectorAll('.complete-booking-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleCompleteBooking(e));
        });

        // Chargement des transactions
        this.loadTransactions();

        // Mise à jour dynamique des prix
        this.updateBookingPrices();
    }

    updateBookingPrices() {
        const ridePrice = parseFloat(document.querySelector('[name="ride_price"]').value);
        const driverSubscription = document.querySelector('[name="driver_subscription"]').value;
        
        let totalPrice = ridePrice;
        let commission = 0;
        let commissionRate = 0;

        switch(driverSubscription) {
            case 'free':
                commissionRate = 10;
                commission = ridePrice * 0.10;
                break;
            case 'pro':
                commissionRate = 2;
                commission = ridePrice * 0.02;
                totalPrice = ridePrice + commission;
                break;
            case 'business':
                commissionRate = 0;
                commission = 0;
                break;
        }

        // Mise à jour de l'affichage
        document.querySelector('.base-price').textContent = ridePrice.toFixed(2) + '€';
        document.querySelector('.commission-amount').textContent = commission.toFixed(2) + '€';
        document.querySelector('.commission-rate').textContent = commissionRate + '%';
        document.querySelector('.total-price').textContent = totalPrice.toFixed(2) + '€';

        // Mise à jour du montant dans le formulaire
        document.querySelector('[name="amount"]').value = totalPrice;
    }

    handleCreateBooking(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        // Vérification du solde
        const passengerBalance = parseFloat(document.querySelector('.passenger-balance').textContent);
        const totalPrice = parseFloat(document.querySelector('.total-price').textContent);

        if (passengerBalance < totalPrice) {
            this.showNotification('Solde insuffisant. Veuillez recharger votre wallet.', 'error');
            return;
        }

        // Envoi de la requête
        fetch('api/booking_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showNotification('Réservation créée avec succès', 'success');
                setTimeout(() => {
                    window.location.href = 'index.php?page=my-bookings';
                }, 1500);
            } else {
                this.showNotification(data.message || 'Erreur lors de la création de la réservation', 'error');
            }
        })
        .catch(error => {
            this.showNotification('Erreur lors de la communication avec le serveur', 'error');
        });
    }

    showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 5000);
    }

    async handleCancelBooking(e) {
        const bookingId = e.target.dataset.bookingId;
        
        try {
            const response = await fetch('api/booking_api.php', {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'cancel',
                    booking_id: bookingId
                })
            });

            const result = await response.json();
            
            if (result.success) {
                showNotification('Réservation annulée avec succès', 'success');
                this.loadTransactions();
            } else {
                showNotification(result.message, 'error');
            }
        } catch (error) {
            showNotification('Erreur lors de l\'annulation de la réservation', 'error');
            console.error('Erreur:', error);
        }
    }

    async handleCompleteBooking(e) {
        const bookingId = e.target.dataset.bookingId;
        
        try {
            const response = await fetch('api/booking_api.php', {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'complete',
                    booking_id: bookingId
                })
            });

            const result = await response.json();
            
            if (result.success) {
                showNotification('Réservation complétée avec succès', 'success');
                this.loadTransactions();
            } else {
                showNotification(result.message, 'error');
            }
        } catch (error) {
            showNotification('Erreur lors de la complétion de la réservation', 'error');
            console.error('Erreur:', error);
        }
    }

    async loadTransactions() {
        const type = document.body.dataset.userType || 'passenger';
        
        try {
            const response = await fetch('api/booking_api.php', {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'get_transactions',
                    type: type
                })
            });

            const result = await response.json();
            
            if (result.success) {
                this.displayTransactions(result.transactions);
            } else {
                showNotification(result.message, 'error');
            }
        } catch (error) {
            showNotification('Erreur lors du chargement des transactions', 'error');
            console.error('Erreur:', error);
        }
    }

    displayTransactions(transactions) {
        const container = document.querySelector('.transactions-container');
        if (!container) return;

        container.innerHTML = transactions.map(transaction => `
            <div class="transaction-card">
                <div class="transaction-header">
                    <span class="transaction-id">#${transaction.booking_id}</span>
                    <span class="transaction-status ${transaction.status}">${transaction.status}</span>
                </div>
                <div class="transaction-details">
                    <p>Montant: ${formatAmount(transaction.amount)}</p>
                    <p>Commission: ${formatAmount(transaction.commission_amount)}</p>
                    <p>Date: ${new Date(transaction.created_at).toLocaleString()}</p>
                </div>
                <div class="transaction-actions">
                    ${transaction.status === 'pending' ? `
                        <button class="cancel-booking-btn" data-booking-id="${transaction.booking_id}">
                            Annuler
                        </button>
                    ` : ''}
                    ${transaction.status === 'pending' ? `
                        <button class="complete-booking-btn" data-booking-id="${transaction.booking_id}">
                            Terminer
                        </button>
                    ` : ''}
                </div>
            </div>
        `).join('');

        // Réinitialiser les écouteurs d'événements
        this.initializeEventListeners();
    }
}

// Fonction utilitaire pour formater les montants
function formatAmount(amount) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount);
}

// Initialiser le gestionnaire de réservations
document.addEventListener('DOMContentLoaded', () => {
    new BookingManager();
}); 