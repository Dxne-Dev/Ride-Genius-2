// Fonction pour vérifier si jQuery est chargé et l'initialiser si nécessaire
(function() {
    // Fonction pour charger jQuery dynamiquement
    function loadJQuery(callback) {
        if (typeof jQuery !== 'undefined') {
            // jQuery est déjà chargé
            callback(jQuery);
            return;
        }

        // Créer un élément script pour charger jQuery
        const script = document.createElement('script');
        script.src = 'https://code.jquery.com/jquery-3.6.0.min.js';
        script.integrity = 'sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=';
        script.crossOrigin = 'anonymous';
        
        // Ajouter un gestionnaire d'événements pour le chargement
        script.onload = function() {
            console.log('jQuery chargé avec succès');
            // Attendre un court délai pour s'assurer que jQuery est complètement initialisé
            setTimeout(function() {
                callback(jQuery);
            }, 100);
        };
        
        script.onerror = function() {
            console.error('Échec du chargement de jQuery');
        };
        
        // Insérer le script avant tous les autres scripts
        const firstScript = document.getElementsByTagName('script')[0];
        firstScript.parentNode.insertBefore(script, firstScript);
    }

    // Fonction d'initialisation principale
    function initializeWallet($) {
        if (typeof $ === 'undefined') {
            console.error('jQuery n\'est toujours pas disponible après le chargement');
            return;
        }

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
                        $('.balance-amount').text(formatAmount(response.balance));
                        // Mettre à jour la valeur maximale du champ de retrait
                        $('#withdrawAmount').attr('max', response.balance);
                        $('small.text-muted').text('Solde disponible: ' + formatAmount(response.balance));
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
                        const tbody = $('.transactions-table tbody');
                        tbody.empty();
                        
                        if (response.transactions.length === 0) {
                            tbody.append('<tr><td colspan="5" class="text-center">Aucune transaction</td></tr>');
                            return;
                        }
                        
                        response.transactions.forEach(function(transaction) {
                            const row = $('<tr>');
                            row.append($('<td>').text(new Date(transaction.created_at).toLocaleString('fr-FR')));
                            row.append($('<td>').text(transaction.description || '-'));
                            
                            const amountCell = $('<td>').addClass(transaction.type === 'credit' ? 'text-success' : 'text-danger');
                            amountCell.text((transaction.type === 'credit' ? '+' : '-') + formatAmount(transaction.amount));
                            row.append(amountCell);
                            
                            const typeBadge = $('<span>').addClass('badge ' + (transaction.type === 'credit' ? 'bg-success' : 'bg-danger'));
                            typeBadge.text(transaction.type === 'credit' ? 'Crédit' : 'Débit');
                            row.append($('<td>').append(typeBadge));
                            
                            row.append($('<td>').addClass('d-none d-md-table-cell').text(formatAmount(transaction.balance_after)));
                            tbody.append(row);
                        });
                    }
                }
            });
        }

        // Gestionnaire pour l'ajout de fonds
        $('#submitAddFunds').on('click', function() {
            const amount = parseFloat($('#amount').val());
            const paymentMethod = $('#paymentMethod').val();
            const description = $('#description').val() || 'Dépôt de fonds';
            
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
                        // Réinitialiser le formulaire
                        $('#addFundsForm')[0].reset();
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
        $('#submitWithdrawFunds').on('click', function() {
            const amount = parseFloat($('#withdrawAmount').val());
            const withdrawMethod = $('#withdrawMethod').val();
            const description = $('#withdrawDescription').val() || 'Retrait de fonds';
            
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
                        showNotification('Fonds retirés avec succès', 'success');
                        $('#withdrawFundsModal').modal('hide');
                        updateBalance();
                        loadTransactions();
                        // Réinitialiser le formulaire
                        $('#withdrawFundsForm')[0].reset();
                    } else {
                        showNotification(response.message || 'Erreur lors du retrait des fonds', 'error');
                    }
                },
                error: function() {
                    showNotification('Erreur lors de la communication avec le serveur', 'error');
                }
            });
        });

        // Gestionnaire pour les boutons de dépôt rapide (mode sandbox)
        $('.quick-deposit').on('click', function() {
            const amount = parseFloat($(this).data('amount'));
            const description = 'Dépôt rapide (Mode démonstration)';
            
            $.ajax({
                url: 'api/wallet_api.php',
                method: 'POST',
                data: {
                    action: 'addFunds',
                    amount: amount,
                    paymentMethod: 'sandbox',
                    description: description
                },
                success: function(response) {
                    if (response.success) {
                        showNotification(`Dépôt de ${formatAmount(amount)} simulé avec succès`, 'success');
                        updateBalance();
                        loadTransactions();
                    } else {
                        showNotification(response.message || 'Erreur lors de la simulation du dépôt', 'error');
                    }
                },
                error: function() {
                    showNotification('Erreur lors de la communication avec le serveur', 'error');
                }
            });
        });

        // Gestionnaire pour les boutons de retrait rapide (mode sandbox)
        $('.quick-withdraw').on('click', function() {
            const amount = parseFloat($(this).data('amount'));
            const description = 'Retrait rapide (Mode démonstration)';
            
            $.ajax({
                url: 'api/wallet_api.php',
                method: 'POST',
                data: {
                    action: 'withdrawFunds',
                    amount: amount,
                    withdrawMethod: 'sandbox',
                    description: description
                },
                success: function(response) {
                    if (response.success) {
                        showNotification(`Retrait de ${formatAmount(amount)} simulé avec succès`, 'success');
                        updateBalance();
                        loadTransactions();
                    } else {
                        showNotification(response.message || 'Erreur lors de la simulation du retrait', 'error');
                    }
                },
                error: function() {
                    showNotification('Erreur lors de la communication avec le serveur', 'error');
                }
            });
        });

        // Gestionnaire pour réinitialiser le solde (mode sandbox)
        $('#resetBalance').on('click', function() {
            if (confirm('Êtes-vous sûr de vouloir réinitialiser votre solde à 100€ ?')) {
                $.ajax({
                    url: 'api/wallet_api.php',
                    method: 'POST',
                    data: {
                        action: 'resetBalance',
                        amount: 100
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification('Solde réinitialisé à 100€ avec succès', 'success');
                            updateBalance();
                            loadTransactions();
                        } else {
                            showNotification(response.message || 'Erreur lors de la réinitialisation du solde', 'error');
                        }
                    },
                    error: function() {
                        showNotification('Erreur lors de la communication avec le serveur', 'error');
                    }
                });
            }
        });

        // Fonction pour afficher les notifications
        function showNotification(message, type = 'info') {
            const toast = $('#notificationToast');
            const toastTitle = $('#toastTitle');
            const toastMessage = $('#toastMessage');
            
            // Définir le titre et le message en fonction du type
            switch (type) {
                case 'success':
                    toastTitle.text('Succès');
                    toast.removeClass('bg-danger bg-warning').addClass('bg-success');
                    break;
                case 'error':
                    toastTitle.text('Erreur');
                    toast.removeClass('bg-success bg-warning').addClass('bg-danger');
                    break;
                case 'warning':
                    toastTitle.text('Attention');
                    toast.removeClass('bg-success bg-danger').addClass('bg-warning');
                    break;
                default:
                    toastTitle.text('Information');
                    toast.removeClass('bg-success bg-danger bg-warning');
                    break;
            }
            
            toastMessage.text(message);
            
            // Afficher le toast
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
        }

        // Gestionnaires pour les modales Bootstrap
        $('#addFundsModal').on('shown.bs.modal', function() {
            // Mettre le focus sur le premier champ du formulaire
            $('#amount').focus();
        });

        $('#withdrawFundsModal').on('shown.bs.modal', function() {
            // Mettre le focus sur le premier champ du formulaire
            $('#withdrawAmount').focus();
        });

        // Initialisation
        updateBalance();
        loadTransactions();
    }

    // Attendre que le DOM soit chargé avant de vérifier jQuery
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            loadJQuery(initializeWallet);
        });
    } else {
        loadJQuery(initializeWallet);
    }
})(); 