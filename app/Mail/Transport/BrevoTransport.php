<?php

namespace App\Mail\Transport;

use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;
use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;
use Illuminate\Support\Facades\Config;

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
    protected function doSend(SentMessage $message): void
    {
        $email = $message->getOriginalMessage();

        $config = Configuration::getDefaultConfiguration()
            ->setApiKey('api-key', $this->apiKey);

        $apiInstance = new TransactionalEmailsApi(null, $config);

        $sendSmtpEmail = new SendSmtpEmail();
        $sendSmtpEmail->setSubject($email->getSubject());
        
        // Set sender - get from email or use config default
        $from = $email->getFrom();
        if ($from) {
            $fromAddresses = [];
            foreach ($from as $address) {
                $name = $address->getName() ?: $address->getAddress();
                $fromAddresses[] = [
                    'email' => $address->getAddress(),
                    'name' => $name,
                ];
            }
            $sendSmtpEmail->setSender($fromAddresses[0]);
        } else {
            // Use default from config if not set
            $defaultFrom = Config::get('mail.from.address');
            $defaultName = Config::get('mail.from.name');
            if ($defaultFrom) {
                $sendSmtpEmail->setSender([
                    'email' => $defaultFrom,
                    'name' => $defaultName ?: $defaultFrom,
                ]);
            }
        }

        // Set recipients
        $toAddresses = [];
        foreach ($email->getTo() as $address) {
            $name = $address->getName() ?: $address->getAddress();
            $toAddresses[] = [
                'email' => $address->getAddress(),
                'name' => $name,
            ];
        }
        $sendSmtpEmail->setTo($toAddresses);

        // Set CC
        if ($email->getCc()) {
            $ccAddresses = [];
            foreach ($email->getCc() as $address) {
                $name = $address->getName() ?: $address->getAddress();
                $ccAddresses[] = [
                    'email' => $address->getAddress(),
                    'name' => $name,
                ];
            }
            $sendSmtpEmail->setCc($ccAddresses);
        }

        // Set BCC
        if ($email->getBcc()) {
            $bccAddresses = [];
            foreach ($email->getBcc() as $address) {
                $name = $address->getName() ?: $address->getAddress();
                $bccAddresses[] = [
                    'email' => $address->getAddress(),
                    'name' => $name,
                ];
            }
            $sendSmtpEmail->setBcc($bccAddresses);
        }

        // Set reply-to
        if ($email->getReplyTo()) {
            $replyToAddresses = [];
            foreach ($email->getReplyTo() as $address) {
                $name = $address->getName() ?: $address->getAddress();
                $replyToAddresses[] = [
                    'email' => $address->getAddress(),
                    'name' => $name,
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
