<?php

namespace ManishJoy\ProductReviewRestriction\Block\Review;

class Form extends \Magento\Review\Block\Form
{
	/**
     * Customer Session Factory
     *
     * @var \Magento\Customer\Model\SessionFactory
     */
	protected $_customerSession;
	/**
     * Order Collection Factory
     *
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
	protected $_orderCollectionFactory;
	/**
     * Registry
     *
     * @var \Magento\Framework\Registry
     */
	protected $_registry;
	/**
     * Review data
     *
     * @var \Magento\Review\Helper\Data
     */
    protected $_reviewData = null;

    /**
     * Catalog product model
     *
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * Rating model
     *
     * @var \Magento\Review\Model\RatingFactory
     */
    protected $_ratingFactory;

    /**
     * @var \Magento\Framework\Url\EncoderInterface
     */
    protected $urlEncoder;

    /**
     * Message manager interface
     *
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * @var \Magento\Customer\Model\Url
     */
    protected $customerUrl;

    /**
     * @var array
     */
    protected $jsLayout;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

	public function __construct(
		\Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        \Magento\Review\Helper\Data $reviewData,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Review\Model\RatingFactory $ratingFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Customer\Model\Url $customerUrl,
        array $data = [],
        \Magento\Framework\Serialize\Serializer\Json $serializer = null,
		\Magento\Customer\Model\SessionFactory $customerSession,
		\Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
	) {
		$this->_customerSession = $customerSession;
		$this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_registry = $registry;
        $this->_scopeConfig = $scopeConfig;
		parent::__construct($context, $urlEncoder, $reviewData, $productRepository, $ratingFactory, $messageManager, $httpContext, $customerUrl, $data);
		$this->jsLayout = isset($data['jsLayout']) ? $data['jsLayout'] : [];
		$this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Serialize\Serializer\Json::class);
	}
	
	protected function _construct()
	{
		parent::_construct();

        if($this->_scopeConfig->getValue(
            'review_restriction/setup/enable', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        )) {
            if ($this->hasCurrentCustomerPurchasedThisProduct()) {
                $this->setTemplate('Magento_Review::form.phtml');
            } else {
                $this->setTemplate('ManishJoy_ProductReviewRestriction::no_review_form.phtml');
            }
        } else {
            $this->setTemplate('Magento_Review::form.phtml');
        }
		
	}

	public function getCurrentCustomerId()
	{
		return $this->_customerSession->create()->getCustomer()->getId();
	}

	public function getCustomerOrders()
	{
		$orders = $this->_orderCollectionFactory->create()->addFieldToSelect(
            '*'
        )->addFieldToFilter(
            'customer_id',
            $this->getCurrentCustomerId()
        );

        return $orders;
	}

	public function getCurrentProduct()
	{
		return $this->_registry->registry('current_product');
	}

	public function hasCurrentCustomerPurchasedThisProduct()
	{
		$product_ids = [];

		foreach ($this->getCustomerOrders() as $order) {
		    foreach ($order->getAllVisibleItems() as $item) {
		        $product_ids[$item->getProductId()] = $item->getProductId();
		    }
		}

		if (in_array($this->getCurrentProduct()->getId(), $product_ids)) {
			return true;
		} else {
			return false;
		}
	}
}