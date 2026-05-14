<?php

namespace DeadSimpleApps\TreeSizeMailer\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TreeSizeReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $rows;

    public array $treeView;

    public array $customBreakdowns;

    public string $basePath;

    public array $config;

    public string $generatedAt;

    public function __construct(array $rows, array $treeView, array $customBreakdowns, string $basePath, array $config)
    {
        $this->rows = $rows;
        $this->treeView = $treeView;
        $this->customBreakdowns = $customBreakdowns;
        $this->basePath = $basePath;
        $this->config = $config;
        $this->generatedAt = now()->format('Y-m-d H:i:s');
    }

    public function envelope(): Envelope
    {
        $appName = config('tree-size-mailer.app_name', config('app.name', 'Laravel'));

        return new Envelope(
            subject: '[' . $appName . '] Directory Tree Size Report – ' . now()->format('Y-m-d'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'tree-size-mailer::email',
        );
    }
}
