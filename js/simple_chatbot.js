// Simple Chatbot Integration
document.addEventListener('DOMContentLoaded', function() {
    // Create chatbot elements if they don't exist
    if (!document.querySelector('.chat-container')) {
        createChatbotUI();
    }
    
    const chatToggle = document.getElementById('chatToggle');
    const chatBox = document.getElementById('chatBox');
    const chatClose = document.getElementById('chatClose');
    const chatMessages = document.getElementById('chatMessages');
    const userInput = document.getElementById('userInput');
    const sendMessage = document.getElementById('sendMessage');
    const typingIndicator = document.getElementById('typingIndicator');
    
    // Get hall information from the page
    const hallName = document.querySelector('.card-title').textContent;
    const hallId = new URLSearchParams(window.location.search).get('hall_id');
    
    // Generate a unique session ID
    const sessionId = 'session_' + Math.random().toString(36).substring(2, 15);
    
    // Toggle chat box visibility
    chatToggle.addEventListener('click', function() {
        chatBox.style.display = 'flex';
        chatToggle.style.display = 'none';
        
        // Add welcome message if it's the first time opening
        if (chatMessages.children.length === 1) { // Only the typing indicator is present
            addBotMessage(`Hello! I'm your Hall Booking Assistant for ${hallName}. How can I help you today?`);
            addBotMessage("You can ask me about hall availability, booking process, or any other questions about hall booking.");
        }
    });
    
    // Close chat box
    chatClose.addEventListener('click', function() {
        chatBox.style.display = 'none';
        chatToggle.style.display = 'flex';
    });
    
    // Send message when button is clicked
    sendMessage.addEventListener('click', function() {
        sendUserMessage();
    });
    
    // Send message when Enter key is pressed
    userInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendUserMessage();
        }
    });
    
    // Function to send user message
    function sendUserMessage() {
        const message = userInput.value.trim();
        if (message) {
            addUserMessage(message);
            showTypingIndicator();
            sendToChatbotAPI(message);
            userInput.value = '';
        }
    }
    
    // Add a bot message to the chat
    function addBotMessage(message) {
        const messageElement = document.createElement('div');
        messageElement.classList.add('message', 'bot-message');
        messageElement.textContent = message;
        
        // Insert before typing indicator
        chatMessages.insertBefore(messageElement, typingIndicator);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Add a user message to the chat
    function addUserMessage(message) {
        const messageElement = document.createElement('div');
        messageElement.classList.add('message', 'user-message');
        messageElement.textContent = message;
        
        // Insert before typing indicator
        chatMessages.insertBefore(messageElement, typingIndicator);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Show typing indicator
    function showTypingIndicator() {
        typingIndicator.style.display = 'block';
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Hide typing indicator
    function hideTypingIndicator() {
        typingIndicator.style.display = 'none';
    }
    
    // Send message to chatbot API
    function sendToChatbotAPI(message) {
        // Get user information
        const userEmail = document.querySelector('input[name="organizer_email"]').value || '';
        const userDepartmentId = document.getElementById('selected_department_id').value || '';
        
        // Prepare data for API
        const data = {
            query: message,
            sessionId: sessionId,
            hallId: hallId,
            hallName: hallName,
            userEmail: userEmail,
            userDepartmentId: userDepartmentId
        };
        
        // Send request to API
        fetch('simple_chatbot_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            hideTypingIndicator();
            
            // Add bot response
            addBotMessage(data.response);
            
            // Handle actions
            if (data.action === 'fillForm' && data.formData) {
                fillBookingForm(data.formData);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            hideTypingIndicator();
            addBotMessage("Sorry, I'm having trouble connecting. Please try again later.");
        });
    }
    
    // Fill booking form with data from chatbot
    function fillBookingForm(formData) {
        // Map form data to form fields
        const fieldMap = {
            'organizer_name': 'input[name="organizer_name"]',
            'organizer_email': 'input[name="organizer_email"]',
            'organizer_contact': 'input[name="organizer_contact"]',
            'program_name': 'input[name="program_name"]',
            'program_purpose': 'textarea[name="program_purpose"]',
            'from_date': 'input[name="from_date"]',
            'end_date': 'input[name="end_date"]',
            'start_time': 'input[name="start_time"]',
            'end_time': 'input[name="end_time"]',
            'queries': 'textarea[name="queries"]'
        };
        
        // Fill each field if data is available
        for (const [key, selector] of Object.entries(fieldMap)) {
            const field = document.querySelector(selector);
            if (field && formData[key]) {
                field.value = formData[key];
            }
        }
        
        // Handle radio buttons for program type
        if (formData.program_type) {
            const radioButton = document.querySelector(`input[name="program_type"][value="${formData.program_type}"]`);
            if (radioButton) {
                radioButton.checked = true;
            }
        }
        
        // Highlight the form to draw attention
        const form = document.querySelector('form');
        form.classList.add('highlight-form');
        setTimeout(() => {
            form.classList.remove('highlight-form');
        }, 2000);
    }
    
    // Create chatbot UI
    function createChatbotUI() {
        // Create CSS
        const style = document.createElement('style');
        style.textContent = `
            .chat-container {
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 1000;
            }
            
            .chat-box {
                width: 350px;
                height: 450px;
                background-color: #fff;
                border-radius: 10px;
                box-shadow: 0 0 10px rgba(0,0,0,0.2);
                overflow: hidden;
                display: none;
                flex-direction: column;
            }
            
            .chat-header {
                background-color: #007bff;
                color: white;
                padding: 10px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .chat-header h5 {
                margin: 0;
            }
            
            .chat-close {
                cursor: pointer;
                background: none;
                border: none;
                color: white;
                font-size: 18px;
            }
            
            .chat-messages {
                flex: 1;
                padding: 10px;
                overflow-y: auto;
                display: flex;
                flex-direction: column;
            }
            
            .message {
                margin-bottom: 10px;
                padding: 8px 12px;
                border-radius: 15px;
                max-width: 80%;
            }
            
            .bot-message {
                background-color: #f1f1f1;
                align-self: flex-start;
            }
            
            .user-message {
                background-color: #007bff;
                color: white;
                align-self: flex-end;
                margin-left: auto;
            }
            
            .chat-input {
                display: flex;
                padding: 10px;
                border-top: 1px solid #ddd;
            }
            
            .chat-input input {
                flex: 1;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 20px;
                margin-right: 10px;
            }
            
            .chat-input button {
                background-color: #007bff;
                color: white;
                border: none;
                border-radius: 20px;
                padding: 8px 15px;
                cursor: pointer;
            }
            
            .chat-toggle {
                width: 60px;
                height: 60px;
                background-color: #007bff;
                color: white;
                border: none;
                border-radius: 50%;
                display: flex;
                justify-content: center;
                align-items: center;
                cursor: pointer;
                box-shadow: 0 0 10px rgba(0,0,0,0.2);
                font-size: 24px;
            }
            
            .typing-indicator {
                display: none;
                align-self: flex-start;
                background-color: #f1f1f1;
                padding: 8px 12px;
                border-radius: 15px;
                margin-bottom: 10px;
            }
            
            .typing-indicator span {
                display: inline-block;
                width: 8px;
                height: 8px;
                background-color: #666;
                border-radius: 50%;
                margin-right: 5px;
                animation: typing 1s infinite;
            }
            
            .typing-indicator span:nth-child(2) {
                animation-delay: 0.2s;
            }
            
            .typing-indicator span:nth-child(3) {
                animation-delay: 0.4s;
                margin-right: 0;
            }
            
            @keyframes typing {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-5px); }
            }
            
            .highlight-form {
                animation: highlight 2s;
            }
            
            @keyframes highlight {
                0%, 100% { box-shadow: none; }
                50% { box-shadow: 0 0 20px rgba(0, 123, 255, 0.5); }
            }
        `;
        document.head.appendChild(style);
        
        // Create HTML
        const chatHTML = `
            <div class="chat-container">
                <div class="chat-box" id="chatBox">
                    <div class="chat-header">
                        <h5>Hall Booking Assistant</h5>
                        <button class="chat-close" id="chatClose">×</button>
                    </div>
                    <div class="chat-messages" id="chatMessages">
                        <div class="typing-indicator" id="typingIndicator">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                    <div class="chat-input">
                        <input type="text" id="userInput" placeholder="Type your question here...">
                        <button id="sendMessage">Send</button>
                    </div>
                </div>
                <button class="chat-toggle" id="chatToggle">💬</button>
            </div>
        `;
        
        // Append to body
        const chatContainer = document.createElement('div');
        chatContainer.innerHTML = chatHTML;
        document.body.appendChild(chatContainer);
    }
});