<template>
    <div class="flex h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
        <!-- Sidebar -->
        <ChatSidebar 
            :chats="chats" 
            :models="models"
            @new-chat="createNewChat"
            @select-chat="selectChat"
            :current-chat="chat"
            :user="user"
        />
        
        <!-- Main Chat Area -->
        <div class="flex-1 flex flex-col">
            <!-- Chat Interface -->
            <ChatInterface 
                :chat="chat"
                :initial-messages="chat.messages"
                :system-prompts="props.systemPrompts"
                @send-message="sendMessage"
            />
        </div>
    </div>
</template>

<script setup>
import { provide } from 'vue';
import { router } from '@inertiajs/vue3';
import ChatSidebar from '@/Components/ChatSidebar.vue';
import ChatInterface from '@/Components/ChatInterface.vue';
import axios from 'axios';

const props = defineProps({
    chat: Object,
    chats: Array,
    models: Array,
    systemPrompts: Array,
    user: Object,
});

// Provide models and system prompts to child components
provide('availableModels', props.models);
provide('availableSystemPrompts', props.systemPrompts);

const createNewChat = async (modelId) => {
    try {
        const response = await axios.post('/api/chats', {
            ai_model_id: modelId
        });
        
        const newChat = response.data.chat;
        router.visit(`/chat/${newChat.uuid}`);
    } catch (error) {
        console.error('Error creating chat:', error);
    }
};

const selectChat = (chat) => {
    router.visit(`/chat/${chat.uuid}`);
};

const sendMessage = async (message, images = []) => {
    // Message sending is handled in ChatInterface component
};
</script>