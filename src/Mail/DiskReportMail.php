<?php

namespace DeadSimpleApps\TreeSizeMailer\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DiskReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $rows;

    public array $overview;

    public array $vendorBreakdown;

    public array $treeView;

    public string $basePath;

    public string $generatedAt;

    public function __construct(array $rows, array $overview, array $vendorBreakdown, array $treeView, string $basePath)
    {
        $this->rows = $rows;
        $this->overview = $overview;
        $this->vendorBreakdown = $vendorBreakdown;
        $this->treeView = $treeView;
        $this->basePath = $basePath;
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
