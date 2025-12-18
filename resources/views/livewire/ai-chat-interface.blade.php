@php
    use Carbon\Carbon;
@endphp

<div class="fi-ai-chat flex flex-col" wire:key="{{ $identifier }}" style="height: 600px">
    {{-- Messages Container --}}
    <div class="fi-ai-chat-messages flex-1 overflow-y-auto">
        @forelse ($messages as $index => $message)
            @if ($message['role'] === 'user')
                {{-- User Message: Bubble Style --}}
                <div
                    wire:key="message-{{ $index }}"
                    class="fi-ai-chat-message fi-ai-chat-message-user group relative w-full border-b border-gray-100 bg-gray-50/50 px-6 py-4 transition-colors duration-200 hover:bg-gray-100/50 dark:border-gray-800 dark:bg-gray-900/50 dark:hover:bg-gray-900/70"
                >
                    {{-- Delete Button --}}
                    <div
                        class="fi-ai-chat-message-delete-btn absolute top-2 right-2 opacity-0 transition-opacity group-hover:opacity-100"
                    >
                        <x-filament::icon-button
                            wire:click="deleteMessage({{ $index }})"
                            icon="heroicon-o-x-mark"
                            color="gray"
                            size="sm"
                            label="Delete message"
                        />
                    </div>

                    <div class="flex justify-end">
                        <div class="max-w-[85%]">
                            {{-- User Message Bubble --}}
                            <div
                                class="fi-ai-chat-message-content bg-primary-600 dark:bg-primary-500 rounded-lg px-4 py-3 shadow-sm"
                            >
                                <p class="m-0 text-sm leading-relaxed break-words whitespace-pre-wrap text-white">
                                    {{ $message['content'] }}
                                </p>
                            </div>

                            {{-- Metadata Below --}}
                            <div
                                class="fi-ai-chat-message-meta mt-2 flex items-center justify-end gap-2 text-xs text-gray-500 dark:text-gray-400"
                            >
                                <span>{{ Carbon::parse($message['timestamp'])->diffForHumans() }}</span>
                                <span class="text-gray-400 dark:text-gray-600">·</span>
                                <span class="font-medium">You</span>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                {{-- Agent Message: Full Width, Clean --}}
                <div
                    wire:key="message-{{ $index }}"
                    class="fi-ai-chat-message fi-ai-chat-message-assistant group relative w-full border-b border-gray-100 px-6 py-4 transition-colors duration-200 hover:bg-gray-50/50 dark:border-gray-800 dark:hover:bg-white/5"
                >
                    {{-- Delete Button --}}
                    <div
                        class="fi-ai-chat-message-delete-btn absolute top-2 right-2 opacity-0 transition-opacity group-hover:opacity-100"
                    >
                        <x-filament::icon-button
                            wire:click="deleteMessage({{ $index }})"
                            icon="heroicon-o-x-mark"
                            color="gray"
                            size="sm"
                            label="Delete message"
                        />
                    </div>

                    {{-- Message Content (Full Width) --}}
                    <div class="fi-ai-chat-message-content prose prose-sm dark:prose-invert max-w-none">
                        <p class="m-0 text-sm leading-relaxed break-words text-gray-700 dark:text-gray-300">
                            {{ $message['content'] }}
                        </p>
                    </div>

                    {{-- Metadata Below --}}
                    <div
                        class="fi-ai-chat-message-meta mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400"
                    >
                        <span class="font-medium">AI Assistant</span>
                        <span class="text-gray-400 dark:text-gray-600">·</span>
                        <span>{{ Carbon::parse($message['timestamp'])->diffForHumans() }}</span>
                    </div>
                </div>
            @endif
        @empty
            {{-- Empty State --}}
            <div class="fi-ai-chat-empty flex h-full items-center justify-center text-center">
                <div class="max-w-md space-y-3">
                    <x-filament::icon icon="heroicon-o-sparkles" class="mx-auto h-12 w-12 text-gray-400" />
                    <div>
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">Refine with AI</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Start a conversation to perfect your content
                        </p>
                    </div>
                </div>
            </div>
        @endforelse

        {{-- Streaming Message (Static Target) --}}
        @if ($generating)
            <div
                class="fi-ai-chat-streaming group w-full border-b border-gray-100 px-6 py-4 transition-colors duration-200 hover:bg-gray-50/50 dark:border-gray-800 dark:hover:bg-white/5"
            >
                {{-- Streaming Content (Full Width) --}}
                <div class="fi-ai-chat-message-content prose prose-sm dark:prose-invert max-w-none">
                    <p class="m-0 text-sm leading-relaxed break-words text-gray-700 dark:text-gray-300">
                        <span wire:stream="ai-streaming" class="inline">{{ $streamingContent }}</span>
                        <span
                            class="fi-ai-chat-cursor bg-primary-600 ml-0.5 inline-block h-4 w-1.5 animate-pulse align-bottom"
                        ></span>
                    </p>
                </div>

                {{-- Metadata Below --}}
                <div
                    class="fi-ai-chat-message-meta mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400"
                >
                    <span class="font-medium">AI Assistant</span>
                    <span class="text-gray-400 dark:text-gray-600">·</span>
                    <span class="animate-pulse">responding now</span>
                </div>
            </div>
        @endif
    </div>

    {{-- Error Message --}}
    @if ($errorMessage)
        <div
            class="fi-ai-chat-error bg-danger-50 ring-danger-600/10 dark:bg-danger-500/10 dark:ring-danger-400/20 mx-6 mb-4 flex items-start gap-3 rounded-lg px-4 py-3 ring-1 transition-all duration-200"
        >
            <x-filament::icon
                icon="heroicon-o-exclamation-triangle"
                class="text-danger-600 dark:text-danger-400 h-5 w-5 shrink-0"
            />
            <div class="text-danger-600 dark:text-danger-400 flex-1 text-sm">
                {{ $errorMessage }}
            </div>
        </div>
    @endif

    {{-- Input Area --}}
    <div
        class="fi-ai-chat-input-ctn border-t border-gray-200 bg-white pt-4 transition-colors duration-200 dark:border-gray-700 dark:bg-gray-950"
    >
        <div class="fi-ai-chat-actions flex items-end gap-3">
            <div class="flex-1">
                <x-filament::input.wrapper
                    class="fi-ai-chat-input focus-within:ring-primary-600 dark:focus-within:ring-primary-500 transition-all duration-200 focus-within:ring-2"
                >
                    <x-filament::input
                        type="text"
                        wire:model.live="userInput"
                        wire:keydown.enter="sendMessage"
                        placeholder="Message AI Assistant..."
                        :disabled="$generating"
                        class="resize-none transition-colors duration-200 placeholder:text-gray-400 dark:placeholder:text-gray-600"
                    />
                </x-filament::input.wrapper>
            </div>

            <x-filament::button
                class="fi-ai-chat-regenerate-btn"
                wire:click="regenerate"
                :disabled="$generating"
                icon="heroicon-o-arrow-path"
                color="info"
            />

            <x-filament::button
                class="fi-ai-chat-send-btn"
                wire:click="sendMessage"
                :disabled="$generating || !$userInput"
                icon="heroicon-o-arrow-up"
                color="primary"
            />
        </div>

        {{-- Loading Indicator --}}
        @if ($generating)
            <div class="fi-ai-chat-loading mt-3 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <x-filament::loading-indicator class="h-3 w-3" />
                <span class="animate-pulse">AI is thinking...</span>
            </div>
        @endif
    </div>
</div>
