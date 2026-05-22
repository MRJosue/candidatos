<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class AppointmentInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Appointment $appointment)
    {
        $this->appointment->loadMissing(['talent', 'vacancy.company', 'vacancy.position', 'user']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitacion a cita: '.$this->appointment->talent?->full_name
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.appointments.invitation',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => $this->calendarInvite(),
                'cita-'.$this->appointment->id.'.ics'
            )->withMime('text/calendar; charset=UTF-8; method=REQUEST'),
        ];
    }

    private function calendarInvite(): string
    {
        $startsAt = $this->appointment->scheduled_at->copy()->timezone('UTC');
        $endsAt = $startsAt->copy()->addHour();
        $summary = 'Cita con '.$this->appointment->talent?->full_name;
        $company = $this->appointment->vacancy?->display_company;
        $description = collect([
            'Candidato: '.$this->appointment->talent?->full_name,
            'Vacante: '.$this->appointment->vacancy?->display_title,
            $company ? 'Empresa: '.$company : null,
            $this->appointment->notes ? 'Notas: '.$this->appointment->notes : null,
        ])->filter()->implode('\\n');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//CV Studio//Appointments//ES',
            'CALSCALE:GREGORIAN',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'UID:appointment-'.$this->appointment->id.'@'.parse_url(config('app.url'), PHP_URL_HOST),
            'DTSTAMP:'.$startsAt->format('Ymd\THis\Z'),
            'DTSTART:'.$startsAt->format('Ymd\THis\Z'),
            'DTEND:'.$endsAt->format('Ymd\THis\Z'),
            'SUMMARY:'.$this->escapeCalendarText($summary),
            'DESCRIPTION:'.$this->escapeCalendarText($description),
            'ORGANIZER;CN='.$this->escapeCalendarText($this->appointment->user?->name ?? config('app.name')).':MAILTO:'.$this->appointment->user?->email,
            'STATUS:CONFIRMED',
            'SEQUENCE:0',
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", $lines)."\r\n";
    }

    private function escapeCalendarText(?string $text): string
    {
        return Str::of($text ?? '')
            ->replace('\\', '\\\\')
            ->replace("\n", '\\n')
            ->replace("\r", '')
            ->replace(',', '\\,')
            ->replace(';', '\\;')
            ->toString();
    }
}
