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
        // Empêcher l'initialisation multiple
        if (window.walletInitialized) {
            return;
        }
        window.walletInitialized = true;

        // Nettoyer un éventuel intervalle existant
        if (window.walletIntervalId) {
            clearInterval(window.walletIntervalId);
        }

        if (typeof $ === 'undefined') {
            console.error('jQuery is not loaded');
            return;
        }

        // Fonction pour formater les montants
        function formatAmount(amount) {
            return parseFloat(amount).toFixed(2) + 'FCFA';
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
        $('#submitAddFunds').on('click', function(e) {
            e.preventDefault();
            const amount = parseFloat($('#amount').val());
            const paymentMethod = $('#paymentMethod').val();
            const description = $('#description').val() || 'Dépôt via KKiaPay';

            if (isNaN(amount) || amount <= 0) {
                showNotification('Le montant doit être supérieur à 0', 'error');
                return;
            }

            if (paymentMethod === 'kkiapay') {
                // Empêcher double ouverture
                if (window.kkiapayPaymentPending) return;
                window.kkiapayPaymentPending = true;
                const userId = $('#userId').val() || '';
                // Forcer le chargement depuis localhost si nécessaire
                const iframeDomain = window.location.hostname === 'ride-genius'
                    ? 'http://localhost/ride-genius'
                    : window.location.origin;

                const iframeUrl = `${iframeDomain}/kkiapay-iframe.html?amount=${amount}&userId=${userId}`;

                const popup = window.open(
                    iframeUrl,
                    'Paiement KKiaPay',
                    'width=600,height=750'
                );

                // Écoute la réponse de KKiaPay via postMessage
                window.addEventListener("message", function(event) {
                    window.kkiapayPaymentPending = false;
                    if (event.data.status === 'success') {
                        // Paiement validé → appelle l’API wallet pour créditer
                        $.post('api/wallet_api.php', {
                            action: 'addFunds',
                            amount: event.data.amount,
                            paymentMethod: 'kkiapay',
                            description: 'Paiement via KKiaPay',
                            transaction_id: event.data.transactionId
                        }, function(resp) {
                            if (resp.success) {
                                showNotification('Fonds ajoutés avec succès via KKiaPay', 'success');
                                $('#addFundsModal').modal('hide');
                                updateBalance();
                                loadTransactions();
                                $('#addFundsForm')[0].reset();
                            } else {
                                showNotification('Erreur côté serveur KKiaPay', 'error');
                            }
                        }, 'json');
                    } else if (event.data.status === 'error') {
                        showNotification('Paiement KKiaPay échoué', 'error');
                    }
                }, { once: true });
                return; // Stop ici, on ne passe pas à l'AJAX classique
            }

            // Cas standard (sandbox ou autres méthodes)
            $.ajax({
                url: 'api/wallet_api.php',
                method: 'POST',
                data: {
                    action: 'addFunds',
                    amount: amount,
                    paymentMethod: paymentMethod,
                    description: description
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showNotification('Fonds ajoutés avec succès', 'success');
                        $('#addFundsModal').modal('hide');
                        setTimeout(function() {
                            $('.modal-backdrop').remove();
                            $('body').removeClass('modal-open').css('padding-right', '');
                        }, 500);
                        updateBalance();
                        loadTransactions();
                        $('#addFundsForm')[0].reset();
                    } else {
                        showNotification(response.message || 'Erreur lors de l\'ajout des fonds', 'error');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("Erreur AJAX:", textStatus, errorThrown);
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
        $('#submitWithdrawFunds').on('click', function(e) {
            e.preventDefault();
            const $form = $(this).closest('form');
            const amount = parseFloat($('#withdrawAmount').val());
            const withdrawMethod = $('#withdrawMethod').val();
            const description = $('#withdrawDescription').val() || 'Retrait via KKiaPay';
            const userId = $('#userId').val() || '';
            
            if (isNaN(amount) || amount <= 0) {
                showNotification('Le montant doit être supérieur à 0', 'error');
                return;
            }
            
            // Vérifier que le montant ne dépasse pas le solde
            const currentBalance = parseFloat($('.balance-amount').text().replace(/[^0-9.,]/g, '').replace(',', '.'));
            if (amount > currentBalance) {
                showNotification('Le montant demandé dépasse votre solde disponible', 'error');
                return;
            }
            
            // Désactiver le bouton pour éviter les clics multiples
            const $submitBtn = $(this);
            const originalText = $submitBtn.html();
            $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Traitement...');
            
            if (withdrawMethod === 'kkiapay') {
                // Vérifier si un retrait est déjà en cours
                if (window.kkiapayWithdrawPending) {
                    showNotification('Un retrait est déjà en cours', 'warning');
                    $submitBtn.prop('disabled', false).html(originalText);
                    return;
                }
                
                window.kkiapayWithdrawPending = true;
                
                // Construire l'URL de l'iframe KKiaPay
                // Utiliser le même format d'URL que pour le dépôt
                const iframeDomain = window.location.hostname === 'ride-genius' || window.location.hostname === 'localhost'
                    ? 'http://localhost/ride-genius'
                    : window.location.origin;
                
                const iframeUrl = new URL(`${iframeDomain}/kkiapay-iframe.html`);
                iframeUrl.searchParams.append('amount', amount);
                iframeUrl.searchParams.append('userId', userId || '');
                iframeUrl.searchParams.append('type', 'withdraw');
                if (description) {
                    iframeUrl.searchParams.append('description', description);
                }

                // Ouvrir la fenêtre KKiaPay
                const popup = window.open(
                    iframeUrl.toString(),
                    'Retrait KKiaPay',
                    'width=600,height=750,scrollbars=no,resizable=no,location=no,menubar=no,status=no'
                );

                // Vérifier si la fenêtre s'est bien ouverte
                if (!popup || popup.closed || typeof popup.closed === 'undefined') {
                    showNotification('Veuvez autoriser les fenêtres popups pour ce site pour effectuer un retrait', 'error');
                    window.kkiapayWithdrawPending = false;
                    $submitBtn.prop('disabled', false).html(originalText);
                    return;
                }

                // Gérer la réponse de KKiaPay
                const messageHandler = function(event) {
                    // Vérifier l'origine du message pour des raisons de sécurité
                    if (event.origin !== window.location.origin) {
                        return;
                    }

                    // Nettoyer l'écouteur d'événements
                    window.removeEventListener('message', messageHandler);
                    window.kkiapayWithdrawPending = false;
                    $submitBtn.prop('disabled', false).html(originalText);

                    switch (event.data.status) {
                        case 'success':
                            // Mettre à jour l'interface utilisateur
                            showNotification('Retrait effectué avec succès', 'success');
                            $form[0].reset();
                            updateBalance();
                            loadTransactions();
                            $('#withdrawFundsModal').modal('hide');
                            break;
                            
                        case 'cancelled':
                            showNotification('Retrait annulé', 'info');
                            break;
                            
                        case 'error':
                            showNotification('Erreur de retrait: ' + (event.data.message || 'Veuillez réessayer'), 'error');
                            break;
                    }
                };

                // Ajouter l'écouteur d'événements
                window.addEventListener('message', messageHandler);
                
                // Timeout au cas où on ne reçoit pas de réponse
                setTimeout(() => {
                    if (window.kkiapayWithdrawPending) {
                        window.removeEventListener('message', messageHandler);
                        window.kkiapayWithdrawPending = false;
                        $submitBtn.prop('disabled', false).html(originalText);
                        showNotification('Délai dépassé. Veuillez vérifier votre retrait et réessayer si nécessaire.', 'warning');
                    }
                }, 600000); // 10 minutes
                
                return; // Ne pas continuer avec le traitement AJAX standard
            }

            $.ajax({
                url: 'api/wallet_api.php',
                method: 'POST',
                data: {
                    action: 'withdrawFunds',
                    amount: amount,
                    withdrawMethod: withdrawMethod,
                    description: description,
                    transaction_id: null
                },
                dataType: 'json', // Spécifier explicitement que nous attendons du JSON
                success: function(response) {
                    if (response.success) {
                        showNotification('Fonds retirés avec succès', 'success');
                        $('#withdrawFundsModal').modal('hide');
                        // Correction : forcer la suppression du backdrop si besoin
                        setTimeout(function() {
                            $('.modal-backdrop').remove();
                            $('body').removeClass('modal-open').css('padding-right', '');
                        }, 500);
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
            if (confirm('Êtes-vous sûr de vouloir réinitialiser votre solde à 100FCFA ?')) {
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
                            showNotification('Solde réinitialisé à 100FCFA avec succès', 'success');
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
        window.walletIntervalId = setInterval(function() {
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