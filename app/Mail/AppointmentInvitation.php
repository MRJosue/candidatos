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
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class AppointmentInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Appointment $appointment)
    {
        $this->appointment->loadMissing(['talent.cvProfile', 'vacancy.company', 'vacancy.position', 'user']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitacion a cita: '.$this->appointment->talent?->full_name,
            using: [
                function (Email $message): void {
                    $message->getHeaders()->addTextHeader('Content-Class', 'urn:content-classes:calendarmessage');
                    $message->addPart($this->calendarPart());
                },
            ],
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
        return [];
    }

    private function calendarInvite(): string
    {
        $startsAt = $this->appointment->scheduled_at->copy()->timezone('UTC');
        $endsAt = $startsAt->copy()->addHour();
        $summary = 'Cita con '.$this->appointment->talent?->full_name;
        $company = $this->appointment->vacancy?->display_company;
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'candidatos.icu';
        $organizerEmail = config('mail.from.address');
        $organizerName = config('mail.from.name', config('app.name'));
        $location = $this->appointment->vacancy?->location ?: $company;
        $description = collect([
            'Candidato: '.$this->appointment->talent?->full_name,
            'Vacante: '.$this->appointment->vacancy?->display_title,
            $company ? 'Empresa: '.$company : null,
            $this->appointment->notes ? 'Notas: '.$this->appointment->notes : null,
        ])->filter()->implode("\n");

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//CV Studio//Appointments//ES',
            'CALSCALE:GREGORIAN',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'UID:cv-studio-appointment-'.$this->appointment->id.'@'.$host,
            'DTSTAMP:'.now()->timezone('UTC')->format('Ymd\THis\Z'),
            $this->appointment->created_at ? 'CREATED:'.$this->appointment->created_at->copy()->timezone('UTC')->format('Ymd\THis\Z') : null,
            $this->appointment->updated_at ? 'LAST-MODIFIED:'.$this->appointment->updated_at->copy()->timezone('UTC')->format('Ymd\THis\Z') : null,
            'DTSTART:'.$startsAt->format('Ymd\THis\Z'),
            'DTEND:'.$endsAt->format('Ymd\THis\Z'),
            'SUMMARY:'.$this->escapeCalendarText($summary),
            'DESCRIPTION:'.$this->escapeCalendarText($description),
            'LOCATION:'.$this->escapeCalendarText($location),
            'ORGANIZER;CN='.$this->escapeCalendarParameter($organizerName).':MAILTO:'.$organizerEmail,
            ...$this->attendeeLines(),
            'STATUS:CONFIRMED',
            'TRANSP:OPAQUE',
            'SEQUENCE:0',
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return collect($lines)
            ->filter(fn (?string $line) => filled($line))
            ->map(fn (string $line) => $this->foldCalendarLine($line))
            ->implode("\r\n")."\r\n";
    }

    private function calendarPart(): DataPart
    {
        $fileName = 'cita-'.$this->appointment->id.'.ics';
        $part = new DataPart($this->calendarInvite(), $fileName, 'text/calendar');

        $part->getHeaders()->setHeaderBody('Parameterized', 'Content-Type', 'text/calendar');
        $part->getHeaders()->setHeaderParameter('Content-Type', 'method', 'REQUEST');
        $part->getHeaders()->setHeaderParameter('Content-Type', 'charset', 'UTF-8');
        $part->getHeaders()->setHeaderParameter('Content-Type', 'name', $fileName);

        return $part;
    }

    /**
     * @return array<int, string>
     */
    private function attendeeLines(): array
    {
        return collect([
            [
                'name' => $this->appointment->talent?->full_name,
                'email' => $this->appointment->talent?->contact_email,
            ],
            [
                'name' => $this->appointment->vacancy?->display_company,
                'email' => $this->appointment->vacancy?->company?->email,
            ],
        ])
            ->filter(fn (array $attendee) => filled($attendee['email']))
            ->unique('email')
            ->map(fn (array $attendee): string => 'ATTENDEE;CN='
                .$this->escapeCalendarParameter($attendee['name'] ?? $attendee['email'])
                .';ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE:MAILTO:'
                .$attendee['email'])
            ->values()
            ->all();
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

    private function escapeCalendarParameter(?string $text): string
    {
        $escaped = Str::of($text ?? '')
            ->replace('\\', '\\\\')
            ->replace('"', '\"')
            ->replace("\n", ' ')
            ->replace("\r", '')
            ->toString();

        return '"'.$escaped.'"';
    }

    private function foldCalendarLine(string $line): string
    {
        $folded = '';

        while (strlen($line) > 75) {
            $folded .= substr($line, 0, 75)."\r\n ";
            $line = substr($line, 75);
        }

        return $folded.$line;
    }
}
