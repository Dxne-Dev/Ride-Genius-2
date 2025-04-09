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

            const reader = new FileReader();
            reader.onload = (e) => {
                const fileData = {
                    name: file.name,
                    type: file.type,
                    size: file.size,
                    content: e.target.result.split(',')[1]
                };
                this.attachedFiles.push(fileData);
                this.displayAttachment(fileData);
            };
            reader.readAsDataURL(file);
        });
    }

    displayAttachment(file) {
        const isImage = file.type.startsWith('image/');
        const attachmentHtml = `
            <div class="attachment-item" data-name="${file.name}">
                ${isImage ? `<img src="data:${file.type};base64,${file.content}" alt="${file.name}">` : ''}
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
        const sendPromises = this.attachedFiles.map(file => {
            return new Promise((resolve, reject) => {
                this.socket.emit('uploadFile', {
                    type: 'uploadFile',
                    receiver_id: receiverId,
                    file_name: file.name,
                    file_type: file.type,
                    file_content: file.content,
                    message: message
                }, response => {
                    if (response.success) {
                        resolve(response);
                    } else {
                        reject(new Error(response.message || 'Erreur lors de l\'envoi du fichier'));
                    }
                });
            });
        });

        try {
            await Promise.all(sendPromises);
            this.clearAttachments();
            return true;
        } catch (error) {
            this.showNotification(error.message, 'error');
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

    showNotification(message, type) {
        // Utiliser la fonction de notification existante ou créer une nouvelle
        if (window.showNotification) {
            window.showNotification(message, type);
        } else {
            console.log(`${type}: ${message}`);
        }
    }
}

// Exporter la classe pour l'utiliser dans d'autres fichiers
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ChatFileHandler;
} 