<?php

/**
 * Manage data.
 *
 * @category  Visidea
 * @package   Inferendo_Visidea
 * @author    Inferendo SRL <hello@visidea.ai>
 * @copyright 2022 Inferendo SRL
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0
 * @link      https://visidea.ai/
 */

namespace Inferendo\Visidea\Helper;

use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Module\Dir;
use Magento\Framework\File\Csv;
use Magento\Framework\App\PageCache\Version;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Checkout\Model\Cart;

/**
 * Helper class for managing Visidea data operations
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var OrderManagementInterface
     */
    protected $orderManagement;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\App\Config\Storage\WriterInterface
     */
    protected $writeConfig;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $file;

    /**
     * @var \Magento\Framework\Filesystem\DirectoryList
     */
    protected $dir;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory
     */
    protected $quoteFactory;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $quoteModel;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $quoteManagement;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $csvProcessor;

    /**
     * @var mixed
     */
    protected $fileFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $customers;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Framework\App\Cache\TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var \Magento\Framework\App\Cache\Frontend\Pool
     */
    protected $cacheFrontendPool;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    private $_httpContext;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory
     */
    private $quoteCollectionFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * Module enabled configuration path
     */
    public const MODULE_ENABLED = 'inferendo_visidea/general/enable';

    /**
     * Method __construct
     *
     * @param \Magento\Framework\App\Helper\Context                          $context
     * @param \Magento\Store\Model\StoreManagerInterface                     $storeManager
     * @param OrderManagementInterface                                       $orderManagement
     * @param \Magento\Framework\ObjectManagerInterface                      $objectManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface             $scopeConfig
     * @param \Magento\Framework\App\Config\Storage\WriterInterface          $scopeWriterConfig
     * @param \Magento\Framework\Filesystem\Io\File                          $file
     * @param \Magento\Framework\Filesystem\DirectoryList                    $dir
     * @param \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory     $quoteFactory
     * @param \Magento\Quote\Model\Quote                                     $quoteModel
     * @param \Magento\Quote\Model\QuoteManagement                           $quoteManagement
     * @param \Magento\Framework\Stdlib\DateTime\DateTime                    $dateTime
     * @param \Magento\Framework\App\Http\Context                            $httpContext
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Customer\Model\CustomerFactory                        $customerFactory
     * @param \Magento\Customer\Model\Customer                               $customers
     * @param \Magento\Framework\App\Cache\TypeListInterface                 $cacheTypeList
     * @param \Magento\Framework\App\Cache\Frontend\Pool                     $cacheFrontendPool
     * @param \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory     $quoteCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory     $orderCollectionFactory
     * @param Cart                                                           $cart
     *
     * @return void
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        OrderManagementInterface $orderManagement,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\Storage\WriterInterface $scopeWriterConfig,
        \Magento\Framework\Filesystem\Io\File $file,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteFactory,
        \Magento\Quote\Model\Quote $quoteModel,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\Customer $customers,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        Cart $cart
    ) {
        $this->storeManager = $storeManager;
        $this->logger = $context->getLogger();
        $this->orderManagement = $orderManagement;
        $this->objectManager = $objectManager;
        $this->scopeConfig = $scopeConfig;
        $this->writeConfig = $scopeWriterConfig;
        $this->file = $file;
        $this->dir = $dir;
        $this->quoteFactory = $quoteFactory;
        $this->quoteModel = $quoteModel;
        $this->quoteManagement = $quoteManagement;
        $this->dateTime = $dateTime;
        $this->_httpContext = $httpContext;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->customerFactory = $customerFactory;
        $this->customers = $customers;
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->cart = $cart;
        parent::__construct($context);
    }

    /**
     * Method isEnable
     *
     * @return int return if the module if enabled
     */
    public function isEnabled()
    {
        return 1;
    }

    /**
     * Method getConfig
     *
     * @param string $group   group
     * @param string $field   field
     * @param int    $storeId storeId
     *
     * @return string         return the config
     */
    public function getConfig($group, $field, $storeId = 0)
    {
        return $this->scopeConfig->getValue(
            'inferendo_visidea/' . $group . '/' . $field,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Method setConfig
     *
     * @param string $group   group
     * @param string $field   field
     * @param string $value   value
     *
     * @return void
     */
    public function setConfig($group, $field, $value)
    {
        $this->writeConfig->save('inferendo_visidea/' . $group . '/' . $field, $value);
    }

    /**
     * Method flushCache
     *
     * @return void
     */
    public function flushCache()
    {
        $_types = [
            'config',
            'layout',
            'block_html',
            'collections',
            'reflection',
            'db_ddl',
            'eav',
            'config_integration',
            'config_integration_api',
            'full_page',
            'translate',
            'config_webservice'
        ];

        foreach ($_types as $type) {
            $this->cacheTypeList->cleanType($type);
        }
        foreach ($this->cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
    }

    /**
     * Method getBaseUrl
     *
     * @param string $path path
     *
     * @return string         return full path
     */
    public function getBaseUrl($path)
    {
        return $this->storeManager->getStore()->getBaseUrl() . $path;
    }

    /**
     * Method getPrivateToken
     *
     * @return string         return private_token
     */
    public function getPrivateToken()
    {
        return $this->getConfig('general', 'private_token');
    }

    /**
     * Method getPublicToken
     *
     * @return string         return public_token
     */
    public function getPublicToken()
    {
        return $this->getConfig('general', 'public_token');
    }

    /**
     * Method getInteractionExportUrl
     *
     * @return string         return url
     */
    public function getInteractionExportUrl()
    {
        $token_id = $this->getConfig('general', 'private_token');
        return $this->getBaseUrl('pub/media/visidea/csv/interactions_' . $token_id . '.csv');
    }

    /**
     * Method getItemsExportUrl
     *
     * @return string         return url
     */
    public function getItemsExportUrl()
    {
        $token_id = $this->getConfig('general', 'private_token');
        return $this->getBaseUrl('pub/media/visidea/csv/items_' . $token_id . '.csv');
    }

    /**
     * Method getCustomerExportUrl
     *
     * @return string         return url
     */
    public function getCustomerExportUrl()
    {
        $token_id = $this->getConfig('general', 'private_token');
        return $this->getBaseUrl('pub/media/visidea/csv/users_' . $token_id . '.csv');
    }

    /**
     * Method getCartsCollection
     *
     * @return array return collection
     */
    public function getCartsCollection()
    {
        // Fetch the collection of quotes
        $cartsCollection = $this->quoteCollectionFactory->create();
        $cartsCollection->addFieldToSelect('*');
        $cartsCollection->addFieldToFilter('customer_id', ['neq' => 'NULL']);

        return $cartsCollection;
    }

    /**
     * Method getOrdersCollection
     *
     * @return array return collection
     */
    public function getOrdersCollection()
    {
        // Fetch the collection of orders
        $ordersCollection = $this->orderCollectionFactory->create();
        $ordersCollection->addFieldToSelect('*');
        $ordersCollection->addFieldToFilter('customer_id', ['neq' => 'NULL']);

        return $ordersCollection;
    }

    /**
     * Method getItemsCollection
     *
     * @return array return collection
     */
    public function getItemsCollection()
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*');

        return $collection;
    }

    /**
     * Method getUsersCollection
     *
     * @return array return collection
     */
    public function getUsersCollection()
    {
        return $this->customers->getCollection()
            ->addAttributeToSelect("*")
            ->load();
    }

    /**
     * Method createExportFolder
     *
     * @return void
     */
    public function createExportFolder()
    {
        $destPath = $this->dir->getPath('media') . '/visidea/csv';
        $this->file->mkdir($destPath, 0755, true);
    }

    /**
     * Method getInteractionsColumnsHeader
     *
     * @return array return headers
     */
    public function getInteractionsColumnsHeader()
    {
        $headers = [
            'user_id',
            'item_id',
            'action',
            'price',
            'quantity',
            'createdDate'
        ];
        return $headers;
    }

    /**
     * Method getUsersColumnsHeader
     *
     * @return array return headers
     */
    public function getUsersColumnsHeader()
    {
        $headers = [
            'user_id',
            'email',
            'name',
            'surname',
            'address',
            'city',
            'zip',
            'state',
            'country',
            'birthday',
            'createdDate'
        ];
        return $headers;
    }

    /**
     * Method getItemsColumnsHeader
     *
     * @return array return headers
     */
    public function getItemsColumnsHeader()
    {
        $headers = [
            'item_id',
            'name',
            'description',
            'brand_id',
            'brand_name',
            'price',
            'market_price',
            'discount',
            'page_ids',
            'page_names',
            'url',
            'images',
            'stock',
            'gender',
            'ean',
            'mpn',
            'code'
        ];
        return $headers;
    }

    /**
     * Method isLoggedIn
     *
     * @return bool return true if logged in
     */
    public function isLoggedIn()
    {
        $isLoggedIn = $this->_httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
        return $isLoggedIn;
    }

    /**
     * Get cart product IDs as comma-separated string
     *
     * @return string
     */
    public function getCartProductIds()
    {
        $productIds = [];
        $quote = $this->cart->getQuote();
        foreach ($quote->getAllVisibleItems() as $item) {
            $productIds[] = $item->getProductId();
        }
        return implode(',', $productIds);
    }

    /**
     * Method getCronHour
     *
     * @return string         return cronhour
     */
    public function getCronHour()
    {
        return $this->getConfig('general', 'cronhour');
    }

    /**
     * Method saveConfig
     *
     * @param string $path
     * @param string $value
     * @param string $scope
     * @param int $scopeId
     *
     * @return void
     */
    public function saveConfig(
        $path,
        $value,
        $scope = \Magento\Store\Model\ScopeInterface::SCOPE_DEFAULT,
        $scopeId = 0
    ) {
        $this->writeConfig->save(
            'inferendo_visidea/' . $path,
            $value,
            $scope,
            $scopeId
        );
    }

    /**
     * Method getStoreId
     *
     * @return string
     */
    public function getStoreId()
    {
        try {
            $storeId = $this->storeManager->getStore()->getId();
            $this->logger->debug('Visidea getStoreId: ' . $storeId);
            return $storeId;
        } catch (\Exception $e) {
            $this->logger->error('Visidea getStoreId error: ' . $e->getMessage());
            return '';
        }
    }
}
