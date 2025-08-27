<template>
    <div class="w-72 bg-gray-50 dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700 flex flex-col h-screen transition-colors duration-200">
        <!-- Header with Dark Mode Toggle -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Chats</h2>
                <!-- Dark Mode Toggle -->
                <button
                    @click="toggleDarkMode"
                    class="p-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors"
                    aria-label="Toggle dark mode"
                >
                    <svg v-if="!isDarkMode" class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>
                    <svg v-else class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </button>
            </div>
            
            <!-- New Chat Button -->
            <div class="relative" ref="modelSelectorRef">
                <button
                    @click.stop="showModelSelector = !showModelSelector"
                    class="w-full bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg px-4 py-2.5 flex items-center justify-center gap-2 transition-colors text-sm font-medium"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>New Chat</span>
                </button>
                
                <!-- Model Selector Dropdown -->
                <div v-if="showModelSelector" class="absolute z-20 mt-2 w-full bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden max-h-80 dropdown-enter">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 px-3 py-2 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        Select AI Model
                    </div>
                    <div class="overflow-y-auto max-h-64 py-1 model-dropdown">
                        <button
                            v-for="model in models"
                            :key="model.id"
                            @click="selectModel(model.id)"
                            class="w-full text-left px-3 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors border-l-2 border-transparent hover:border-blue-300 dark:hover:border-blue-600"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium truncate">{{ model.name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">{{ model.description }}</div>
                                </div>
                                <div class="flex-shrink-0 ml-2">
                                    <span v-if="model.supports_images" class="inline-flex items-center text-xs bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 px-1.5 py-0.5 rounded">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </span>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Search Bar -->
        <div class="p-3 border-b border-gray-200 dark:border-gray-700">
            <div class="relative">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search conversations..."
                    class="w-full pl-9 pr-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                />
                <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
        </div>
        
        <!-- Chat List -->
        <div class="flex-1 overflow-y-auto sidebar-scroll">
            <div class="p-2">
                <div v-if="filteredChats.length > 0" class="space-y-1">
                    <button
                        v-for="chat in filteredChats"
                        :key="chat.id"
                        @click="$emit('select-chat', chat)"
                        :class="[
                            'w-full text-left p-3 rounded-lg transition-all duration-200 group',
                            currentChat?.id === chat.id 
                                ? 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800' 
                                : 'hover:bg-gray-100 dark:hover:bg-gray-800'
                        ]"
                    >
                        <div class="flex items-start justify-between mb-1">
                            <div :class="[
                                'text-sm font-medium truncate flex-1 mr-2',
                                currentChat?.id === chat.id ? 'text-blue-900 dark:text-blue-300' : 'text-gray-900 dark:text-gray-100'
                            ]">
                                {{ chat.title || 'New Conversation' }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                {{ formatDate(chat.last_activity_at) }}
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div :class="[
                                'text-xs px-2 py-0.5 rounded',
                                currentChat?.id === chat.id 
                                    ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400' 
                                    : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
                            ]">
                                {{ chat.ai_model?.name }}
                            </div>
                        </div>
                    </button>
                </div>
                
                <!-- Empty state -->
                <div v-else class="text-center py-8">
                    <div class="w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">No conversations found</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Start a new chat to begin</p>
                </div>
            </div>
        </div>
        
        <!-- User Section (if logged in) -->
        <div v-if="user" class="p-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center">
                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ user.name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ user.email }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    chats: Array,
    models: Array,
    currentChat: Object,
    user: Object,
});

const emit = defineEmits(['new-chat', 'select-chat']);

const showModelSelector = ref(false);
const searchQuery = ref('');
const isDarkMode = ref(false);
const modelSelectorRef = ref(null);

// Handle click outside to close dropdown
const handleClickOutside = (event) => {
    if (modelSelectorRef.value && !modelSelectorRef.value.contains(event.target)) {
        showModelSelector.value = false;
    }
};

// Check for saved dark mode preference or system preference
onMounted(() => {
    // Sync with the current state from HTML
    isDarkMode.value = document.documentElement.classList.contains('dark');
    console.log('ChatSidebar mounted, dark mode:', isDarkMode.value);
    
    // Add click outside listener
    document.addEventListener('click', handleClickOutside);
});

const applyDarkMode = () => {
    console.log('Applying dark mode:', isDarkMode.value);
    if (isDarkMode.value) {
        document.documentElement.classList.add('dark');
        document.body.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
        document.body.classList.remove('dark');
    }
};

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

const toggleDarkMode = () => {
    console.log('Toggle dark mode clicked, current:', isDarkMode.value);
    isDarkMode.value = !isDarkMode.value;
    console.log('New dark mode value:', isDarkMode.value);
    
    // Save preference
    localStorage.setItem('darkMode', isDarkMode.value.toString());
    console.log('Saved to localStorage:', localStorage.getItem('darkMode'));
    
    // Apply to document
    applyDarkMode();
};

const selectModel = (modelId) => {
    showModelSelector.value = false;
    emit('new-chat', modelId);
};

const filteredChats = computed(() => {
    if (!searchQuery.value) return props.chats;
    
    const query = searchQuery.value.toLowerCase();
    return props.chats.filter(chat => {
        const title = (chat.title || 'New Conversation').toLowerCase();
        const modelName = (chat.ai_model?.name || '').toLowerCase();
        return title.includes(query) || modelName.includes(query);
    });
});

const formatDate = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
    
    if (diffDays === 0) {
        const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
        if (diffHours === 0) {
            const diffMinutes = Math.floor(diffMs / (1000 * 60));
            if (diffMinutes === 0) return 'Just now';
            return `${diffMinutes}m ago`;
        }
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    } else if (diffDays === 1) {
        return 'Yesterday';
    } else if (diffDays < 7) {
        return `${diffDays}d ago`;
    } else {
        return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
    }
};
</script>