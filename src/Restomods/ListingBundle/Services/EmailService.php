<?php

namespace Restomods\ListingBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;

class EmailService
{
    public function __construct(ContainerInterface $container, \Swift_Mailer $mailer, \Twig_Environment $twig)
    {
	    $this->container = $container;
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    public function sendMail($to, $subject, $content, $plainText = null, $useBaseTemplate = true, $from = null, $cc = null, $bcc = null, $replyTo = null, $attachments = null)
    {
	    $from = $from ? $from : $this->container->getParameter('mailer_address');
	    $fromName = $this->container->getParameter('mailer_name');
        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($from, $fromName)
            ->setTo($to)
            ->setBody($useBaseTemplate ? $this->twig->loadTemplate('RestomodsListingBundle:Emails:base.html.twig')->render(array('content' => $content)) : $content, "text/html");

        if ($plainText) $message->addPart($plainText, 'text/plain');
        if($replyTo) $message->setReplyTo($replyTo);
        if($cc) $message->setCc($cc);
        if($bcc) $message->setBcc($bcc);
        if(count($attachments)){
            foreach($attachments as $path){
                $message->attach(\Swift_Attachment::fromPath($path));
            }
        }

        return $this->mailer->send($message);
    }
}
