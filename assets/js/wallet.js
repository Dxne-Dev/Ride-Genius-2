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
            console.error('jQuery is not loaded');
            return;
        }

        // Fonction pour formater les montants
        function formatAmount(amount) {
            return parseFloat(amount).toFixed(2) + '€';
        }

        // Fonction pour mettre à jour le solde
        function updateBalance() {
            $.ajax({
                url: 'api/wallet_api.php',
                method: 'POST',
                data: { action: 'getBalance' },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('.balance-amount').text(formatAmount(response.balance));
                        // Mettre à jour la valeur maximale du champ de retrait
                        $('#withdrawAmount').attr('max', response.balance);
                        $('small.text-muted').text('Solde disponible: ' + formatAmount(response.balance));
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("Erreur AJAX:", textStatus, errorThrown);
                    
                    // Vérifier si l'erreur est due à un problème de parsing JSON
                    if (textStatus === "parsererror") {
                        console.error("Réponse brute du serveur:", jqXHR.responseText);
                        showNotification('Le serveur a renvoyé une réponse invalide. Veuillez réessayer plus tard.', 'error');
                        return;
                    }
                    
                    showNotification('Erreur lors de la récupération du solde', 'error');
                }
            });
        }

        // Fonction pour charger les transactions
        function loadTransactions() {
            $.ajax({
                url: 'api/wallet_api.php',
                method: 'POST',
                data: { action: 'getTransactions' },
                dataType: 'json',
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
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("Erreur AJAX:", textStatus, errorThrown);
                    
                    // Vérifier si l'erreur est due à un problème de parsing JSON
                    if (textStatus === "parsererror") {
                        console.error("Réponse brute du serveur:", jqXHR.responseText);
                        showNotification('Le serveur a renvoyé une réponse invalide. Veuillez réessayer plus tard.', 'error');
                        return;
                    }
                    
                    showNotification('Erreur lors du chargement des transactions', 'error');
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
                dataType: 'json', // Spécifier explicitement que nous attendons du JSON
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
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("Erreur AJAX:", textStatus, errorThrown);
                    
                    // Vérifier si l'erreur est due à un problème de parsing JSON
                    if (textStatus === "parsererror") {
                        console.error("Réponse brute du serveur:", jqXHR.responseText);
                        showNotification('Le serveur a renvoyé une réponse invalide. Veuillez réessayer plus tard.', 'error');
                        return;
                    }
                    
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
                dataType: 'json', // Spécifier explicitement que nous attendons du JSON
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
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("Erreur AJAX:", textStatus, errorThrown);
                    
                    // Vérifier si l'erreur est due à un problème de parsing JSON
                    if (textStatus === "parsererror") {
                        console.error("Réponse brute du serveur:", jqXHR.responseText);
                        showNotification('Le serveur a renvoyé une réponse invalide. Veuillez réessayer plus tard.', 'error');
                        return;
                    }
                    
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
                dataType: 'json', // Spécifier explicitement que nous attendons du JSON
                success: function(response) {
                    if (response.success) {
                        showNotification(`Dépôt de ${formatAmount(amount)} simulé avec succès`, 'success');
                        updateBalance();
                        loadTransactions();
                    } else {
                        showNotification(response.message || 'Erreur lors de la simulation du dépôt', 'error');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("Erreur AJAX:", textStatus, errorThrown);
                    
                    // Vérifier si l'erreur est due à un problème de parsing JSON
                    if (textStatus === "parsererror") {
                        console.error("Réponse brute du serveur:", jqXHR.responseText);
                        showNotification('Le serveur a renvoyé une réponse invalide. Veuillez réessayer plus tard.', 'error');
                        return;
                    }
                    
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
                dataType: 'json', // Spécifier explicitement que nous attendons du JSON
                success: function(response) {
                    if (response.success) {
                        showNotification(`Retrait de ${formatAmount(amount)} simulé avec succès`, 'success');
                        updateBalance();
                        loadTransactions();
                    } else {
                        showNotification(response.message || 'Erreur lors de la simulation du retrait', 'error');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("Erreur AJAX:", textStatus, errorThrown);
                    
                    // Vérifier si l'erreur est due à un problème de parsing JSON
                    if (textStatus === "parsererror") {
                        console.error("Réponse brute du serveur:", jqXHR.responseText);
                        showNotification('Le serveur a renvoyé une réponse invalide. Veuillez réessayer plus tard.', 'error');
                        return;
                    }
                    
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
                    dataType: 'json', // Spécifier explicitement que nous attendons du JSON
                    success: function(response) {
                        if (response.success) {
                            showNotification('Solde réinitialisé à 100€ avec succès', 'success');
                            updateBalance();
                            loadTransactions();
                        } else {
                            showNotification(response.message || 'Erreur lors de la réinitialisation du solde', 'error');
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("Erreur AJAX:", textStatus, errorThrown);
                        
                        // Vérifier si l'erreur est due à un problème de parsing JSON
                        if (textStatus === "parsererror") {
                            console.error("Réponse brute du serveur:", jqXHR.responseText);
                            showNotification('Le serveur a renvoyé une réponse invalide. Veuillez réessayer plus tard.', 'error');
                            return;
                        }
                        
                        showNotification('Erreur lors de la communication avec le serveur', 'error');
                    }
                });
            }
        });

        // Fonction pour afficher les notifications
        function showNotification(message, type = 'success') {
            const toast = $('<div>').addClass('toast').attr('role', 'alert').attr('aria-live', 'assertive').attr('aria-atomic', 'true');
            const toastHeader = $('<div>').addClass('toast-header');
            const toastTitle = $('<strong>').addClass('me-auto').text(type === 'success' ? 'Succès' : 'Erreur');
            const toastClose = $('<button>').addClass('btn-close').attr('type', 'button').attr('data-bs-dismiss', 'toast');
            const toastBody = $('<div>').addClass('toast-body').text(message);

            toastHeader.append(toastTitle).append(toastClose);
            toast.append(toastHeader).append(toastBody);

            $('#toastContainer').append(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();

            toast.on('hidden.bs.toast', function() {
                $(this).remove();
            });
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

        // Rafraîchissement automatique toutes les 30 secondes
        setInterval(function() {
            updateBalance();
            loadTransactions();
        }, 30000);
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