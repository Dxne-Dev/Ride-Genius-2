// Gestion des fichiers dans le chat
class ChatFileHandler {
    constructor(options = {}) {
        this.maxFileSize = options.maxFileSize || 10 * 1024 * 1024; // 10MB par défaut
        this.attachedFiles = [];
        this.socket = options.socket;
        
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        // Gestionnaire pour le bouton d'attachement
        document.getElementById('attachButton').addEventListener('click', () => {
            document.getElementById('fileInput').click();
        });

        // Gestionnaire pour la sélection de fichiers
        document.getElementById('fileInput').addEventListener('change', (e) => {
            this.handleFiles(e.target.files);
        });

        // Gestionnaire pour la suppression de fichiers
        document.addEventListener('click', (e) => {
            if (e.target.closest('.remove-attachment')) {
                const attachmentItem = e.target.closest('.attachment-item');
                const fileName = attachmentItem.dataset.name;
                this.removeFile(fileName);
            }
        });

        // Support du drag & drop
        const dropZone = document.getElementById('chatMessages');
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('drag-over');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            this.handleFiles(e.dataTransfer.files);
        });
    }

    handleFiles(files) {
        Array.from(files).forEach(file => {
            if (file.size > this.maxFileSize) {
                this.showNotification(`Le fichier ${file.name} est trop volumineux (max ${this.formatFileSize(this.maxFileSize)})`, 'error');
                return;
            }

            // Vérifier le type de fichier
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'];
            if (!allowedTypes.includes(file.type)) {
                this.showNotification(`Type de fichier non supporté: ${file.type}`, 'error');
                return;
            }

            this.attachedFiles.push(file);
            this.displayAttachment(file);
        });
    }

    displayAttachment(file) {
        const isImage = file.type.startsWith('image/');
        const isVideo = file.type.startsWith('video/');
        const attachmentHtml = `
            <div class="attachment-item" data-name="${file.name}">
                ${isImage ? `<img src="${URL.createObjectURL(file)}" alt="${file.name}">` : ''}
                ${isVideo ? `<video src="${URL.createObjectURL(file)}" controls></video>` : ''}
                <span class="attachment-name">${file.name}</span>
                <span class="attachment-size">${this.formatFileSize(file.size)}</span>
                <button type="button" class="remove-attachment">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        document.getElementById('messageAttachments').insertAdjacentHTML('beforeend', attachmentHtml);
    }

    removeFile(fileName) {
        this.attachedFiles = this.attachedFiles.filter(f => f.name !== fileName);
        const attachmentItem = document.querySelector(`.attachment-item[data-name="${fileName}"]`);
        if (attachmentItem) {
            attachmentItem.remove();
        }
    }

    async sendFiles(receiverId, message = '') {
        if (this.attachedFiles.length === 0) return true;

        const formData = new FormData();
        formData.append('action', 'sendMessage');
        formData.append('receiver_id', receiverId);
        formData.append('message', message);
        
        this.attachedFiles.forEach((file, index) => {
            formData.append(`files[${index}]`, file);
        });

        try {
            const response = await fetch('message_api.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                // Émettre l'événement de message via le socket
                if (this.socket && this.socket.connected) {
                    this.socket.emit('sendMessage', {
                        receiver_id: receiverId,
                        message: message,
                        files: result.files || []
                    });
                }
                
                this.clearAttachments();
                return true;
            } else {
                this.showNotification(result.message || 'Erreur lors de l\'envoi des fichiers', 'error');
                return false;
            }
        } catch (error) {
            console.error('Erreur lors de l\'envoi des fichiers:', error);
            this.showNotification('Erreur lors de l\'envoi des fichiers', 'error');
            return false;
        }
    }

    clearAttachments() {
        this.attachedFiles = [];
        document.getElementById('messageAttachments').innerHTML = '';
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    showNotification(message, type = 'info') {
        // Utiliser SweetAlert2 pour les notifications
        Swal.fire({
            icon: type,
            title: type.charAt(0).toUpperCase() + type.slice(1),
            text: message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    }
}

// Exporter la classe pour l'utiliser dans d'autres fichiers
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ChatFileHandler;
} 