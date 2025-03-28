<div id="chat-container" style="display: flex; flex-direction: column; height: 100vh; background: #121b22; color: #e9edef; border-radius: 10px; overflow: hidden;">
    <div style="background: #202c33; padding: 15px; display: flex; align-items: center; justify-content: space-between;">
        <h3 style="margin: 0; font-size: 1.2rem;">Dad</h3>
        <button style="background: none; border: none; color: #e9edef; font-size: 1.2rem; cursor: pointer;">...</button>
    </div>
    <div id="messages-container" style="flex: 1; overflow-y: auto; padding: 15px; background: #0b141a;">
        <!-- Messages will be dynamically loaded here -->
    </div>
    <form id="chat-form" enctype="multipart/form-data" style="display: flex; align-items: center; padding: 10px; background: #202c33; gap: 10px;">
        <label for="file-input" style="cursor: pointer; color: #8696a0;">
            <i class="fas fa-paperclip" style="font-size: 1.2rem;"></i>
        </label>
        <input type="file" id="file-input" accept="image/*,video/*,audio/*" hidden>
        <textarea id="message-input" placeholder="Écrivez un message..." rows="1" style="flex: 1; padding: 10px; border: none; border-radius: 10px; background: #2a3942; color: #e9edef; resize: none;"></textarea>
        <button type="button" id="emoji-button" style="background: none; border: none; font-size: 1.2rem; color: #8696a0; cursor: pointer;">😊</button>
        <button type="submit" style="background: #005c4b; color: #e9edef; border: none; padding: 10px 15px; border-radius: 10px; cursor: pointer;">Envoyer</button>
    </form>
</div>

<script>
    const messagesContainer = document.getElementById('messages-container');
    const chatForm = document.getElementById('chat-form');
    const messageInput = document.getElementById('message-input');
    const fileInput = document.getElementById('file-input');
    const emojiButton = document.getElementById('emoji-button');

    // WebSocket connection
    const socket = new WebSocket('ws://localhost:8080/chat');

    socket.onmessage = (event) => {
        const data = JSON.parse(event.data);
        const messageElement = document.createElement('div');
        messageElement.classList.add('message', data.sender === 'me' ? 'sent' : 'received');
        messageElement.innerHTML = `<strong>${data.sender}:</strong> ${data.message}`;

        if (data.file_path) {
            if (data.file_type === 'image') {
                messageElement.innerHTML += `<img src="${data.file_path}" alt="Image" style="max-width: 100%;">`;
            } else if (data.file_type === 'video') {
                messageElement.innerHTML += `<video controls style="max-width: 100%;"><source src="${data.file_path}" type="video/mp4"></video>`;
            } else if (data.file_type === 'audio') {
                messageElement.innerHTML += `<audio controls><source src="${data.file_path}" type="audio/mpeg"></audio>`;
            }
        }

        messagesContainer.appendChild(messageElement);
    };

    chatForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const message = messageInput.value;
        socket.send(JSON.stringify({ sender: 'me', message }));
        messageInput.value = '';
    });

    emojiButton.addEventListener('click', () => {
        messageInput.value += '😊';
    });
</script>