/**
 * Script pour appliquer des modifications spécifiques au rôle de l'utilisateur
 */
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si l'utilisateur est un passager
    const userRole = document.body.getAttribute('data-role');
    
    if (userRole === 'passager') {
        // Modifier le texte "Avis reçus" en "Avis-conducteur" dans la navbar
        const reviewLinks = document.querySelectorAll('a[href="index.php?page=my-reviews"]');
        reviewLinks.forEach(function(link) {
            link.textContent = 'Avis-conducteur';
        });
        
        // Masquer les informations de commission
        const commissionInfos = document.querySelectorAll('.text-muted:not(.commission-info)');
        commissionInfos.forEach(function(info) {
            if (info.textContent.includes('Commission:')) {
                info.classList.add('commission-info');
            }
        });
    }
    
    // Pour les conducteurs avec abonnement business, ajouter des informations sur la commission
    if (userRole === 'conducteur') {
        // Ajouter des informations sur la commission dans les transactions wallet
        const walletTransactions = document.querySelectorAll('.wallet-transaction');
        walletTransactions.forEach(function(transaction) {
            if (transaction.textContent.includes('Revenu sur trajet')) {
                // Vérifier si le texte ne contient pas déjà "+commissions"
                if (!transaction.textContent.includes('+commissions')) {
                    const transactionText = transaction.querySelector('.transaction-description');
                    if (transactionText) {
                        transactionText.textContent += ' +commissions';
                    }
                }
            }
        });
    }
});
