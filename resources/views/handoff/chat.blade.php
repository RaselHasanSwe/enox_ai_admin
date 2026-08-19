@extends('layouts.app')

@section('title', 'Agent Chat')

@section('content')
<div class="page-content page-content--chat">
    @if(session('status'))
        <div class="flash-banner">{{ session('status') }}</div>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
        <div>
            <h1 class="text-[17px] font-semibold text-slate-800 dark:text-slate-100 leading-tight">Chat with {{ $conversation['user_name'] ?? 'customer' }}</h1>
            <p class="text-[12px] text-slate-400 dark:text-slate-500 mt-0.5">{{ $conversation['user_email'] ?? '' }}</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <form method="POST" action="{{ route('handoff.release', $conversation['id']) }}">
                @csrf
                <button class="btn-secondary" type="submit">Release to queue</button>
            </form>
            <form method="POST" action="{{ route('handoff.resolve', $conversation['id']) }}">
                @csrf
                <button class="btn-primary" type="submit">Resolve</button>
            </form>
        </div>
    </div>

    <div class="chat-split">
        <div class="chat-shell">
            <div class="chat-thread" id="chat-messages">
                @forelse($messages as $msg)
                    <x-chat.message :message="$msg" :show-meta="true" :agent-name="$conversation['agent_name'] ?? ($user['name'] ?? null)" />
                @empty
                    <div class="empty-state">No messages yet.</div>
                @endforelse
            </div>

            <form id="chat-form" class="chat-composer">
                <textarea id="chat-input" rows="1" class="chat-composer-input" placeholder="Type a reply… Enter to send, Shift+Enter for a new line" required></textarea>
                <button class="btn-primary chat-composer-send" type="submit" id="chat-send">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7"/></svg>
                    Send
                </button>
            </form>
            <p id="chat-error" class="text-[12px] text-red-500 mt-2 hidden px-3 pb-2"></p>
        </div>

        <x-handoff.summary
            :summary="$conversation['handoff_summary'] ?? ''"
            :reason="$conversation['handoff_reason'] ?? null"
            :messages="$messages"
            :agent-name="$conversation['agent_name'] ?? ($user['name'] ?? null)"
        />
    </div>
</div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const conversationId = @json($conversation['id']);
    const sessionId = @json($conversation['session_id'] ?? '');
    const apiUrl = @json(rtrim($apiUrl, '/'));
    const token = @json($token);
    const wsUrl = @json($wsUrl) + '/ws/handoff/' + encodeURIComponent(sessionId);
    const box = document.getElementById('chat-messages');
    const form = document.getElementById('chat-form');
    const input = document.getElementById('chat-input');
    const sendBtn = document.getElementById('chat-send');
    const errorEl = document.getElementById('chat-error');
    const csrf = @json(csrf_token());

    function scrollToBottom() {
        box.scrollTop = box.scrollHeight;
    }

    function appendMessage(sender, text, extras = {}) {
        box.insertAdjacentHTML('beforeend', EnoxChat.renderMessageHtml(text, { sender, ...extras, showMeta: true }));
        scrollToBottom();
    }

    function resizeInput() {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 140) + 'px';
    }

    function readyComposer() {
        input.value = '';
        input.style.height = 'auto';
        sendBtn.disabled = false;
        input.focus();
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const message = input.value.trim();
        if (!message || sendBtn.disabled) return;
        errorEl.classList.add('hidden');
        sendBtn.disabled = true;
        appendMessage('agent', message, { agentName: window.ENOX_ADMIN?.agentName });
        readyComposer();
        try {
            const res = await fetch('/handoff/' + conversationId + '/message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ message }),
            });
            if (!res.ok) throw new Error('Send failed');
        } catch (err) {
            errorEl.textContent = 'Could not send message. Please try again.';
            errorEl.classList.remove('hidden');
        } finally {
            input.focus();
        }
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.requestSubmit();
        }
    });
    input.addEventListener('input', resizeInput);

    function handlePayload(payload) {
        if (payload.event === 'new_message' && payload.data.sender_type === 'user') {
            appendMessage('user', payload.data.message || '', { image_path: payload.data.image_path });
        }
    }

    let es = null;
    const startSseFallback = () => {
        if (es) return;
        es = new EventSource(apiUrl + '/handoff/stream/' + encodeURIComponent(sessionId));
        es.onmessage = (event) => {
            try { handlePayload(JSON.parse(event.data)); } catch (e) {}
        };
    };

    try {
        const ws = new WebSocket(wsUrl);
        ws.onmessage = (event) => {
            try { handlePayload(JSON.parse(event.data)); } catch (e) {}
        };
        ws.onclose = () => startSseFallback();
        ws.onerror = () => {
            ws.close();
            startSseFallback();
        };
    } catch (e) {
        startSseFallback();
    }

    scrollToBottom();
    input.focus();
});
</script>
@endpush
