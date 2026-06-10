<?php

namespace App\Mail\Transport;

use Symfony\Component\Mailer\SmailerTransport;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;

class BrevoTransport extends AbstractTransport
{
    protected string $apiKey;

    public function __construct(string $apiKey)
    {
        parent::__construct();
        $this->apiKey = $apiKey;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSend(Email $email): void
    {
        $config = Configuration::getDefaultConfiguration()
            ->setApiKey('api-key', $this->apiKey);

        $apiInstance = new TransactionalEmailsApi(null, $config);

        $sendSmtpEmail = new SendSmtpEmail();
        $sendSmtpEmail->setSubject($email->getSubject());
        
        // Set sender
        if ($email->getFrom()) {
            $fromAddresses = [];
            foreach ($email->getFrom() as $address) {
                $fromAddresses[] = [
                    'email' => $address->getAddress(),
                    'name' => $address->getDisplayName(),
                ];
            }
            $sendSmtpEmail->setFrom($fromAddresses[0]);
        }

        // Set recipients
        $toAddresses = [];
        foreach ($email->getTo() as $address) {
            $toAddresses[] = [
                'email' => $address->getAddress(),
                'name' => $address->getDisplayName(),
            ];
        }
        $sendSmtpEmail->setTo($toAddresses);

        // Set CC
        if ($email->getCc()) {
            $ccAddresses = [];
            foreach ($email->getCc() as $address) {
                $ccAddresses[] = [
                    'email' => $address->getAddress(),
                    'name' => $address->getDisplayName(),
                ];
            }
            $sendSmtpEmail->setCc($ccAddresses);
        }

        // Set BCC
        if ($email->getBcc()) {
            $bccAddresses = [];
            foreach ($email->getBcc() as $address) {
                $bccAddresses[] = [
                    'email' => $address->getAddress(),
                    'name' => $address->getDisplayName(),
                ];
            }
            $sendSmtpEmail->setBcc($bccAddresses);
        }

        // Set reply-to
        if ($email->getReplyTo()) {
            $replyToAddresses = [];
            foreach ($email->getReplyTo() as $address) {
                $replyToAddresses[] = [
                    'email' => $address->getAddress(),
                    'name' => $address->getDisplayName(),
                ];
            }
            $sendSmtpEmail->setReplyTo($replyToAddresses[0]);
        }

        // Set HTML body
        if ($email->getHtmlBody()) {
            $sendSmtpEmail->setHtmlContent($email->getHtmlBody());
        }

        // Set text body
        if ($email->getTextBody()) {
            $sendSmtpEmail->setTextContent($email->getTextBody());
        }

        // Handle attachments
        $attachments = [];
        foreach ($email->getAttachments() as $attachment) {
            $attachments[] = [
                'content' => base64_encode($attachment->getBody()),
                'name' => $attachment->getFilename(),
            ];
        }
        if (!empty($attachments)) {
            $sendSmtpEmail->setAttachment($attachments);
        }

        try {
            $apiInstance->sendTransacEmail($sendSmtpEmail);
        } catch (\Exception $e) {
            throw new \RuntimeException('Brevo API error: ' . $e->getMessage(), 0, $e);
        }
    }

    public function __toString(): string
    {
        return 'brevo';
    }
}
