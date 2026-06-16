<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('app:test-email {--to=}')]
#[Description('Send a test email')]
class TestEmail extends Command
{
    public function handle(): int
    {
        $to = $this->option('to') ?? 'ncs.photo02@gmail.com';

        $this->info("Sending test email to: {$to}");

        try {
            Mail::raw('This is a test email from Umbrella CRM.', function ($message) use ($to) {
                $message->to($to)
                    ->subject('Test Email - Umbrella CRM');
            });

            $this->info('Email sent successfully!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to send email: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
