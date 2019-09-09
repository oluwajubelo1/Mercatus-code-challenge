<?php

namespace App\Actions;

use Illuminate\Support\Collection;
use App\InvalidSubscriptionList;

class SubscriptionListCollection extends Collection
{
  /** @var string */
  public $defaultListName = '';

  public static function createFromConfig(array $config): self
  {
    $collection = new static();
    foreach ($config['subscribers'] as $name => $listProperties) {
      $collection->push(new SubscriptionList($name, $listProperties));
    }
    $collection->defaultListName = $config['defaultListName'];
    return $collection;
  }

  public function findByName(string $name): subscriptionList
  {
    if ($name === '') {
      return $this->getDefault();
    }
    foreach ($this->items as $subscriptionList) {
      if ($subscriptionList->getName() === $name) {
        return $subscriptionList;
      }
    }
    throw InvalidSubscriptionList::noListWithName($name);
  }

  public function getDefault(): subscriptionList
  {
    foreach ($this->items as $subscriptionList) {
      if ($subscriptionList->getName() === $this->defaultListName) {
        return $subscriptionList;
      }
    }
    throw InvalidSubscriptionList::defaultListDoesNotExist($this->defaultListName);
  }
}
