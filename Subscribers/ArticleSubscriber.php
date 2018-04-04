<?php

namespace SitegeistGoldenGateShopwareEventBus\Subscribers;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Shopware\Models\Article\Article;
use SitegeistGoldenGateShopwareEventBus\Components\ArticleEventHandler;

/**
 * Class ArticleSubscriber
 * @package SitegeistGoldenGateShopwareEventBus\Subscribers
 */
class ArticleSubscriber implements EventSubscriber
{
    public function __construct()
    {
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            Events::postPersist,
            Events::postUpdate
        ];
    }

    /**
     * @param $eventArgs
     */
    public function postPersist($eventArgs)
    {
        $this->handleArticleEvents($eventArgs);
    }

    /**
     * @param $eventArgs
     */
    public function postUpdate($eventArgs)
    {
        $this->handleArticleEvents($eventArgs);
    }

    /**
     * @param $eventArgs
     */
    private function handleArticleEvents($eventArgs)
    {
        $article = $eventArgs->getEntity();

        if (!$article instanceof Article) {
            return;
        }

        $notifyArticleUrl = Shopware()->Config()->getByNamespace('SitegeistGoldenGateShopwareEventBus', 'notify_article_url');
        $notifyCategoryUrl = Shopware()->Config()->getByNamespace('SitegeistGoldenGateShopwareEventBus', 'notify_category_url');

        $articleEventHandler = new ArticleEventHandler($notifyArticleUrl, $notifyCategoryUrl);
        $articleEventHandler->handleUpdate($article);
    }
}