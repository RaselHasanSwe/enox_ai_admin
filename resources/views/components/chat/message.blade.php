@props(['message', 'showMeta' => false, 'agentName' => null])

@php
    $payload = is_array($message) ? $message : ['message' => (string) $message];
    if (empty($payload['agent_name']) && $agentName) {
        $payload['agent_name'] = $agentName;
    }
    $parsed = \App\Support\ChatContent::parse($payload);
    $sender = $parsed['sender'];
    $align = $sender === 'user' ? 'user' : ($sender === 'agent' ? 'agent' : 'ai');
@endphp

<div class="chat-row is-{{ $align }}">
    <div class="chat-bubble msg-{{ $sender }}">
        @if(!empty($showMeta))
            <div class="chat-meta">{{ $parsed['sender_label'] }}@if($parsed['timestamp']) · {{ $parsed['timestamp'] }}@endif</div>
        @endif

        @foreach($parsed['images'] as $src)
            <a href="{{ $src }}" target="_blank" rel="noopener" class="chat-image-link">
                <img src="{{ $src }}" alt="Attachment" class="chat-image">
            </a>
        @endforeach

        @if($parsed['html'] !== '')
            <div class="chat-md">{!! $parsed['html'] !!}</div>
        @endif

        @if($parsed['json_pretty'])
            <pre class="chat-json">{{ $parsed['json_pretty'] }}</pre>
        @endif

        @if($parsed['products'])
            <div class="chat-products">
                @foreach($parsed['products'] as $product)
                    @php
                        $href = $product['product_url'] ?? '#';
                        $img = $product['product_image'] ?? null;
                        $price = $product['discount_price'] ?? $product['price'] ?? null;
                        $currency = $product['currency'] ?? 'GBP';
                        $symbol = $currency === 'GBP' ? '£' : ($currency === 'USD' ? '$' : ($currency === 'EUR' ? '€' : $currency.' '));
                    @endphp
                    <a class="chat-product" href="{{ $href }}" target="_blank" rel="noopener">
                        @if($img)
                            <img src="{{ $img }}" alt="" class="chat-product-img">
                        @endif
                        <span class="chat-product-body">
                            <span class="chat-product-name">{{ $product['product_name'] ?? 'Product' }}</span>
                            @if($price !== null)
                                <span class="chat-product-price">{{ $symbol }}{{ $price }}</span>
                            @endif
                        </span>
                    </a>
                @endforeach
            </div>
        @endif

        @if(!empty($parsed['tool_calls']))
            <div class="chat-tools">Tools: {{ implode(', ', $parsed['tool_calls']) }}</div>
        @endif
        @if(!empty($parsed['latency_ms']))
            <div class="chat-tools">Response: {{ $parsed['latency_ms'] }}ms</div>
        @endif
    </div>
</div>
