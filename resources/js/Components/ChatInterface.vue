<template>
    <div class="flex-1 flex flex-col h-full bg-white dark:bg-gray-900 transition-colors duration-200">
        <!-- Chat Header with Tabs -->
        <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between px-4 sm:px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <div class="absolute -bottom-1 -right-1 w-3 h-3 bg-green-500 rounded-full border-2 border-white dark:border-gray-800"></div>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ currentChat.ai_model?.name }}</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ currentChat.title || 'New Conversation' }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span v-if="currentChat.ai_model?.supports_images" class="inline-flex items-center gap-1 text-xs bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-2.5 py-1 rounded-lg font-medium">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span>Vision</span>
                    </span>
                </div>
            </div>
            
            <!-- Tabs -->
            <div class="px-4 sm:px-6">
                <div class="flex space-x-8 border-b border-gray-200 dark:border-gray-700">
                    <button
                        @click="activeTab = 'chat'"
                        :class="[
                            'py-2 px-1 border-b-2 font-medium text-sm transition-colors',
                            activeTab === 'chat'
                                ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                        ]"
                    >
                        Chat
                    </button>
                    <button
                        @click="fetchNews()"
                        :class="[
                            'py-2 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2',
                            activeTab === 'news'
                                ? 'border-green-500 text-green-600 dark:text-green-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                        ]"
                        title="Fetch latest crypto news"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                        </svg>
                        News
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Chat Messages (shown when activeTab is 'chat') -->
        <div v-if="activeTab === 'chat'" class="flex-1 overflow-y-auto bg-gray-50 dark:bg-gray-900/50 chat-messages" ref="messagesContainer">
            <div class="py-4 px-4 sm:px-6 lg:px-8">
                <div class="space-y-4">
                    <div v-for="(message, index) in messages" :key="index">
                        <!-- User Message -->
                        <div v-if="message.role === 'user'" class="flex justify-end">
                            <div class="max-w-[85%] sm:max-w-[75%] lg:max-w-[60%]">
                                <div class="flex items-end gap-2 justify-end">
                                    <div class="bg-blue-600 dark:bg-blue-500 text-white rounded-2xl rounded-br px-4 py-2.5 shadow-sm">
                                        <div class="whitespace-pre-wrap leading-relaxed break-words text-sm">{{ message.content }}</div>
                                        
                                        <!-- Display Images -->
                                        <div v-if="message.images && message.images.length > 0" class="mt-3 grid grid-cols-2 gap-2">
                                            <img 
                                                v-for="(image, imgIndex) in message.images" 
                                                :key="imgIndex"
                                                :src="image" 
                                                class="rounded-lg max-w-full"
                                                alt="Uploaded image"
                                            />
                                        </div>
                                    </div>
                                    <div class="w-7 h-7 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Assistant Message -->
                        <div v-else class="flex justify-start">
                            <div class="max-w-[85%] sm:max-w-[75%] lg:max-w-[70%]">
                                <div class="flex items-start gap-2">
                                    <div class="w-7 h-7 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-full flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                    </div>
                                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl rounded-tl px-4 py-2.5 shadow-sm">
                                        <div 
                                            class="prose prose-sm dark:prose-invert max-w-none leading-relaxed text-gray-800 dark:text-gray-200 break-words text-sm"
                                            v-html="renderMarkdown(message.content)"
                                        ></div>
                                        <!-- Streaming indicator -->
                                        <div v-if="message.streaming" class="flex items-center gap-1 mt-2">
                                            <div class="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 0ms;"></div>
                                            <div class="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 150ms;"></div>
                                            <div class="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 300ms;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- News Content (shown when activeTab is 'news') -->
        <div v-else-if="activeTab === 'news'" class="flex-1 overflow-y-auto bg-gray-50 dark:bg-gray-900/50">
            <div class="py-4 px-4 sm:px-6 lg:px-8">
                <!-- News Header -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Latest Crypto News</h3>
                        <div class="flex items-center gap-2">
                            <select
                                v-model="selectedNewsSource"
                                @change="fetchNews()"
                                class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-1.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="">All Sources</option>
                                <option v-for="source in newsSources" :key="source" :value="source">{{ source }}</option>
                            </select>
                            <button
                                @click="fetchNews()"
                                :disabled="isLoadingNews"
                                class="px-3 py-1.5 bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white rounded-lg text-sm font-medium flex items-center gap-2 transition-colors"
                            >
                                <svg v-if="!isLoadingNews" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <svg v-else class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ isLoadingNews ? 'Loading...' : 'Refresh' }}</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- System Prompt Info -->
                    <div v-if="currentChat.system_prompt" class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-3 mb-4">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-sm text-purple-800 dark:text-purple-200">
                                Using system prompt: <strong>{{ currentChat.system_prompt.name }}</strong>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- News Loading -->
                <div v-if="isLoadingNews" class="flex items-center justify-center py-12">
                    <div class="text-center">
                        <svg class="w-8 h-8 animate-spin text-green-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-gray-600 dark:text-gray-400">Fetching latest crypto news...</p>
                    </div>
                </div>

                <!-- News Error -->
                <div v-else-if="newsError" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <h4 class="text-red-800 dark:text-red-200 font-medium">Error Loading News</h4>
                            <p class="text-red-600 dark:text-red-300 text-sm mt-1">{{ newsError }}</p>
                            <button @click="fetchNews()" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200 text-sm font-medium mt-2">
                                Try Again
                            </button>
                        </div>
                    </div>
                </div>

                <!-- News List -->
                <div v-else-if="newsItems.length > 0" class="space-y-4">
                    <div
                        v-for="(article, index) in newsItems"
                        :key="index"
                        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow"
                    >
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex-1">
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">
                                            <a :href="article.url" target="_blank" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                                {{ article.title }}
                                            </a>
                                        </h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ article.description }}</p>
                                        <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                            <span class="inline-flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                </svg>
                                                {{ article.source }}
                                            </span>
                                            <span class="inline-flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                {{ formatDate(article.published_at) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 flex items-center gap-2">
                                        <button
                                            @click="analyzeNews(article)"
                                            class="p-2 text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors"
                                            title="Ask AI to analyze this news"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                            </svg>
                                        </button>
                                        <a
                                            :href="article.url"
                                            target="_blank"
                                            class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors"
                                            title="Read full article"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- No News -->
                <div v-else-if="!isLoadingNews && newsItems.length === 0" class="text-center py-12">
                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                    </svg>
                    <p class="text-gray-600 dark:text-gray-400">No news articles available.</p>
                    <button @click="fetchNews()" class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 text-sm font-medium mt-2">
                        Fetch News
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Input Area -->
        <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3">
            <div class="max-w-3xl mx-auto">
                <!-- Image Preview -->
                <div v-if="selectedImages.length > 0" class="mb-3 flex gap-2 flex-wrap">
                    <div v-for="(image, index) in selectedImages" :key="index" class="relative group">
                        <img :src="image.preview" class="h-16 w-16 object-cover rounded-lg border border-gray-200 dark:border-gray-600" />
                        <button
                            @click="removeImage(index)"
                            class="absolute -top-1 -right-1 bg-red-500 hover:bg-red-600 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-all duration-200"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Input Container -->
                <div class="relative bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 focus-within:border-blue-500 dark:focus-within:border-blue-400 transition-colors">
                    <!-- Message Input -->
                    <textarea
                        v-model="messageInput"
                        @keydown.enter.prevent="handleEnterKey"
                        :disabled="isLoading"
                        rows="3"
                        placeholder="Type your message..."
                        class="w-full px-4 py-3 bg-transparent resize-none focus:outline-none text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 text-sm"
                    ></textarea>
                    
                    <!-- Bottom Toolbar -->
                    <div class="flex items-center justify-between px-3 pb-3">
                        <!-- Left Side Controls -->
                        <div class="flex items-center gap-2">
                            <!-- System Prompt Switcher -->
                            <div class="relative" ref="systemPromptDropdownRef">
                                <button
                                    @click.stop="showSystemPromptSwitcher = !showSystemPromptSwitcher"
                                    class="flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <span>{{ currentChat.system_prompt?.name || 'System Prompt' }}</span>
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                
                                <!-- System Prompt Dropdown -->
                                <div v-if="showSystemPromptSwitcher" class="absolute bottom-full left-0 mb-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden z-10 max-h-64 dropdown-enter">
                                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 px-4 py-2 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                                        Select System Prompt
                                    </div>
                                    <div class="overflow-y-auto max-h-56 py-1 system-prompt-dropdown">
                                        <button
                                            v-for="prompt in availableSystemPrompts"
                                            :key="prompt.id"
                                            @click="switchSystemPrompt(prompt)"
                                            :class="[
                                                'w-full text-left px-3 py-2.5 transition-colors border-l-2',
                                                prompt.id === currentChat.system_prompt?.id 
                                                    ? 'bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 border-purple-500' 
                                                    : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 border-transparent hover:border-gray-300 dark:hover:border-gray-600'
                                            ]"
                                        >
                                            <div class="flex items-center justify-between">
                                                <div class="flex-1 min-w-0">
                                                    <div class="font-medium truncate">{{ prompt.name }}</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">{{ prompt.description }}</div>
                                                </div>
                                                <div class="flex-shrink-0 ml-2">
                                                    <svg v-if="prompt.id === currentChat.system_prompt?.id" class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                        </button>
                                        <button
                                            @click="clearSystemPrompt"
                                            class="w-full text-left px-3 py-2.5 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 border-transparent hover:border-gray-300 dark:hover:border-gray-600"
                                        >
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                                <span>Clear System Prompt</span>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Model Switcher -->
                            <div class="relative" ref="modelDropdownRef">
                                <button
                                    @click.stop="showModelSwitcher = !showModelSwitcher"
                                    class="flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                                    </svg>
                                    <span>{{ currentChat.ai_model?.name || 'Select Model' }}</span>
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                
                                <!-- Model Dropdown -->
                                <div v-if="showModelSwitcher" class="absolute bottom-full left-0 mb-2 w-64 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden z-10 max-h-64 dropdown-enter">
                                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 px-4 py-2 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                                        Switch Model
                                    </div>
                                    <div class="overflow-y-auto max-h-56 py-1 model-dropdown">
                                        <button
                                            v-for="model in availableModels"
                                            :key="model.id"
                                            @click="switchModel(model)"
                                            :class="[
                                                'w-full text-left px-3 py-2.5 transition-colors border-l-2',
                                                model.id === currentChat.ai_model?.id 
                                                    ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border-blue-500' 
                                                    : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 border-transparent hover:border-gray-300 dark:hover:border-gray-600'
                                            ]"
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
                                                    <svg v-if="model.id === currentChat.ai_model?.id" class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Image Upload Button -->
                            <button
                                v-if="currentChat.ai_model?.supports_images"
                                @click="$refs.imageInput.click()"
                                class="p-1.5 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                :disabled="isLoading"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                            <input
                                ref="imageInput"
                                type="file"
                                accept="image/*"
                                multiple
                                @change="handleImageSelect"
                                class="hidden"
                            />
                        </div>
                        
                        <!-- Send Button -->
                        <button
                            @click="sendMessage"
                            :disabled="isLoading || !messageInput.trim()"
                            class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm font-medium flex items-center gap-2"
                        >
                            <span v-if="!isLoading">Send</span>
                            <span v-else>Sending...</span>
                            <svg v-if="!isLoading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                            <svg v-else class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick, watch, inject } from 'vue';
