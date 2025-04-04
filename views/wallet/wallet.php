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
        <h1 class="mb-4"><i class="fas fa-wallet me-2"></i>Mon Wallet</h1>
        
        <!-- Carte de solde -->
        <div class="balance-card mb-4">
            <div class="row align-items-center">
                <div class="col-md-6 mb-3 mb-md-0">
                    <h2>Solde actuel</h2>
                    <div class="balance-amount"><?php echo number_format($balance, 2); ?> €</div>
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
        
        <!-- Statistiques -->
        <div class="stats-container mb-4">
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="stat-card income h-100">
                        <h3>Revenus du mois</h3>
                        <div class="stat-amount"><?php echo number_format($monthlyIncome, 2); ?> €</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-card expenses h-100">
                        <h3>Dépenses du mois</h3>
                        <div class="stat-amount"><?php echo number_format($monthlyExpenses, 2); ?> €</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Historique des transactions -->
        <div class="transactions-card">
            <h3 class="mb-3">Historique des transactions</h3>
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
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="5" class="text-center">Aucune transaction</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                    <td class="<?php echo $transaction['type'] === 'credit' ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo ($transaction['type'] === 'credit' ? '+' : '-') . number_format($transaction['amount'], 2); ?> €
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $transaction['type'] === 'credit' ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $transaction['type'] === 'credit' ? 'Crédit' : 'Débit'; ?>
                                        </span>
                                    </td>
                                    <td class="d-none d-md-table-cell"><?php echo number_format($transaction['balance_after'], 2); ?> €</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ajouter des fonds -->
<div class="modal fade" id="addFundsModal" tabindex="-1" aria-labelledby="addFundsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addFundsModalLabel">Ajouter des fonds</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addFundsForm">
                    <div class="mb-3">
                        <label for="amount" class="form-label">Montant (€)</label>
                        <input type="number" class="form-control" id="amount" name="amount" min="1" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="paymentMethod" class="form-label">Méthode de paiement</label>
                        <select class="form-select" id="paymentMethod" name="paymentMethod" required>
                            <option value="card">Carte bancaire</option>
                            <option value="paypal">PayPal</option>
                            <option value="bank_transfer">Virement bancaire</option>
                        </select>
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
<div class="modal fade" id="withdrawFundsModal" tabindex="-1" aria-labelledby="withdrawFundsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="withdrawFundsModalLabel">Retirer des fonds</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="withdrawFundsForm">
                    <div class="mb-3">
                        <label for="withdrawAmount" class="form-label">Montant (€)</label>
                        <input type="number" class="form-control" id="withdrawAmount" name="amount" min="1" step="0.01" max="<?php echo $balance; ?>" required>
                        <small class="text-muted">Solde disponible: <?php echo number_format($balance, 2); ?> €</small>
                    </div>
                    <div class="mb-3">
                        <label for="withdrawMethod" class="form-label">Méthode de retrait</label>
                        <select class="form-select" id="withdrawMethod" name="withdrawMethod" required>
                            <option value="bank_transfer">Virement bancaire</option>
                            <option value="paypal">PayPal</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="withdrawDescription" class="form-label">Description (optionnel)</label>
                        <input type="text" class="form-control" id="withdrawDescription" name="description">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="submitWithdrawFunds">Retirer</button>
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