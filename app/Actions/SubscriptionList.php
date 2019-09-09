<?php

namespace App\Actions;

use DrewM\MailChimp\MailChimp;


class SubscriptionList
{
  /** @var \DrewM\MailChimp\MailChimp */
  protected $mailChimp;

  /** @var \App\Actions\SubscriptionListCollection */
  protected $lists;

  public function __construct(MailChimp $mailChimp, SubscriptionListCollection $lists)
  {
    $this->mailChimp = $mailChimp;
    $this->lists = $lists;
  }

  public function subscribe(string $email, array $mergeFields = [], string $listName = '', array $options = [])
  {
    $list = $this->lists->findByName($listName);
    $options = $this->getSubscriptionOptions($email, $mergeFields, $options);
    $response = $this->mailChimp->post("subscribers/{$list->getId()}/members", $options);
    if (!$this->lastActionSucceeded()) {
      return false;
    }
    return $response;
  }

  public function subscribePending(string $email, array $mergeFields = [], string $listName = '', array $options = [])
  {
    $options = array_merge($options, ['status' => 'pending']);
    return $this->subscribe($email, $mergeFields, $listName, $options);
  }

  public function subscribeOrUpdate(string $email, array $mergeFields = [], string $listName = '', array $options = [])
  {
    $list = $this->lists->findByName($listName);
    $options = $this->getSubscriptionOptions($email, $mergeFields, $options);
    $response = $this->mailChimp->put("subscribers/{$list->getId()}/members/{$this->getSubscriberHash($email)}", $options);
    if (!$this->lastActionSucceeded()) {
      return false;
    }
    return $response;
  }

  public function getMembers(string $listName = '', array $parameters = [])
  {
    $list = $this->lists->findByName($listName);
    return $this->mailChimp->get("subscribers/{$list->getId()}/members", $parameters);
  }

  public function getMember(string $email, string $listName = '')
  {
    $list = $this->lists->findByName($listName);
    return $this->mailChimp->get("subscribers/{$list->getId()}/members/{$this->getSubscriberHash($email)}");
  }

  public function getMemberActivity(string $email, string $listName = '')
  {
    $list = $this->lists->findByName($listName);
    return $this->mailChimp->get("subscribers/{$list->getId()}/members/{$this->getSubscriberHash($email)}/activity");
  }

  public function hasMember(string $email, string $listName = ''): bool
  {
    $response = $this->getMember($email, $listName);
    if (!isset($response['email'])) {
      return false;
    }
    if (strtolower($response['email']) != strtolower($email)) {
      return false;
    }
    return true;
  }

  public function isSubscribed(string $email, string $listName = ''): bool
  {
    $response = $this->getMember($email, $listName);
    if (!isset($response)) {
      return false;
    }
    if ($response['status'] != 'subscribed') {
      return false;
    }
    return true;
  }

  public function unsubscribe(string $email, string $listName = '')
  {
    $list = $this->lists->findByName($listName);
    $response = $this->mailChimp->patch("subscribers/{$list->getId()}/members/{$this->getSubscriberHash($email)}", [
      'status' => 'unsubscribed',
    ]);
    if (!$this->lastActionSucceeded()) {
      return false;
    }
    return $response;
  }

  public function updateEmailAddress(string $currentEmailAddress, string $newEmailAddress, string $listName = '')
  {
    $list = $this->lists->findByName($listName);
    $response = $this->mailChimp->patch("subscribers/{$list->getId()}/members/{$this->getSubscriberHash($currentEmailAddress)}", [
      'email' => $newEmailAddress,
    ]);
    return $response;
  }

  public function delete(string $email, string $listName = '')
  {
    $list = $this->lists->findByName($listName);
    $response = $this->mailChimp->delete("subscribers/{$list->getId()}/members/{$this->getSubscriberHash($email)}");
    return $response;
  }
  public function getTags(string $email, string $listName = '')
  {
    $list = $this->lists->findByName($listName);
    return $this->mailChimp->get("subscribers/{$list->getId()}/members/{$this->getSubscriberHash($email)}/tags");
  }

  public function addTags(array $tags, string $email, string $listName = '')
  {
    $list = $this->lists->findByName($listName);
    $payload = collect($tags)->map(function ($tag) {
      return ['name' => $tag, 'status' => 'active'];
    })->toArray();
    return $this->mailChimp->post("subscribers/{$list->getId()}/members/{$this->getSubscriberHash($email)}/tags", [
      'tags' => $payload,
    ]);
  }


  public function removeTags(array $tags, string $email, string $listName = '')
  {
    $list = $this->lists->findByName($listName);
    $payload = collect($tags)->map(function ($tag) {
      return ['name' => $tag, 'status' => 'inactive'];
    })->toArray();
    return $this->mailChimp->post("subscribers/{$list->getId()}/members/{$this->getSubscriberHash($email)}/tags", [
      'tags' => $payload,
    ]);
  }



  public function getApi(): MailChimp
  {
    return $this->mailChimp;
  }

  public function getLastError()
  {
    return $this->mailChimp->getLastError();
  }
  public function lastActionSucceeded(): bool
  {
    return $this->mailChimp->success();
  }
  protected function getSubscriberHash(string $email): string
  {
    return $this->mailChimp->subscriberHash($email);
  }

  protected function getSubscriptionOptions(string $email, array $mergeFields, array $options): array
  {
    $defaultOptions = [
      'email' => $email,
      'status' => 'subscribed',
      'email_type' => 'html',
    ];
    if (count($mergeFields)) {
      $defaultOptions['merge_fields'] = $mergeFields;
    }
    $options = array_merge($defaultOptions, $options);
    return $options;
  }
}
