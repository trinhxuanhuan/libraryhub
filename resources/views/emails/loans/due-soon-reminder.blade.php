<x-mail::message>
# Nhắc nhở sắp đến hạn trả sách

Xin chào {{ $loan->user->name }},

LibraryHub thông báo phiếu mượn **#{{ $loan->id }}**
của bạn sắp đến hạn trả sách.

**Hạn trả:** {{ $loan->due_at->copy()->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}

@php
    $unresolvedItems = $loan->items->whereNull('resolved_at');
@endphp

@if ($unresolvedItems->isNotEmpty())
**Các sách cần trả:**

@foreach ($unresolvedItems as $item)
- {{ $item->book?->title ?? 'Sách không còn thông tin' }}
@endforeach

@endif

Vui lòng chuẩn bị trả sách đúng hạn để tránh phát sinh phí quá hạn.

Nếu cần gia hạn, bạn có thể kiểm tra điều kiện gia hạn
trong danh sách phiếu mượn của mình.

<x-mail::button :url="route('member.loans.index')">
Xem phiếu mượn của tôi
</x-mail::button>

Trân trọng,

**LibraryHub**
</x-mail::message>
