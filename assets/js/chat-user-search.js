class ChatUserSearch {
    constructor(options = {}) {
        this.searchInput = document.getElementById('searchUsers');
        this.searchResults = document.querySelector('.search-results');
        this.onUserSelect = options.onUserSelect || function() {};
        this.searchTimeout = null;
        this.minSearchLength = 2;

        this.initializeEventListeners();
    }

    initializeEventListeners() {
        // Écouteur d'événement pour la saisie de recherche
        this.searchInput.addEventListener('input', () => {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.handleSearch();
            }, 300); // Délai de 300ms pour éviter trop de requêtes
        });

        // Fermer les résultats lors d'un clic à l'extérieur
        document.addEventListener('click', (e) => {
            if (!this.searchInput.contains(e.target) && !this.searchResults.contains(e.target)) {
                this.clearResults();
            }
        });
    }

    async handleSearch() {
        const query = this.searchInput.value.trim();
        
        // Effacer les résultats si la recherche est trop courte
        if (query.length < this.minSearchLength) {
            this.clearResults();
            return;
        }

        try {
            const response = await fetch(`message_api.php?action=searchUsers&query=${encodeURIComponent(query)}`);
            const data = await response.json();

            if (data.success) {
                this.displayResults(data.users);
            } else {
                console.error('Erreur lors de la recherche:', data.message);
                this.displayError(data.message);
            }
        } catch (error) {
            console.error('Erreur lors de la recherche:', error);
            this.displayError('Une erreur est survenue lors de la recherche');
        }
    }

    displayResults(users) {
        this.searchResults.innerHTML = '';
        
        if (users.length === 0) {
            this.searchResults.innerHTML = '<div class="no-results">Aucun utilisateur trouvé</div>';
            this.searchResults.style.display = 'block';
            return;
        }

        const resultsList = document.createElement('div');
        resultsList.className = 'search-results-list';

        users.forEach(user => {
            const userElement = document.createElement('div');
            userElement.className = 'search-result-item';
            userElement.innerHTML = `
                <img src="${user.profile_image || 'assets/images/default-avatar.png'}" alt="Avatar" class="avatar">
                <div class="user-info">
                    <div class="user-name">${user.first_name} ${user.last_name}</div>
                    <div class="user-email">${user.email}</div>
                </div>
            `;

            userElement.addEventListener('click', () => {
                this.onUserSelect(user);
                this.clearResults();
                this.searchInput.value = '';
            });

            resultsList.appendChild(userElement);
        });

        this.searchResults.appendChild(resultsList);
        this.searchResults.style.display = 'block';
    }

    displayError(message) {
        this.searchResults.innerHTML = `<div class="error-message">${message}</div>`;
        this.searchResults.style.display = 'block';
    }

    clearResults() {
        this.searchResults.innerHTML = '';
        this.searchResults.style.display = 'none';
    }
} 