import axios from 'axios';
import { marked } from 'marked';
import hljs from 'highlight.js';

const props = defineProps({
    chat: Object,
    initialMessages: Array,
    systemPrompts: Array,
});

const emit = defineEmits(['send-message', 'switch-model']);

const messages = ref(props.initialMessages || []);
const messageInput = ref('');
const isLoading = ref(false);
const selectedImages = ref([]);
const messagesContainer = ref(null);
const showModelSwitcher = ref(false);
const showSystemPromptSwitcher = ref(false);
const availableModels = inject('availableModels', []);
const availableSystemPrompts = ref(props.systemPrompts || []);
const modelDropdownRef = ref(null);
const systemPromptDropdownRef = ref(null);
const currentChat = reactive({ ...props.chat });

// News functionality
const activeTab = ref('chat');
const isLoadingNews = ref(false);
const newsError = ref('');
const newsItems = ref([]);
const selectedNewsSource = ref('');
const newsSources = ref([
    'CoinDesk',
    'CoinTelegraph',
    'NewsBTC',
]);

// Check if user has selected a system prompt
const hasSelectedSystemPrompt = computed(() => {
    return currentChat.system_prompt && currentChat.system_prompt.id;
});

// Fetch news 
const fetchNews = async () => {
    // Switch to news tab when fetching news
    activeTab.value = 'news';
    isLoadingNews.value = true;
    newsError.value = '';

    try {
        const params = {
            limit: 20,
            ...(selectedNewsSource.value && { source: selectedNewsSource.value })
        };

        const response = await axios.get('/api/news', { params });
        newsItems.value = response.data.news || [];
        
        if (newsItems.value.length === 0) {
            newsError.value = 'No news articles found. Please try again later.';
        }
    } catch (error) {
        console.error('Error fetching news:', error);
        let errorMessage = 'Failed to fetch news. Please try again later.';
        
        if (error.response?.status === 429) {
            errorMessage = 'Too many requests. Please wait a moment and try again.';
        } else if (error.response?.status >= 500) {
            errorMessage = 'Server error. Please try again later.';
        } else if (error.code === 'NETWORK_ERROR') {
            errorMessage = 'Network error. Please check your connection.';
        }
        
        newsError.value = errorMessage;
        newsItems.value = [];
    } finally {
        isLoadingNews.value = false;
    }
};

