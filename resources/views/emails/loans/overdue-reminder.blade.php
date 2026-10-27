<x-mail::message>
# Thông báo sách mượn quá hạn

Xin chào {{ $loan->user->name }},

LibraryHub thông báo phiếu mượn **#{{ $loan->id }}**
của bạn đã quá hạn trả sách.

**Hạn trả:** {{ $loan->due_at->format('d/m/Y H:i') }}

@php
    $unresolvedItems = $loan->items->whereNull('resolved_at');
@endphp

@if ($unresolvedItems->isNotEmpty())
**Các sách chưa hoàn tất xử lý:**

@foreach ($unresolvedItems as $item)
- {{ $item->book?->title ?? 'Sách không còn thông tin' }}
@endforeach

@endif

Vui lòng liên hệ thư viện và hoàn tất việc trả sách
sớm nhất có thể để tránh phát sinh thêm phí quá hạn.

<x-mail::button :url="route('member.loans.index')">
Xem phiếu mượn của tôi
</x-mail::button>

Trân trọng,

**LibraryHub**
</x-mail::message>
