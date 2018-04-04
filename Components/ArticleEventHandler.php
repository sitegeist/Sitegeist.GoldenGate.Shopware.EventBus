<?php

namespace SitegeistGoldenGateShopwareEventBus\Components;

use Shopware\Models\Article\Article;
use Shopware\Models\Category\Category;

use Sitegeist\Goldengate\Dto\Serializer\CategoryReferenceSerializer;
use Sitegeist\Goldengate\Dto\Serializer\ProductReferenceSerializer;
use Sitegeist\Goldengate\Dto\Structure\CategoryReference;
use Sitegeist\Goldengate\Dto\Structure\ProductReference;

/**
 * Class ArticleEventHandler
 * @package SitegeistGoldenGateShopwareEventBus\Components
 */
class ArticleEventHandler
{
    const ARTICLE_ID_TAG = '{articleData}';
    const CATEGORY_ID_TAG = '{categoryData}';

    /** @var string */
    private $notifyArticleUrl;

    /** @var string */
    private $notifyCategoryUrl;

    /** @var resource */
    private $curlClient;

    /**
     * ArticleEventHandler constructor.
     * @param string $notifyArticleUrl
     * @param string $notifyCategoryUrl
     */
    public function __construct($notifyArticleUrl = '', $notifyCategoryUrl = '')
    {
        $this->notifyArticleUrl = $notifyArticleUrl;
        $this->notifyCategoryUrl = $notifyCategoryUrl;
    }

    /**
     * @param Article $article
     */
    public function handleUpdate($article)
    {
        try {
            $this->notifyArticleUrl($article->getMainDetail()->getNumber());
            $this->notifyCategoryUrl($article);
        } catch (\Exception $ex) {
            // TODO: log error messages
        }
    }

    /**
     * @param $articleNr
     */
    private function notifyArticleUrl($articleNr)
    {
        /** @var ProductReference $productReference */
        $productReference = new ProductReference();
        $productReference->setId($articleNr);

        /** @var ProductReferenceSerializer $productReferenceSerializer */
        $productReferenceSerializer = new ProductReferenceSerializer();
        $productReferenceData = $productReferenceSerializer->serialize($productReference);

        $this->callUrl($this->notifyArticleUrl, ['productReference' => $productReferenceData]);
    }

    /**
     * @param Article $article
     */
    private function notifyCategoryUrl($article)
    {
        /** @var Category $category */
        foreach ($article->getAllCategories() as $category)
        {
            /** @var CategoryReference $categoryReference */
            $categoryReference = new CategoryReference();
            $categoryReference->setId($category->getId());

            /** @var CategoryReferenceSerializer $categoryReferenceSerializer */
            $categoryReferenceSerializer = new CategoryReferenceSerializer();
            $categoryReferenceData = $categoryReferenceSerializer->serialize($categoryReference);

            //$categoryUrl = str_replace(self::CATEGORY_ID_TAG, $categoryReferenceData, $this->notifyCategoryUrl);
            $this->callUrl($this->notifyCategoryUrl, ['categoryReference' => $categoryReferenceData]);
        }
    }

    /**
     * @param string $url
     * @param $postData
     * @throws \Exception
     */
    private function callUrl($url, $postData)
    {
        $curlRequest  = $this->getCurlClient();
        curl_setopt($curlRequest, CURLOPT_URL, $url);
        curl_setopt($curlRequest, CURLOPT_POSTFIELDS, $postData);

        $result = json_decode(curl_exec($curlRequest), true);
        $httpCode = curl_getinfo($curlRequest, CURLINFO_HTTP_CODE);

        if ($httpCode !== 200 || !$result['success']) {
            throw new \Exception('error notify article url');
        }
    }

    /**
     * @return resource
     */
    private function getCurlClient()
    {
        if (!$this->curlClient)
        {
            $this->curlClient = curl_init();
            curl_setopt_array($this->curlClient, [
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_FOLLOWLOCATION => false
            ]);
        }
        return $this->curlClient;
    }
}