// Format date for display
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Refresh news manually
const refreshNews = () => {
    fetchNews();
};

// Configure marked for better markdown rendering
marked.setOptions({
    highlight: function(code, lang) {
        if (lang && hljs.getLanguage(lang)) {
            try {
                return hljs.highlight(code, { language: lang }).value;
            } catch (err) {
                console.warn('Highlight.js error:', err);
            }
        }
        return hljs.highlightAuto(code).value;
    },
    breaks: true,
    gfm: true,
});

// Render markdown to HTML
const renderMarkdown = (content) => {
    if (!content) return '';
    
    try {
        return marked.parse(content);
    } catch (error) {
        console.error('Markdown parsing error:', error);
        // Fallback to plain text with basic formatting
        return content
            .replace(/\n/g, '<br>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code>$1</code>');
    }
};

// Analyze news article with AI
const analyzeNews = (article) => {
    // Switch to chat tab
    activeTab.value = 'chat';
    
    // Create analysis template and fill the input field (let user send manually)
    const analysisTemplate = `Please analyze this crypto news:

Title: ${article.title}
Source: ${article.source}

Key points: ${article.description}

What are the trading implications?`;

    // Set the question in input field but don't auto-send
    messageInput.value = analysisTemplate;
    
    // Focus the input field
    nextTick(() => {
        const textarea = document.querySelector('textarea[placeholder="Type your message..."]');
        if (textarea) {
            textarea.focus();
            textarea.setSelectionRange(textarea.value.length, textarea.value.length);
        }
    });
};

