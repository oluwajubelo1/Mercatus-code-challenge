<?php

use Illuminate\Support\Facades\Mail;

trait MailTracking
{
  protected $emails = [];

  /**@before */
  public function setUp(): void
  {
    parent::setUp();
    Mail::getSwiftMailer()
      ->registerPlugin(new TestingMailEventListner($this));
  }
  public function addEmail(Swift_Message $email)
  {
    $this->emails[] = $email;
  }

  public function seeEmailTo($receipent, Swift_Message $message = null)
  {
    $this->assertArrayHasKey($receipent, $this->getEmail($message)->getTo(), "No email was sent to $receipent");
    return $this;
  }

  public function seeEmailFrom($sender, Swift_Message $message = null)
  {
    $this->assertArrayHasKey($sender, $this->getEmail($message)->getFrom(), "No email was sent from $sender");
    return $this;
  }
  protected function seeEmailsSent($count)
  {
    $emailsSent = count($this->emails);
    $this->assertCount($count, $this->emails, "Expected $count emails to have been sent, but $emailsSent email(s) sent");
    return $this;
  }
  protected function seeEmailWasSent()
  {
    $this->assertNotEmpty($this->emails, 'No emails have been sent');
    return $this;
  }

  protected function seeEmailReplyToEquals($replyTo, Swift_Message $message = null)
  {

    $this->assertEquals($replyTo, key($this->getEmail($message)->getReplyTo()), "No email with the provided replyTo was sent");
    return $this;
  }

  protected function seeEmailSubjectEquals($subject, Swift_Message $message = null)
  {
    $this->assertEquals($subject, $this->getEmail($message)->getSubject(), "No email with the provided subject was sent");
    return $this;
  }

  protected function seeEmailsEquals($body, Swift_Message $message = null)
  {
    $this->assertEquals($body, $this->getEmail($message)->getBody(), "No email with the provided body was sent");
    return $this;
  }



  protected function seeEmailContains($excerpt, Swift_Message $message = null)
  {
    $this->assertContains($excerpt, $this->getEmail($message)->getBody(), "No email containing the provided body was found");
    return $this;
  }
  protected function seeEmailWasNotSent()
  {
    $this->assertEmpty($this->emails, 'Did not expect any emails to have been sent');
    return $this;
  }

  protected function getEmail(Swift_Message $message = null)
  {
    $this->seeEmailWasSent();
    return $message ?: $this->lastEmail();
  }

  protected function lastEmail()
  {
    return end($this->emails);
  }
}


class TestingMailEventListner implements Swift_Events_EventListener
{
  protected $test;
  public function __construct($test)
  {
    $this->test = $test;
  }
  public function beforeSendPerformed($event)
  {
    $message = $event->getMessage();
    $this->test->addEmail($event->getMessage());
  }
}
