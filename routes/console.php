<?php

use Illuminate\Support\Facades\Artisan;
use Mailtrap\Helper\ResponseHelper;
use Mailtrap\MailtrapClient;
use Mailtrap\Mime\MailtrapEmail;
use Symfony\Component\Mime\Address;
use Illuminate\Support\Facades\Schedule;

Artisan::command('send-mail', function () {
    $email = (new MailtrapEmail())
        ->from(new Address('noreply@naicatech.com', 'The CRM'))
        ->to(new Address('ncs.it02@outlook.com'))
        ->subject('You are awesome!')
        ->text('Congrats for sending test email with Mailtrap!')
    ;

    $response = MailtrapClient::initSendingEmails(
        apiKey: '76226dca9a847a0b796ee1e1868948b1'
    )->send($email);

    var_dump(ResponseHelper::toArray($response));
})->purpose('Send Mail via API');

Artisan::command('test-mail', function () {
    \Illuminate\Support\Facades\Mail::raw('Test email from The CRM via SMTP', function ($m) {
        $m->to('ncs.it02@outlook.com')->subject('SMTP Test');
    });
    $this->info('Email sent!');
})->purpose('Test SMTP mail');

Schedule::command('queue:work --stop-when-empty')
    ->everyMinute()
    ->withoutOverlapping();

// Check for Doc Sent deals that haven't progressed in 24+ hours
Schedule::command('deals:check-stale-stages')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Check for deals not touched in 24 hours (any stage)
Schedule::command('deals:check-inactive')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('gdpr:anonymize-expired')->daily();
