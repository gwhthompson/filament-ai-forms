@php
    use Carbon\Carbon;
@endphp

<div class="fi-ai-chat flex flex-col" wire:key="{{ $identifier }}" style="height: 600px">
    {{-- Messages Container --}}
    <div class="fi-ai-chat-messages flex-1 overflow-y-auto">
        @forelse ($messages as $index => $message)
            @if ($message['role'] === 'user')
                {{-- User Message: Right-aligned with colored bubble --}}
                <div
                    wire:key="message-{{ $index }}"
                    class="fi-ai-chat-message fi-ai-chat-message-user group relative flex justify-end px-4 py-3"
                >
                    <div
                        class="fi-ai-chat-message-content bg-primary-600 dark:bg-primary-500 max-w-[85%] rounded-2xl rounded-tr-sm px-4 py-2.5 text-white shadow-sm"
                    >
                        <p class="m-0 text-sm leading-relaxed break-words whitespace-pre-wrap">
                            {{ $message['content'] }}
                        </p>
                        <div class="text-primary-200 dark:text-primary-200 mt-1.5 text-right text-xs">
                            {{ Carbon::parse($message['timestamp'])->diffForHumans() }}
                        </div>
                    </div>

                    {{-- Delete Button --}}
                    <div
                        class="fi-ai-chat-message-delete-btn absolute top-1 right-1 opacity-0 transition-opacity group-hover:opacity-100"
                    >
                        <x-filament::icon-button
                            wire:click="deleteMessage({{ $index }})"
                            icon="heroicon-o-x-mark"
                            color="gray"
                            size="xs"
                            label="Delete message"
                        />
                    </div>
                </div>
            @else
                {{-- Assistant Message: Left-aligned with avatar, no bubble --}}
                <div
                    wire:key="message-{{ $index }}"
                    class="fi-ai-chat-message fi-ai-chat-message-assistant group relative flex gap-3 px-4 py-3"
                >
                    {{-- AI Avatar --}}
                    <div class="shrink-0">
                        <div
                            class="bg-primary-100 dark:bg-primary-900/50 flex h-8 w-8 items-center justify-center rounded-full"
                        >
                            <x-filament::icon
                                icon="heroicon-m-sparkles"
                                class="text-primary-600 dark:text-primary-400 h-4 w-4"
                            />
                        </div>
                    </div>

                    {{-- Content --}}
                    <div class="fi-ai-chat-message-content min-w-0 flex-1">
                        <p
                            class="m-0 text-sm leading-relaxed break-words whitespace-pre-wrap text-gray-700 dark:text-gray-300"
                        >
                            {{ $message['content'] }}
                        </p>
                        <div class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                            {{ Carbon::parse($message['timestamp'])->diffForHumans() }}
                        </div>
                    </div>

                    {{-- Delete Button --}}
                    <div
                        class="fi-ai-chat-message-delete-btn absolute top-1 left-10 opacity-0 transition-opacity group-hover:opacity-100"
                    >
                        <x-filament::icon-button
                            wire:click="deleteMessage({{ $index }})"
                            icon="heroicon-o-x-mark"
                            color="gray"
                            size="xs"
                            label="Delete message"
                        />
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

        {{-- Streaming Message --}}
        @if ($generating)
            <div class="fi-ai-chat-streaming group relative flex gap-3 px-4 py-3">
                {{-- AI Avatar with loading indicator --}}
                <div class="shrink-0">
                    <div
                        class="bg-primary-100 dark:bg-primary-900/50 flex h-8 w-8 items-center justify-center rounded-full"
                    >
                        <x-filament::loading-indicator class="text-primary-600 dark:text-primary-400 h-4 w-4" />
                    </div>
                </div>

                {{-- Content --}}
                <div class="fi-ai-chat-message-content min-w-0 flex-1">
                    <p
                        class="m-0 text-sm leading-relaxed break-words whitespace-pre-wrap text-gray-700 dark:text-gray-300"
                    >
                        <span wire:stream="ai-streaming" class="inline">{{ $streamingContent }}</span>
                        <span
                            class="fi-ai-chat-cursor bg-primary-600 dark:bg-primary-400 ml-0.5 inline-block h-4 w-0.5 animate-pulse align-text-bottom"
                        ></span>
                    </p>
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
