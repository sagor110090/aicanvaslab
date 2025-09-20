<template>
    <div class="flex h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
        <!-- Sidebar -->
        <ChatSidebar 
            :chats="chats" 
            :models="models"
            @new-chat="createNewChat"
            @select-chat="selectChat"
            :current-chat="currentChat"
            :user="user"
        />
        
        <!-- Main Chat Area -->
        <div class="flex-1 flex flex-col relative">
            <!-- Welcome Screen -->
            <div v-if="!currentChat" class="flex-1 flex items-center justify-center p-8">
                <div class="text-center max-w-4xl mx-auto">
                    <!-- Hero Section -->
                    <div class="mb-12">
                        <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-xl">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-3">
                            Welcome to AI Chat
                        </h1>
                        <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                            Select an AI model below to start a conversation
                        </p>
                    </div>

                    <!-- Model Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-w-5xl mx-auto">
                        <button
                            v-for="model in models"
                            :key="model.id"
                            @click="createNewChat(model.id)"
                            class="group p-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-400 transition-all duration-200 text-left hover:shadow-lg"
                        >
                            <div class="flex items-start justify-between mb-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span v-if="model.supports_images" class="inline-flex items-center gap-1 text-xs bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-2 py-0.5 rounded">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </span>
                                </div>
                            </div>
                            <h3 class="font-semibold text-lg mb-2 text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                {{ model.name }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                {{ model.description }}
                            </p>
                            <div class="flex items-center text-blue-600 dark:text-blue-400 text-sm font-medium group-hover:gap-2 transition-all">
                                <span>Start Chat</span>
                                <svg class="w-4 h-4 ml-1 opacity-0 group-hover:opacity-100 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                            </div>
                        </button>
                    </div>

                    <!-- Features Section -->
                    <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6 max-w-3xl mx-auto">
                        <div class="text-center">
                            <div class="w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded-xl flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <h4 class="font-medium text-gray-900 dark:text-white mb-1 text-sm">Lightning Fast</h4>
                            <p class="text-gray-600 dark:text-gray-400 text-xs">Instant AI responses</p>
                        </div>
                        <div class="text-center">
                            <div class="w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded-xl flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <h4 class="font-medium text-gray-900 dark:text-white mb-1 text-sm">Secure & Private</h4>
                            <p class="text-gray-600 dark:text-gray-400 text-xs">Protected conversations</p>
                        </div>
                        <div class="text-center">
                            <div class="w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded-xl flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <h4 class="font-medium text-gray-900 dark:text-white mb-1 text-sm">Multiple Models</h4>
                            <p class="text-gray-600 dark:text-gray-400 text-xs">Various AI capabilities</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Chat Interface -->
            <ChatInterface 
                v-else
                :chat="currentChat"
                :messages="messages"
                :system-prompts="props.systemPrompts"
                @send-message="sendMessage"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, provide } from 'vue';
import { router } from '@inertiajs/vue3';
import ChatSidebar from '@/Components/ChatSidebar.vue';
import ChatInterface from '@/Components/ChatInterface.vue';
import axios from 'axios';

const props = defineProps({
    chats: Array,
    models: Array,
    systemPrompts: Array,
    user: Object,
});

// Provide models and system prompts to child components
provide('availableModels', props.models);
provide('availableSystemPrompts', props.systemPrompts);

const currentChat = ref(null);
const messages = ref([]);

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
    if (!currentChat.value) return;
    
    try {
        const response = await axios.post(`/api/chats/${currentChat.value.uuid}/message`, {
            message,
            images
        });
        
        messages.value.push({
            role: 'user',
            content: message,
            images,
        });
        
        messages.value.push({
            role: 'assistant',
            content: response.data.response,
        });
    } catch (error) {
        console.error('Error sending message:', error);
    }
};

onMounted(() => {
    if (props.user) {
        axios.post('/api/chats/merge-anonymous').catch(() => {});
    }
});
</script>