const sendMessage = async () => {
    if (!messageInput.value.trim() || isLoading.value) return;
    
    const message = messageInput.value;
    const images = selectedImages.value.map(img => img.dataUrl);
    
    // Add user message immediately
    messages.value.push({
        role: 'user',
        content: message,
        images: images,
    });
    
    // Add empty assistant message for streaming
    const assistantMessageIndex = messages.value.length;
    messages.value.push({
        role: 'assistant',
        content: '',
        streaming: true,
    });
    
    messageInput.value = '';
    selectedImages.value = [];
    isLoading.value = true;
    
    await nextTick();
    scrollToBottom();
    
    try {
        const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        // Try streaming first
        try {
            const params = new URLSearchParams({
                message: message,
                images: JSON.stringify(images),
                _token: csrfToken
            });
            
            const eventSource = new EventSource(`/api/chats/${currentChat.uuid}/stream?${params.toString()}`);
            
            eventSource.onmessage = (event) => {
                const data = event.data.trim();
                
                if (data === '[DONE]') {
                    eventSource.close();
                    messages.value[assistantMessageIndex].streaming = false;
                    isLoading.value = false;
                    scrollToBottom();
                    return;
                }
                
                // Skip empty data or control messages
                if (!data) {
                    return;
                }
                
                try {
                    // Parse as JSON
                    const parsed = JSON.parse(data);
                    
                    // Handle different message types
                    if (parsed.type === 'start') {
                        console.log('Streaming started');
                        return;
                    }
                    
                    if (parsed.type === 'error' || parsed.error) {
                        messages.value[assistantMessageIndex].content = parsed.error || 'Streaming error occurred';
                        messages.value[assistantMessageIndex].streaming = false;
                        eventSource.close();
                        isLoading.value = false;
                        scrollToBottom();
                        return;
                    }
                    
                    // Handle content chunks
                    if (parsed.content) {
                        messages.value[assistantMessageIndex].content += parsed.content;
                        scrollToBottom();
                    }
                    
                } catch (parseError) {
                    console.warn('Failed to parse SSE data:', parseError, 'Raw data:', data);
                    
                    // If parsing fails and it looks like raw content, add it
                    if (data && !data.startsWith('{"type":') && data.length > 0) {
                        messages.value[assistantMessageIndex].content += data;
                        scrollToBottom();
                    }
                }
            };
            
            eventSource.onerror = async (error) => {
                console.warn('SSE error, falling back to regular request:', error);
                eventSource.close();
                
                // Fallback to regular request
                await fallbackToRegularRequest(message, images, assistantMessageIndex);
            };
            
            // Set timeout for streaming
            setTimeout(() => {
                if (eventSource.readyState !== EventSource.CLOSED) {
                    eventSource.close();
                    fallbackToRegularRequest(message, images, assistantMessageIndex);
                }
            }, 120000); // 2 minutes timeout
            
        } catch (sseError) {
            console.warn('SSE not supported, using regular request:', sseError);
            await fallbackToRegularRequest(message, images, assistantMessageIndex);
        }
        
    } catch (error) {
        console.error('Error sending message:', error);
        isLoading.value = false;
        messages.value[assistantMessageIndex].content = 'Sorry, there was an error processing your request. Please try again.';
        messages.value[assistantMessageIndex].streaming = false;
        scrollToBottom();
    }
};

