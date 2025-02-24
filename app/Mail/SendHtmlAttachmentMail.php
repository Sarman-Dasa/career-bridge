<?php

namespace App\Mail;

use App\Http\Traits\ManageFiles;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SendHtmlAttachmentMail extends Mailable
{
    use Queueable, SerializesModels, ManageFiles;

    public $filePath;
    public $user;
    /**
     * Create a new message instance.
     */
    public function __construct($user)
    {
        $this->user = $user;
        $this->filePath = storage_path('app/public/design.pdf');
    }

    /**
     * Get the message envelope.
     */
    /**
     * Build the message.
     */
    public function build()
    {
        try {
            // If necessary, increase the memory limit and execution time.
            // ini_set('memory_limit', '512M');
            // set_time_limit(300); // Also increase execution time

            $imageSrc = $this->getBase64Image('chat-icon.png');

            $svgImg = $this->getBase64Image('file-pdf-box.svg');

            // Generate the PDF from the Blade view
            $pdf = Pdf::loadView('emails.user_data', ['user' => $this->user, 'imageSrc' => $imageSrc, 'svg' => $svgImg])->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true])
                ->setPaper('a4', 'portrait');

            // Define the file path in storage
            // $pdfFilePath = storage_path("app/public/user_details.pdf");

            // Store the PDF file
            // Storage::put("public/user_details.pdf", $pdf->output());

            return $this->subject('User Details Attachment')
                ->view('emails.user_data', ['user' => $this->user, 'imageSrc' => $imageSrc, 'svg' => $svgImg]) // Email body
                ->attachData($pdf->output(), "document.pdf", [
                    'mime' => 'application/pdf',
                ]);
        } catch (\Exception $e) {
            Log::info($e);
        }
    }
}
