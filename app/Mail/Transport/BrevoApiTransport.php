<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

/**
 * Sends mail through Brevo's transactional HTTP API instead of SMTP.
 *
 * Render's free web service silently drops outbound connections on the SMTP
 * ports (25/465/587) — connections just hang for ~60s and time out, which is
 * a common anti-abuse restriction on free PaaS tiers, not a Brevo rejection.
 * The HTTP API talks plain HTTPS on port 443, which isn't blocked.
 */
class BrevoApiTransport extends AbstractTransport
{
    public function __construct(private readonly string $apiKey)
    {
        parent::__construct();
    }

    public function __toString(): string
    {
        return 'brevo+api://api.brevo.com';
    }

    protected function doSend(SentMessage $message): void
    {
        $email = $message->getOriginalMessage();

        if (! $email instanceof Email) {
            throw new TransportException('BrevoApiTransport only supports Symfony\Component\Mime\Email messages.');
        }

        $payload = [
            'sender' => $this->addressToArray($email->getFrom()[0] ?? new Address('no-reply@example.com')),
            'to' => $this->addressesToArray($email->getTo()),
            'subject' => (string) $email->getSubject(),
        ];

        if ($cc = $this->addressesToArray($email->getCc())) {
            $payload['cc'] = $cc;
        }
        if ($bcc = $this->addressesToArray($email->getBcc())) {
            $payload['bcc'] = $bcc;
        }
        if ($replyTo = $this->addressesToArray($email->getReplyTo())) {
            $payload['replyTo'] = $replyTo[0];
        }
        if ($html = $email->getHtmlBody()) {
            $payload['htmlContent'] = (string) $html;
        }
        if ($text = $email->getTextBody()) {
            $payload['textContent'] = (string) $text;
        }

        $attachments = array_map(
            fn (DataPart $part) => [
                'name' => $part->getFilename() ?? 'attachment',
                'content' => base64_encode($part->getBody()),
            ],
            $email->getAttachments()
        );
        if ($attachments) {
            $payload['attachment'] = $attachments;
        }

        $response = Http::withHeaders([
            'api-key' => $this->apiKey,
            'Accept' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', $payload);

        if ($response->failed()) {
            throw new TransportException(
                'Brevo API mail send failed: '.$response->status().' '.$response->body()
            );
        }
    }

    /** @return array<int, array{email: string, name?: string}> */
    private function addressesToArray(array $addresses): array
    {
        return array_map($this->addressToArray(...), $addresses);
    }

    /** @return array{email: string, name?: string} */
    private function addressToArray(Address $address): array
    {
        $entry = ['email' => $address->getAddress()];
        if ($address->getName() !== '') {
            $entry['name'] = $address->getName();
        }

        return $entry;
    }
}
