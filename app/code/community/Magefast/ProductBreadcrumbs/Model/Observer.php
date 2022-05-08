<?php

class Magefast_ProductBreadcrumbs_Model_Observer
{
    /**
     * @param $observer
     * @return $this
     */
    public function updateBreadcrumbs($observer)
    {
        $layout = $observer->getLayout();

        /**
         * Return, if have not Breadcrumbs block
         */
        if (!$layout->getBlock('breadcrumbs')) {
            return $this;
        }

        /**
         * Return, if exist current category - go to Standart logic
         */
        $currentCategory = Mage::registry('current_category');

        if ($currentCategory) {
            return $this;
        }

        /**
         * Return, if have not product, not product page
         */
        $currentProduct = Mage::registry('current_product');

        if (!$currentProduct) {
            return $this;
        }

        if ($catIds = $currentProduct->getCategoryIds()) {

            if (is_array($catIds) && count($catIds) > 0) {
                $categories = $this->_getCategoriesArray();

                foreach ($catIds as $c) {
                    if (isset($skipCategoryIDsArray[$c])) {
                        continue;
                    }
                    if (isset($categories[$c]) && $categories[$c]['level'] == 4) {
                        $productCategory = $categories[$c];
                        break;
                    }

                    if (isset($categories[$c]) && $categories[$c]['level'] == 3) {
                        $productCategory = $categories[$c];
                        break;
                    }

                    if (isset($categories[$c]) && $categories[$c]['level'] == 2) {
                        $productCategory = $categories[$c];
                        break;
                    }
                }

            }

            if (!isset($productCategory)) {
                return $this;
            }

            $path = $productCategory['path'];

            $pathCategories = explode('/', $path);

            foreach ($pathCategories as $pc) {
                if ($pc == 1 || $pc == 2) {
                    continue;
                }
                $breadcrumbs[$pc]['name'] = $categories[$pc]['name'];
                $breadcrumbs[$pc]['url'] = $categories[$pc]['url'];
                $breadcrumbs[$pc]['category'] = $pc;
            }

            if (isset($breadcrumbs)) {
                if ($breadcrumbs_block = $layout->getBlock('breadcrumbs')) {

                    /**
                     * Remove crumb for Product
                     */
                    $breadcrumbs_block->removeCrumbs('product');

                    /**
                     * Add new crumbs,
                     * for Homepage
                     */
                    $breadcrumbs_block->addCrumb(
                        'home',
                        array(
                            'label' => Mage::helper('cms')->__('Home'),
                            'title' => Mage::helper('cms')->__('Go to Home Page'),
                            'link' => Mage::getBaseUrl()
                        )
                    );

                    /**
                     * For categories, where include product
                     */
                    foreach ($breadcrumbs as $b) {
                        $breadcrumbs_block->addCrumb('category' . $b['category'], array(
                            'label' => $b['name'],
                            'title' => $b['name'],
                            'link' => $b['url'],
                            'first' => true,

                        ));
                    }

                    /**
                     * And for current product
                     */
                    $breadcrumbs_block->addCrumb('product', array(
                        'label' => $currentProduct->getName(),
                        'readonly' => false,
                        'last' => true
                    ));
                }
            }
        }

        return $this;
    }

    /**
     * Get array with categories
     *
     * @return array
     */
    protected function _getCategoriesArray()
    {
        $skipCategoryIDsArray = Mage::helper('magefast_productbreadcrumbs')->getSkipCategoryIDs();

        $categoriesArray = Mage::getModel('catalog/category')
            ->getCollection()
            ->addAttributeToSelect('name');

        $categoryArray = array();
        foreach ($categoriesArray as $category) {
            if ($skipCategoryIDsArray[$category->getId()]) {
                continue;
            }
            $categoryData = $category->getData();
            $categoryArray[$categoryData['entity_id']] = $categoryData;
            $categoryArray[$categoryData['entity_id']]['url'] = $category->getUrl();
        }
        unset($categoriesArray);

        return $categoryArray;
    }
}