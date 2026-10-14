/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import "./echo";

window.initializeChatRealtime = (wire, initialConversationId, userId) => {
    let activeConversationId = null;
    let typingTimeout = null;

    const showTypingIndicator = (name) => {
        const indicator = document.getElementById("typing-indicator");

        if (!indicator) {
            return;
        }

        indicator.textContent = `● ${name} is typing…`;
        indicator.classList.remove("hidden");
        indicator.classList.add("flex", "animate-pulse");

        clearTimeout(typingTimeout);

        typingTimeout = setTimeout(() => {
            indicator.textContent = "";
            indicator.classList.remove("flex", "animate-pulse");
            indicator.classList.add("hidden");
            typingTimeout = null;
        }, 2000);
    };

    const subscribeToConversation = (conversationId) => {
        clearTimeout(typingTimeout);
        typingTimeout = null;

        const indicator = document.getElementById("typing-indicator");

        if (indicator) {
            indicator.textContent = "";
            indicator.classList.remove("flex", "animate-pulse");
            indicator.classList.add("hidden");
        }

        if (activeConversationId !== null) {
            window.Echo.leave(`conversations.${activeConversationId}`);
        }

        activeConversationId = conversationId ? Number(conversationId) : null;

        if (activeConversationId === null) {
            return;
        }

        window.Echo.private(`conversations.${activeConversationId}`)
            .listen(".MessageSent", (event) => {
                clearTimeout(typingTimeout);

                const indicator = document.getElementById("typing-indicator");

                if (indicator) {
                    indicator.textContent = "";
                    indicator.classList.remove("flex", "animate-pulse");
                    indicator.classList.add("hidden");
                }

                wire.call("refreshMessages", event);
            })
            .listen(".MessageUpdated", (event) => {
                wire.call("refreshMessages", event);
            })
            .listen(".UserTyping", (event) => {
                if (Number(event.user.id) === Number(userId)) {
                    return;
                }

                showTypingIndicator(event.user.name);
            });
    };

    subscribeToConversation(initialConversationId);

    wire.$on("active-conversation-changed", (event) => {
        subscribeToConversation(event.conversationId);
    });

    const userChannel = window.Echo.private(`App.Models.User.${userId}`);

    userChannel.listen(".ConversationDeleted", (event) => {
        const deletedConversationId = Number(event.conversationId);

        if (activeConversationId === deletedConversationId) {
            window.location.reload();
            return;
        }

        wire.call("refreshConversations");
    });

    userChannel.listen(".MessageSent", (event) => {
        const messageConversationId = Number(event.message?.conversation_id);

        if (messageConversationId === activeConversationId) {
            return;
        }

        wire.call("refreshConversations");
    });
};
