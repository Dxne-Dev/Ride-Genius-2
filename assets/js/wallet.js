$(document).ready(function() {
    // Fonction pour formater les montants
    function formatAmount(amount) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR'
        }).format(amount);
    }

    // Fonction pour mettre à jour le solde affiché
    function updateBalance() {
        $.ajax({
            url: 'api/wallet_api.php',
            method: 'POST',
            data: { action: 'getBalance' },
            success: function(response) {
                if (response.success) {
                    $('#currentBalance').text(formatAmount(response.balance));
                }
            }
        });
    }

    // Fonction pour charger les transactions
    function loadTransactions() {
        $.ajax({
            url: 'api/wallet_api.php',
            method: 'POST',
            data: { action: 'getTransactions' },
            success: function(response) {
                if (response.success) {
                    const tbody = $('#transactionsTable tbody');
                    tbody.empty();
                    
                    response.transactions.forEach(function(transaction) {
                        const row = $('<tr>');
                        row.append($('<td>').text(new Date(transaction.created_at).toLocaleString('fr-FR')));
                        row.append($('<td>').text(transaction.description || '-'));
                        row.append($('<td>').text(formatAmount(transaction.amount)));
                        row.append($('<td>').text(transaction.type === 'credit' ? 'Crédit' : 'Débit'));
                        row.append($('<td>').text(formatAmount(transaction.balance_after)));
                        tbody.append(row);
                    });
                }
            }
        });
    }

    // Gestionnaire pour l'ajout de fonds
    $('#addFundsForm').on('submit', function(e) {
        e.preventDefault();
        
        const amount = parseFloat($('#addAmount').val());
        const paymentMethod = $('#paymentMethod').val();
        const description = $('#addDescription').val();
        
        if (isNaN(amount) || amount <= 0) {
            showNotification('Le montant doit être supérieur à 0', 'error');
            return;
        }
        
        $.ajax({
            url: 'api/wallet_api.php',
            method: 'POST',
            data: {
                action: 'addFunds',
                amount: amount,
                paymentMethod: paymentMethod,
                description: description
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Fonds ajoutés avec succès', 'success');
                    $('#addFundsModal').modal('hide');
                    updateBalance();
                    loadTransactions();
                } else {
                    showNotification(response.message || 'Erreur lors de l\'ajout des fonds', 'error');
                }
            },
            error: function() {
                showNotification('Erreur lors de la communication avec le serveur', 'error');
            }
        });
    });

    // Gestionnaire pour le retrait de fonds
    $('#withdrawFundsForm').on('submit', function(e) {
        e.preventDefault();
        
        const amount = parseFloat($('#withdrawAmount').val());
        const withdrawMethod = $('#withdrawMethod').val();
        const description = $('#withdrawDescription').val();
        
        if (isNaN(amount) || amount <= 0) {
            showNotification('Le montant doit être supérieur à 0', 'error');
            return;
        }
        
        $.ajax({
            url: 'api/wallet_api.php',
            method: 'POST',
            data: {
                action: 'withdrawFunds',
                amount: amount,
                withdrawMethod: withdrawMethod,
                description: description
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Retrait effectué avec succès', 'success');
                    $('#withdrawFundsModal').modal('hide');
                    updateBalance();
                    loadTransactions();
                } else {
                    showNotification(response.message || 'Erreur lors du retrait des fonds', 'error');
                }
            },
            error: function() {
                showNotification('Erreur lors de la communication avec le serveur', 'error');
            }
        });
    });

    // Fonction pour afficher les notifications
    function showNotification(message, type = 'info') {
        const toast = $('<div>')
            .addClass('toast')
            .attr('role', 'alert')
            .attr('aria-live', 'assertive')
            .attr('aria-atomic', 'true');
            
        const toastHeader = $('<div>')
            .addClass('toast-header')
            .append($('<strong>').addClass('me-auto').text('Notification'))
            .append($('<button>')
                .addClass('btn-close')
                .attr('type', 'button')
                .attr('data-bs-dismiss', 'toast')
                .attr('aria-label', 'Close'));
                
        const toastBody = $('<div>')
            .addClass('toast-body')
            .text(message);
            
        toast.append(toastHeader).append(toastBody);
        
        $('#toastContainer').append(toast);
        
        const bsToast = new bootstrap.Toast(toast, {
            autohide: true,
            delay: 3000
        });
        
        bsToast.show();
        
        toast.on('hidden.bs.toast', function() {
            $(this).remove();
        });
    }

    // Initialisation
    updateBalance();
    loadTransactions();
    
    // Mise à jour périodique
    setInterval(function() {
        updateBalance();
        loadTransactions();
    }, 30000); // Toutes les 30 secondes
}); 