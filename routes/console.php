<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('loans:mark-overdue')
    ->dailyAt('00:05')
    ->timezone('Asia/Ho_Chi_Minh')
    ->withoutOverlapping();

// Đánh dấu phiếu quá hạn lúc 00:05
Schedule::command('loans:mark-overdue')
    ->dailyAt('00:05')
    ->timezone('Asia/Ho_Chi_Minh')
    ->withoutOverlapping();

// Gửi email nhắc trả lúc 08:00
Schedule::command('loans:send-overdue-reminders')
    ->dailyAt('08:00')
    ->timezone('Asia/Ho_Chi_Minh')
    ->withoutOverlapping();
