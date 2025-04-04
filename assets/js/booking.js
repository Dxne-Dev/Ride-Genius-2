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
    }

    async handleCreateBooking(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        try {
            const response = await fetch('api/booking_api.php', {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'create',
                    ...Object.fromEntries(formData)
                })
            });

            const result = await response.json();
            
            if (result.success) {
                showNotification('Réservation créée avec succès', 'success');
                form.reset();
                this.loadTransactions();
            } else {
                showNotification(result.message, 'error');
            }
        } catch (error) {
            showNotification('Erreur lors de la création de la réservation', 'error');
            console.error('Erreur:', error);
        }
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