const fallbackToRegularRequest = async (message, images, assistantMessageIndex) => {
    try {
        const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        const response = await fetch(`/api/chats/${currentChat.uuid}/message`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                message,
                images,
            }),
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (data.error) {
            messages.value[assistantMessageIndex].content = data.error;
        } else {
            messages.value[assistantMessageIndex].content = data.response;
        }
        
    } catch (error) {
        console.error('Error in fallback request:', error);
        let errorMessage = 'Sorry, there was an error processing your request. Please try again.';
        
        if (error.name === 'AbortError') {
            errorMessage = 'Request timed out. Please try again or switch to a different model.';
        } else if (error.message.includes('network')) {
            errorMessage = 'Connection error. Please check your internet connection and try again.';
        }
        
        messages.value[assistantMessageIndex].content = errorMessage;
    } finally {
        messages.value[assistantMessageIndex].streaming = false;
        isLoading.value = false;
        scrollToBottom();
    }
};

const handleImageSelect = (event) => {
    const files = Array.from(event.target.files);
    
    files.forEach(file => {
        const reader = new FileReader();
        reader.onload = (e) => {
            selectedImages.value.push({
                file,
                preview: e.target.result,
                dataUrl: e.target.result,
            });
        };
        reader.readAsDataURL(file);
    });
};

const removeImage = (index) => {
    selectedImages.value.splice(index, 1);
};

const scrollToBottom = () => {
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

const handleEnterKey = (event) => {
    if (!event.shiftKey) {
        sendMessage();
    }
};

const switchSystemPrompt = async (prompt) => {
    showSystemPromptSwitcher.value = false;
    
    try {
        const response = await fetch(`/api/chats/${currentChat.uuid}/system-prompt`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            },
            body: JSON.stringify({
                system_prompt_id: prompt.id
            }),
        });

        if (!response.ok) {
            throw new Error('Failed to update system prompt');
        }

        const data = await response.json();
        
        // Update the local chat object with the new system prompt
        Object.assign(currentChat, data.chat);
    } catch (error) {
        console.error('Error switching system prompt:', error);
        alert('Failed to switch system prompt. Please try again.');
    }
};

const clearSystemPrompt = async () => {
    showSystemPromptSwitcher.value = false;
    
    try {
        const response = await fetch(`/api/chats/${currentChat.uuid}/system-prompt`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            },
            body: JSON.stringify({
                system_prompt_id: null
            }),
        });

        if (!response.ok) {
            throw new Error('Failed to clear system prompt');
        }

        const data = await response.json();
        
        // Update the local chat object
        Object.assign(currentChat, data.chat);
    } catch (error) {
        console.error('Error clearing system prompt:', error);
        alert('Failed to clear system prompt. Please try again.');
    }
};

const switchModel = async (model) => {
    showModelSwitcher.value = false;
    
    try {
        const response = await fetch(`/api/chats/${currentChat.uuid}/model`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            },
            body: JSON.stringify({
                ai_model_id: model.id
            }),
        });

        if (!response.ok) {
            throw new Error('Failed to update model');
        }

        const data = await response.json();
        
        // Update the local chat object with the new model
        Object.assign(currentChat, data.chat);
        
        // Emit event for parent component if needed
        emit('switch-model', model);
    } catch (error) {
        console.error('Error switching model:', error);
        alert('Failed to switch model. Please try again.');
    }
};

// Handle click outside to close dropdowns
const handleClickOutside = (event) => {
    if (modelDropdownRef.value && !modelDropdownRef.value.contains(event.target)) {
        showModelSwitcher.value = false;
    }
    if (systemPromptDropdownRef.value && !systemPromptDropdownRef.value.contains(event.target)) {
        showSystemPromptSwitcher.value = false;
    }
};

watch(() => props.initialMessages, (newMessages) => {
    messages.value = newMessages || [];
    nextTick(() => scrollToBottom());
}, { immediate: true });

watch(() => props.chat, (newChat) => {
    if (newChat) {
        Object.assign(currentChat, newChat);
    }
}, { immediate: true });

watch(() => props.systemPrompts, (newPrompts) => {
    availableSystemPrompts.value = newPrompts || [];
}, { immediate: true });

onMounted(() => {
    scrollToBottom();
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>