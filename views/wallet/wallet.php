<?php
// Vérification de la session (déjà démarrée dans index.php)
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

// Initialisation de la connexion à la base de données
require_once __DIR__ . '/../../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Récupération des informations de l'utilisateur
require_once __DIR__ . '/../../models/User.php';
$user = new User($db);
$userData = $user->findById($_SESSION['user_id']);

// Récupération des informations du wallet
require_once __DIR__ . '/../../models/Wallet.php';
$wallet = new Wallet($db);
$balance = $wallet->getBalance($_SESSION['user_id']);
$transactions = $wallet->getTransactions($_SESSION['user_id']);
$monthlyIncome = $wallet->getMonthlyIncome($_SESSION['user_id']);
$monthlyExpenses = $wallet->getMonthlyExpenses($_SESSION['user_id']);

// Inclusion du header et de la navbar
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';
?>

<div class="container mt-5 pt-5">
    <div class="wallet-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-wallet me-2"></i>Mon Wallet</h1>
            <div class="sandbox-badge">
                <span class="badge bg-warning text-dark">
                    <i class="fas fa-flask me-1"></i> Mode démonstration
                </span>
            </div>
        </div>
        
        <!-- Carte de solde -->
        <div class="balance-card mb-4">
            <div class="row align-items-center">
                <div class="col-md-6 mb-3 mb-md-0">
                    <h2>Solde actuel</h2>
                    <div class="balance-amount"><?php echo number_format($balance, 2); ?> FCFA</div>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="d-grid d-md-inline-block gap-2">
                        <button class="btn btn-light mb-2 mb-md-0 me-md-2" data-bs-toggle="modal" data-bs-target="#addFundsModal">
                            <i class="fas fa-plus-circle me-1"></i> Ajouter des fonds
                        </button>
                        <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#withdrawFundsModal">
                            <i class="fas fa-minus-circle me-1"></i> Retirer des fonds
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Boutons de simulation rapide -->
        <div class="quick-actions mb-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Actions rapides (Mode démonstration)</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6>Simuler un dépôt</h6>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-success quick-deposit" data-amount="10">10 FCFA</button>
                                <button type="button" class="btn btn-outline-success quick-deposit" data-amount="50">50 FCFA</button>
                                <button type="button" class="btn btn-outline-success quick-deposit" data-amount="100">100 FCFA</button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Simuler un retrait</h6>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-danger quick-withdraw" data-amount="10">10 FCFA</button>
                                <button type="button" class="btn btn-outline-danger quick-withdraw" data-amount="50">50 FCFA</button>
                                <button type="button" class="btn btn-outline-danger quick-withdraw" data-amount="100">100 FCFA</button>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="button" class="btn btn-outline-secondary" id="resetBalance">
                            <i class="fas fa-redo me-1"></i> Réinitialiser le solde à 100 FCFA
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistiques -->
        <div class="stats-container mb-4">
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="stat-card income h-100">
                        <h3>Revenus du mois</h3>
                        <div class="stat-amount"><?php echo number_format($monthlyIncome, 2); ?> FCFA</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-card expenses h-100">
                        <h3>Dépenses du mois</h3>
                        <div class="stat-amount"><?php echo number_format($monthlyExpenses, 2); ?> FCFA</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Historique des transactions -->
        <div class="transactions-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Historique des transactions</h3>
                <div class="transaction-filters">
                    <select class="form-select form-select-sm" id="transactionFilter">
                        <option value="all">Toutes les transactions</option>
                        <option value="credit">Dépôts uniquement</option>
                        <option value="debit">Retraits uniquement</option>
                    </select>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="transactions-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Montant</th>
                            <th>Type</th>
                            <th class="d-none d-md-table-cell">Solde après</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>                        <tr>
                                <td colspan="5" class="text-center">Aucune transaction</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr class="transaction-row <?php echo $transaction['type']; ?>" data-date="<?php echo $transaction['created_at']; ?>">
                                    <td data-date-raw="<?php echo $transaction['created_at']; ?>">
                                        <?php 
                                        // Vérifier si la date est valide avant de l'afficher
                                        $date = $transaction['created_at'];
                                        if (!empty($date) && $date != '0000-00-00 00:00:00') {
                                            echo date('d/m/Y H:i', strtotime($date));
                                        } else {
                                            echo "Date inconnue";
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                    <td class="<?php echo $transaction['type'] === 'credit' ? 'credit' : 'debit'; ?>">
                                        <?php echo $transaction['type'] === 'credit' ? '+' : '-'; ?>
                                        <?php echo number_format($transaction['amount'], 2); ?> FCFA
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $transaction['type'] === 'credit' ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $transaction['type'] === 'credit' ? 'Dépôt' : 'Retrait'; ?>
                                        </span>
                                    </td>
                                    <td class="d-none d-md-table-cell"><?php echo number_format($transaction['balance_after'], 2); ?> FCFA</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="transaction-summary">
                    <span class="me-3">Total dépôts: <strong class="text-success"><?php echo number_format($monthlyIncome, 2); ?> FCFA</strong></span>
                    <span>Total retraits: <strong class="text-danger"><?php echo number_format($monthlyExpenses, 2); ?> FCFA</strong></span>
                </div>
                <div class="transaction-pagination">
                    <button class="btn btn-sm btn-outline-primary" id="loadMoreTransactions">
                        <i class="fas fa-plus-circle me-1"></i> Charger plus
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ajouter des fonds -->
<div class="modal fade" id="addFundsModal" tabindex="-1" aria-labelledby="addFundsModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addFundsModalLabel">Ajouter des fonds</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addFundsForm">
                    <div class="mb-3">
                        <label for="amount" class="form-label">Montant (FCFA)</label>
                        <input type="number" class="form-control" id="amount" name="amount" min="1" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="paymentMethod" class="form-label">Méthode de paiement</label>
                        <select class="form-select" id="paymentMethod" name="paymentMethod" required>
                        <option value="card">Carte bancaire</option>
<option value="paypal">PayPal</option>
<option value="kkiapay">KKiaPay</option> <!-- ✅ ajout -->
</select>
                    </div>
                    <!-- Champ dynamique pour les détails de paiement -->
<div class="mb-3" id="paymentDetailsContainer" style="display: none;">
    <label for="paymentDetails" class="form-label" id="paymentDetailsLabel">Numéro</label>
    <input type="text" class="form-control" id="paymentDetails" name="paymentDetails">
    <div class="invalid-feedback" id="paymentDetailsFeedback"></div>
</div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description (optionnel)</label>
                        <input type="text" class="form-control" id="description" name="description">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="submitAddFunds">Ajouter</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Retirer des fonds -->
<div class="modal fade" id="withdrawFundsModal" tabindex="-1" aria-labelledby="withdrawFundsModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="withdrawFundsModalLabel">Retirer des fonds</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="withdrawFundsForm">
                    <div class="mb-3">
                        <label for="withdrawAmount" class="form-label">Montant (FCFA)</label>
                        <input type="number" 
                               class="form-control" 
                               id="withdrawAmount" 
                               name="amount" 
                               min="1" 
                               step="0.01" 
                               max="<?php echo $balance; ?>" 
                               required
                               oninput="this.value = Math.min(this.value, this.max)">
                        <div class="invalid-feedback">
                            Le montant ne peut pas dépasser votre solde disponible
                        </div>
                        <small class="text-muted">Solde disponible: <?php echo number_format($balance, 2); ?> FCFA</small>
                    </div>
                    <div class="mb-3">
                        <label for="withdrawMethod" class="form-label">Méthode de retrait</label>
                        <select class="form-select" id="withdrawMethod" name="withdrawMethod" required>
                            <option value="card">Carte bancaire</option>
                            <option value="paypal">PayPal</option>
                            <option value="kkiapay">KKiaPay</option>
                        </select>
                        <div class="mb-3" id="withdrawDetailsContainer" style="display: none;">
                            <label for="withdrawDetails" class="form-label" id="withdrawDetailsLabel">Numéro</label>
                            <input type="text" class="form-control" id="withdrawDetails" name="withdrawDetails">
                            <div class="invalid-feedback" id="withdrawDetailsFeedback"></div>
                        </div>
                        <div id="kkiapayInfo" class="mt-2 text-info d-none">
                            <i class="fas fa-info-circle"></i> Le retrait via KKiaPay est traité instantanément
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="withdrawDescription" class="form-label">Description (optionnel)</label>
                        <input type="text" class="form-control" id="withdrawDescription" name="description">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="submitWithdrawFunds" disabled>Retirer</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast pour les notifications -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="notificationToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto" id="toastTitle">Notification</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toastMessage">
            Message de notification
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
<!-- SDK KKiaPay -->
<script src="https://cdn.kkiapay.me/k.js"></script>
<!-- Inclusion du JS wallet -->
<script src="/assets/js/wallet.js"></script>
<script src="/assets/js/payment-form.js"></script>
<script>
    // Variables globales
    const MAX_WITHDRAWAL_AMOUNT = <?php echo $balance; ?>;
    const WITHDRAW_AMOUNT_INPUT = document.getElementById('withdrawAmount');
    const WITHDRAW_FORM = document.getElementById('withdrawFundsForm');
    const SUBMIT_WITHDRAW_BTN = document.getElementById('submitWithdrawFunds');

    // Afficher les informations spécifiques à KKiaPay
    document.getElementById('withdrawMethod').addEventListener('change', function() {
        const kkiapayInfo = document.getElementById('kkiapayInfo');
        if (this.value === 'kkiapay') {
            kkiapayInfo.classList.remove('d-none');
        } else {
            kkiapayInfo.classList.add('d-none');
        }
    });

    // Validation du montant de retrait
    function validateWithdrawalAmount() {
        const amount = parseFloat(WITHDRAW_AMOUNT_INPUT.value);
        const isValid = !isNaN(amount) && amount > 0 && amount <= MAX_WITHDRAWAL_AMOUNT;
        
        if (isValid) {
            WITHDRAW_AMOUNT_INPUT.classList.remove('is-invalid');
            SUBMIT_WITHDRAW_BTN.disabled = false;
            return true;
        } else {
            WITHDRAW_AMOUNT_INPUT.classList.add('is-invalid');
            SUBMIT_WITHDRAW_BTN.disabled = true;
            
            if (amount > MAX_WITHDRAWAL_AMOUNT) {
                showNotification('Le montant demandé dépasse votre solde disponible', 'error');
            }
            return false;
        }
    }

    // Événements de validation
    if (WITHDRAW_AMOUNT_INPUT) {
        WITHDRAW_AMOUNT_INPUT.addEventListener('input', validateWithdrawalAmount);
        WITHDRAW_AMOUNT_INPUT.addEventListener('blur', validateWithdrawalAmount);
    }

    // Validation du formulaire avant soumission
    if (WITHDRAW_FORM) {
        WITHDRAW_FORM.addEventListener('submit', function(e) {
            if (!validateWithdrawalAmount()) {
                e.preventDefault();
                return false;
            }
            return true;
        });
    }

    // Initialisation du JS wallet si la fonction existe
    if (typeof initializeWallet === 'function') {
        initializeWallet(window.jQuery || undefined);
    } else if (window.jQuery) {
        // Si la fonction n'est pas encore définie, attendre le chargement du script
        $(function() {
            if (typeof initializeWallet === 'function') {
                initializeWallet(window.jQuery);
            }
        });
    }

    // Fonction pour recharger la page après une opération
    function reloadPageAfterOperation(delay = 1500) {
        setTimeout(() => {
            window.location.reload();
        }, delay);
    }
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestionnaires pour les boutons d'ajout et de retrait de fonds
    const submitAddFundsBtn = document.getElementById('submitAddFunds');
    const submitWithdrawFundsBtn = document.getElementById('submitWithdrawFunds');
    const quickDepositBtns = document.querySelectorAll('.quick-deposit');
    const quickWithdrawBtns = document.querySelectorAll('.quick-withdraw');
    const resetBalanceBtn = document.getElementById('resetBalance');
    
    // Ajouter des fonds
    if (submitAddFundsBtn) {
        submitAddFundsBtn.addEventListener('click', function() {
            // Afficher une notification
            showNotification('Fonds ajoutés avec succès', 'success');
            // Recharger la page après un délai
            reloadPageAfterOperation(1500);
        });
    }
    
    // Retirer des fonds
    if (submitWithdrawFundsBtn) {
        submitWithdrawFundsBtn.addEventListener('click', function() {
            // Afficher une notification
            showNotification('Fonds retirés avec succès', 'success');
            // Recharger la page après un délai
            reloadPageAfterOperation(1500);
        });
    }
    
    // Dépôt rapide
    quickDepositBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const amount = this.getAttribute('data-amount');
            showNotification(`Dépôt de ${amount} FCFA effectué`, 'success');
            reloadPageAfterOperation(1000);
        });
    });
    
    // Retrait rapide
    quickWithdrawBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const amount = this.getAttribute('data-amount');
            showNotification(`Retrait de ${amount} FCFA effectué`, 'success');
            reloadPageAfterOperation(1000);
        });
    });
    
    // Réinitialisation du solde
    if (resetBalanceBtn) {
        resetBalanceBtn.addEventListener('click', function() {
            showNotification('Solde réinitialisé à 100 FCFA', 'success');
            reloadPageAfterOperation(1000);
        });
    }
    
    // Éléments du DOM pour les transactions
    const transactionFilter = document.getElementById('transactionFilter');
    const transactionTableBody = document.querySelector('.transactions-table tbody');
    const transactionSummary = document.querySelector('.transaction-summary');
    const loadMoreBtn = document.getElementById('loadMoreTransactions');
    
    // Fonction simplifiée pour formater la date
    function formatDate(dateString) {
        try {
            // Vérifier si la date est vide ou invalide
            if (!dateString || dateString === '0000-00-00 00:00:00' || dateString === 'null') {
                return "Date inconnue";
            }
            
            // Créer un objet Date à partir de la chaîne
            const date = new Date(dateString);
            
            // Vérifier si la date est valide
            if (isNaN(date.getTime())) {
                console.error('Date invalide:', dateString);
                return "Date inconnue";
            }
            
            // Formater la date au format JJ/MM/AA HH:MM
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = String(date.getFullYear()).substr(-2);
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            
            return `${day}/${month}/${year} ${hours}:${minutes}`;
        } catch (error) {
            console.error('Erreur lors du formatage de la date:', error);
            return "Date inconnue";
        }
    }
    
    // Fonction pour filtrer et afficher les transactions
    function filterTransactions(filterValue) {
        // Récupérer toutes les lignes de transaction
        const rows = document.querySelectorAll('.transaction-row');
        
        // Compteurs pour le résumé
        let totalCredit = 0;
        let totalDebit = 0;
        let visibleCount = 0;
        
        // Vider le tableau
        transactionTableBody.innerHTML = '';
        
        // Parcourir toutes les lignes
        rows.forEach(row => {
            const type = row.classList.contains('credit') ? 'credit' : 'debit';
            
            // Vérifier si la ligne correspond au filtre
            if (filterValue === 'all' || filterValue === type) {
                // Cloner la ligne
                const newRow = row.cloneNode(true);
                
                // Formater la date
                const dateCell = newRow.querySelector('td:first-child');
                if (dateCell) {
                    const rawDate = dateCell.getAttribute('data-date-raw');
                    if (rawDate) {
                        dateCell.textContent = formatDate(rawDate);
                    }
                }
                
                // Ajouter la ligne au tableau
                transactionTableBody.appendChild(newRow);
                
                // Mettre à jour les compteurs
                const amountCell = newRow.querySelector('td:nth-child(3)');
                if (amountCell) {
                    const amountText = amountCell.textContent.trim();
                    const amount = parseFloat(amountText.replace(/[^0-9.-]+/g, ''));
                    
                    if (!isNaN(amount)) {
                        if (type === 'credit') {
                            totalCredit += amount;
                        } else {
                            totalDebit += amount;
                        }
                    }
                }
                
                visibleCount++;
            }
        });
        
        // Mettre à jour le résumé
        if (transactionSummary) {
            transactionSummary.innerHTML = `
                <span class="me-3">Total dépôts: <strong class="text-success">${totalCredit.toFixed(2)} FCFA</strong></span>
                <span>Total retraits: <strong class="text-danger">${totalDebit.toFixed(2)} FCFA</strong></span>
            `;
        }
        
        // Afficher un message si aucune transaction ne correspond au filtre
        if (visibleCount === 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = '<td colspan="5" class="text-center">Aucune transaction correspondant au filtre</td>';
            transactionTableBody.appendChild(emptyRow);
        }
    }
    
    // Appliquer le filtre initial
    filterTransactions(transactionFilter.value);
    
    // Écouter les changements de filtre
    transactionFilter.addEventListener('change', function() {
        filterTransactions(this.value);
    });
    
    // Chargement de plus de transactions
    loadMoreBtn.addEventListener('click', function() {
        // Simuler le chargement
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Chargement...';
        
        setTimeout(() => {
            this.innerHTML = '<i class="fas fa-plus-circle me-1"></i> Charger plus';
            // Dans une implémentation réelle, vous mettriez à jour le tableau avec les nouvelles données
        }, 1000);
    });
    
    // Initialisation des tooltips Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>