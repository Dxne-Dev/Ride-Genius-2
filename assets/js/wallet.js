// Code de gestion du wallet
document.addEventListener('DOMContentLoaded', function () {
    // Vérifier si on est sur la page wallet en cherchant un élément spécifique à cette page
    const isWalletPage = document.querySelector('.wallet-container') !== null;
    
    if (isWalletPage) {
        console.log('Page wallet détectée');
        // Tester si KKiaPay est disponible après un délai pour laisser le temps au script de se charger
        setTimeout(function() {
            if (typeof window.openKkiapayWidget !== 'function') {
                console.error("KKiaPay ne s'est pas chargé correctement");
                alert("KKiaPay ne s'est pas initialisé correctement. Vérifiez votre connexion ou rechargez la page.");
            } else {
                console.log('KKiaPay est correctement chargé');
            }
        }, 2000);
        
        initializeWallet(jQuery);
    }
});

// Fonction d'initialisation principale
function initializeWallet($) {
    if (typeof $ === 'undefined') {
        console.error('jQuery is not loaded');
        return;
    }

    // Configuration de KKiaPay
    const KKIA_PUBLIC_KEY = '0d7e7790fe7711efb8fad7f6612bd409';
    const KKIA_SANDBOX = true;

    // Fonction pour vérifier si KKiaPay est disponible
    function isKKiapayAvailable() {
        return typeof window.openKkiapayWidget === 'function';
    }

    // Fonction pour attendre que KKiaPay soit disponible
    function waitForKKiapay(maxWait = 10000) {
        console.log('Attente de KKiaPay...');
        return new Promise((resolve, reject) => {
            if (isKKiapayAvailable()) {
                console.log('KKiaPay est déjà disponible');
                resolve();
                return;
            }

            let waited = 0;
            const interval = 500;
            const checkInterval = setInterval(() => {
                waited += interval;
                if (isKKiapayAvailable()) {
                    clearInterval(checkInterval);
                    console.log('KKiaPay est maintenant disponible');
                    resolve();
                } else if (waited >= maxWait) {
                    clearInterval(checkInterval);
                    reject(new Error("KKiaPay n'est pas devenu disponible après " + maxWait + "ms"));
                }
            }, interval);
        });
    }

    // Fonction pour ouvrir le widget KKiaPay
    async function openKkiaPayWidget(amount, type = 'deposit') {
        console.log('Tentative d\'ouverture du widget KKiaPay');
        
        try {
            // Attendre que KKiaPay soit disponible
            await waitForKKiapay();
            
            // Nettoyer les anciens listeners (en minuscules comme dans la doc)
            ['success', 'failed', 'closed'].forEach(event => {
                if (window.removeKkiapayListener) {
                    window.removeKkiapayListener(event);
                }
            });
            
            // Définir les fonctions de rappel
            function successHandler(data) {
                console.log('KKiaPay succès:', data);
                if (type === 'deposit') {
                    processDepositSuccess(amount, data.transactionId);
                } else {
                    processWithdrawSuccess(amount, data.transactionId);
                }
            }
            
            function failedHandler(error) {
                console.error('KKiaPay échec:', error);
                showNotification('Paiement échoué: ' + (error.message || 'Erreur inconnue'), 'error');
            }
            
            // Ajouter les écouteurs avec la bonne syntaxe
            if (window.addKkiapayListener) {
                window.addKkiapayListener('success', successHandler);
                window.addKkiapayListener('failed', failedHandler);
            }
            
            // Ouvrir le widget avec les bons paramètres
            if (typeof window.openKkiapayWidget === 'function') {
                // Configuration du widget avec les bons paramètres selon la documentation
                const widgetConfig = {
                    amount: parseInt(amount),
                    // Pas besoin de key ici, elle est déjà dans l'URL du script
                    theme: "#4E6BFC",
                    callback: window.location.origin + "/wallet_api.php?callback=true", // URL de callback
                    name: "Ride Genius",
                    description: type === 'deposit' ? "Ajouter des fonds" : "Retirer des fonds",
                    sandbox: KKIA_SANDBOX
                };
                
                console.log('Configuration du widget:', widgetConfig);
                window.openKkiapayWidget(widgetConfig);
                console.log('Widget KKiaPay ouvert');
            } else {
                throw new Error("La fonction openKkiapayWidget n'est pas disponible");
            }
        } catch (error) {
            console.error('Erreur lors de l\'ouverture du widget KKiaPay:', error);
            showNotification('Erreur lors de l\'ouverture du widget de paiement: ' + error.message, 'error');
        }
    }

    // Fonction pour traiter un dépôt réussi
    function processDepositSuccess(amount, transactionId) {
        console.log('Traitement du dépôt:', { amount, transactionId });
        $.ajax({
            url: 'wallet_api.php',
            method: 'POST',
            data: {
                action: 'addFunds',
                amount: amount,
                transaction_id: transactionId
            },
            dataType: 'json',
            success: function(response) {
                console.log('Réponse du serveur pour le dépôt:', response);
                if (response.success) {
                    showNotification('Dépôt effectué avec succès', 'success');
                    $('#addFundsModal').modal('hide');
                    updateBalance();
                    loadTransactions();
                    $('#addFundsForm')[0].reset();
                } else {
                    showNotification(response.message || 'Erreur lors de l\'ajout des fonds', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX pour le dépôt:', { xhr, status, error });
                showNotification('Erreur lors de la communication avec le serveur', 'error');
            }
        });
    }

    // Fonction pour traiter un retrait réussi
    function processWithdrawSuccess(amount, transactionId) {
        console.log('Traitement du retrait:', { amount, transactionId });
        $.ajax({
            url: 'wallet_api.php',
            method: 'POST',
            data: {
                action: 'withdrawFunds',
                amount: amount,
                transaction_id: transactionId
            },
            dataType: 'json',
            success: function(response) {
                console.log('Réponse du serveur pour le retrait:', response);
                if (response.success) {
                    showNotification('Retrait effectué avec succès', 'success');
                    $('#withdrawFundsModal').modal('hide');
                    updateBalance();
                    loadTransactions();
                    $('#withdrawFundsForm')[0].reset();
                } else {
                    showNotification(response.message || 'Erreur lors du retrait des fonds', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX pour le retrait:', { xhr, status, error });
                showNotification('Erreur lors de la communication avec le serveur', 'error');
            }
        });
    }

    // Fonction pour mettre à jour le solde
    function updateBalance() {
        $.ajax({
            url: 'wallet_api.php',
            method: 'POST',
            data: { action: 'getBalance' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('.balance-amount').text(formatAmount(response.balance));
                    $('#withdrawAmount').attr('max', response.balance);
                    $('small.text-muted').text('Solde disponible: ' + formatAmount(response.balance));
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX pour le solde:', { xhr, status, error });
                showNotification('Erreur lors de la récupération du solde', 'error');
            }
        });
    }

    // Fonction pour charger les transactions
    function loadTransactions() {
        $.ajax({
            url: 'wallet_api.php',
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
            error: function(xhr, status, error) {
                console.error('Erreur AJAX pour les transactions:', { xhr, status, error });
                showNotification('Erreur lors du chargement des transactions', 'error');
            }
        });
    }

    // Fonction pour formater les montants
    function formatAmount(amount) {
        return parseFloat(amount).toFixed(2) + ' FCFA';
    }

    // Fonction pour afficher les notifications
    function showNotification(message, type = 'success') {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alert = $('<div>')
            .addClass('alert ' + alertClass + ' alert-dismissible fade show')
            .attr('role', 'alert')
            .html(`
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `);
        
        $('.wallet-container').prepend(alert);
        
        // Supprimer la notification après 5 secondes
        setTimeout(() => {
            alert.alert('close');
        }, 5000);
    }

    // Gestionnaires d'événements
    $('#submitAddFunds').on('click', function() {
        const amount = parseFloat($('#amount').val());
        
        if (isNaN(amount) || amount <= 0) {
            showNotification('Le montant doit être supérieur à 0', 'error');
            return;
        }
        
        openKkiaPayWidget(amount, 'deposit');
    });

    $('#submitWithdrawFunds').on('click', function() {
        const amount = parseFloat($('#withdrawAmount').val());
        
        if (isNaN(amount) || amount <= 0) {
            showNotification('Le montant doit être supérieur à 0', 'error');
            return;
        }
        
        openKkiaPayWidget(amount, 'withdraw');
    });

    // Initialisation
    $(document).ready(function() {
        // Mettre à jour le solde et charger les transactions
        updateBalance();
        loadTransactions();
    });
} 