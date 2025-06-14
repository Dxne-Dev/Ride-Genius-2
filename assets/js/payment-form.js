document.addEventListener('DOMContentLoaded', function() {
    const paymentMethod = document.getElementById('paymentMethod');
    const paymentDetailsContainer = document.getElementById('paymentDetailsContainer');
    const paymentDetailsLabel = document.getElementById('paymentDetailsLabel');
    const paymentDetails = document.getElementById('paymentDetails');
    const paymentDetailsFeedback = document.getElementById('paymentDetailsFeedback');

    if (!paymentMethod) return;

    paymentMethod.addEventListener('change', function() {
        paymentDetails.classList.remove('is-invalid');
        paymentDetailsFeedback.textContent = '';
        paymentDetails.value = '';
        paymentDetails.type = 'text';
        paymentDetails.removeAttribute('pattern');
        paymentDetails.removeAttribute('maxlength');

        switch(this.value) {
            case 'card':
                paymentDetailsContainer.style.display = 'block';
                paymentDetailsLabel.textContent = 'Numéro de carte';
                paymentDetails.placeholder = 'Entrez votre numéro de carte';
                paymentDetails.pattern = '[0-9]{16}';
                paymentDetails.maxLength = 16;
                break;
            case 'paypal':
                paymentDetailsContainer.style.display = 'block';
                paymentDetailsLabel.textContent = 'Email du compte PayPal';
                paymentDetails.placeholder = 'Entrez votre email PayPal';
                paymentDetails.type = 'email';
                break;
            case 'kkiapay':
                paymentDetailsContainer.style.display = 'block';
                paymentDetailsLabel.textContent = 'Numéro de téléphone';
                paymentDetails.placeholder = 'Entrez votre numéro (format: 01XXXXXXXX)';
                paymentDetails.pattern = '^01[0-9]{8}$';
                paymentDetails.maxLength = 10;
                break;
            default:
                paymentDetailsContainer.style.display = 'none';
        }
    });

    paymentDetails.addEventListener('input', function() {
        const value = this.value;
        let isValid = true;
        let errorMessage = '';
        switch(paymentMethod.value) {
            case 'card':
                if (!/^[0-9]{16}$/.test(value)) {
                    isValid = false;
                    errorMessage = 'Le numéro de carte doit contenir 16 chiffres';
                }
                break;
            case 'paypal':
                if (!/^[^\s@]+@[^\s@]+\\.[^\s@]+$/.test(value)) {
                    isValid = false;
                    errorMessage = 'Veuillez entrer une adresse email valide';
                }
                break;
            case 'kkiapay':
                if (!/^01[0-9]{8}$/.test(value)) {
                    isValid = false;
                    errorMessage = 'Le numéro doit commencer par 01 et contenir 10 chiffres';
                }
                break;
        }
        if (!isValid) {
            this.classList.add('is-invalid');
            paymentDetailsFeedback.textContent = errorMessage;
        } else {
            this.classList.remove('is-invalid');
            paymentDetailsFeedback.textContent = '';
        }
    });
// Gestion dynamique pour le retrait
const withdrawMethod = document.getElementById('withdrawMethod');
const withdrawDetailsContainer = document.getElementById('withdrawDetailsContainer');
const withdrawDetailsLabel = document.getElementById('withdrawDetailsLabel');
const withdrawDetails = document.getElementById('withdrawDetails');
const withdrawDetailsFeedback = document.getElementById('withdrawDetailsFeedback');

if (withdrawMethod) {
    withdrawMethod.addEventListener('change', function() {
        withdrawDetails.classList.remove('is-invalid');
        withdrawDetailsFeedback.textContent = '';
        withdrawDetails.value = '';
        withdrawDetails.type = 'text';
        withdrawDetails.removeAttribute('pattern');
        withdrawDetails.removeAttribute('maxlength');

        switch(this.value) {
            case 'card':
                withdrawDetailsContainer.style.display = 'block';
                withdrawDetailsLabel.textContent = 'Numéro de carte';
                withdrawDetails.placeholder = 'Entrez votre numéro de carte';
                withdrawDetails.pattern = '[0-9]{16}';
                withdrawDetails.maxLength = 16;
                break;
            case 'paypal':
                withdrawDetailsContainer.style.display = 'block';
                withdrawDetailsLabel.textContent = 'Email du compte PayPal';
                withdrawDetails.placeholder = 'Entrez votre email PayPal';
                withdrawDetails.type = 'email';
                break;
            case 'kkiapay':
                withdrawDetailsContainer.style.display = 'block';
                withdrawDetailsLabel.textContent = 'Numéro de téléphone';
                withdrawDetails.placeholder = 'Entrez votre numéro (format: 01XXXXXXXX)';
                withdrawDetails.pattern = '^01[0-9]{8}$';
                withdrawDetails.maxLength = 10;
                break;
            default:
                withdrawDetailsContainer.style.display = 'none';
        }
    });

    withdrawDetails.addEventListener('input', function() {
        const value = this.value;
        let isValid = true;
        let errorMessage = '';
        switch(withdrawMethod.value) {
            case 'card':
                if (!/^[0-9]{16}$/.test(value)) {
                    isValid = false;
                    errorMessage = 'Le numéro de carte doit contenir 16 chiffres';
                }
                break;
            case 'paypal':
                if (!/^[^\s@]+@[^\s@]+\\.[^\s@]+$/.test(value)) {
                    isValid = false;
                    errorMessage = 'Veuillez entrer une adresse email valide';
                }
                break;
            case 'kkiapay':
                if (!/^01[0-9]{8}$/.test(value)) {
                    isValid = false;
                    errorMessage = 'Le numéro doit commencer par 01 et contenir 10 chiffres';
                }
                break;
        }
        if (!isValid) {
            this.classList.add('is-invalid');
            withdrawDetailsFeedback.textContent = errorMessage;
        } else {
            this.classList.remove('is-invalid');
            withdrawDetailsFeedback.textContent = '';
        }
    });
}
});