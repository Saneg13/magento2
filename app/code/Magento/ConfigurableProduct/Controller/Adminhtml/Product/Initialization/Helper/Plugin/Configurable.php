<?php
/**
 * Product initialization helper
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ConfigurableProduct\Controller\Adminhtml\Product\Initialization\Helper\Plugin;

use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProduct;
use Magento\ConfigurableProduct\Model\Product\VariationHandler;
use Magento\Framework\App\RequestInterface;

/**
 * Class Configurable
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Configurable
{
    /**
     * @var VariationHandler
     */
    private $variationHandler;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Factory
     */
    private $optionsFactory;

    /**
     * Constructor
     *
     * @param VariationHandler $variationHandler
     * @param RequestInterface $request
     * @param Factory $optionsFactory
     */
    public function __construct(
        VariationHandler $variationHandler,
        RequestInterface $request,
        Factory $optionsFactory
    ) {
        $this->variationHandler = $variationHandler;
        $this->request = $request;
        $this->optionsFactory = $optionsFactory;
    }

    /**
     * Initialize data for configurable product
     *
     * @param Helper $subject
     * @param ProductInterface $product
     * @return ProductInterface
     * @throws \InvalidArgumentException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterInitialize(Helper $subject, ProductInterface $product)
    {
        $attributes = $this->request->getParam('attributes');
        $productData = $this->request->getPost('product', []);

        if ($product->getTypeId() !== ConfigurableProduct::TYPE_CODE || empty($attributes)) {
            return $product;
        }

        $setId = $this->request->getPost('new-variations-attribute-set-id');
        if ($setId) {
            $product->setAttributeSetId($setId);
        }
        $extensionAttributes = $product->getExtensionAttributes();

        $product->setNewVariationsAttributeSetId($setId);

        $configurableOptions = [];
        if (!empty($productData['configurable_attributes_data'])) {
            $configurableOptions = $this->optionsFactory->create(
                (array) $productData['configurable_attributes_data']
            );
        }

        $extensionAttributes->setConfigurableProductOptions($configurableOptions);

        $this->setLinkedProducts($product, $extensionAttributes);
        $product->setCanSaveConfigurableAttributes(
            (bool) $this->request->getPost('affect_configurable_product_attributes')
        );

        $product->setExtensionAttributes($extensionAttributes);

        return $product;
    }

    /**
     * Relate simple products to configurable
     *
     * @param ProductInterface $product
     * @param ProductExtensionInterface $extensionAttributes
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function setLinkedProducts(ProductInterface $product, ProductExtensionInterface $extensionAttributes)
    {
        $associatedProductIds = $this->request->getPost('associated_product_ids', []);
        $variationsMatrix = $this->request->getParam('variations-matrix', []);
        if (!empty($variationsMatrix)) {
            $generatedProductIds = $this->variationHandler->generateSimpleProducts($product, $variationsMatrix);
            $associatedProductIds = array_merge($associatedProductIds, $generatedProductIds);
        }
        $extensionAttributes->setConfigurableProductLinks(array_filter($associatedProductIds));
    }
}