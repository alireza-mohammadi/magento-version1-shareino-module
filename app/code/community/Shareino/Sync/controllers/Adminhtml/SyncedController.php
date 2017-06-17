<?php

class Shareino_Sync_Adminhtml_SyncedController extends Mage_Adminhtml_Controller_Action
{

    protected function setResponse($data)
    {
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($data));
    }

    public function indexAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('sharein_tab/');
        $this->_addContent($this->getLayout()->createBlock('sync/adminhtml_synced'));
        $this->_addBreadcrumb(Mage::helper('sync')->__('Form'), Mage::helper('sync')->__('Synchronization'));
        Mage::register('productIDs', Mage::helper('sync')->getAllProductIds());
        $this->renderLayout();
    }

    public function syncProductsAction()
    {
        $ids = $this->getRequest()->getParam('ids');

        $products = array();
        foreach ($ids as $id) {
            $products[] = Mage::helper('sync')->getProductById($id);
        }

        $results = Mage::helper('sync')->sendRequset('products', json_encode($products), 'POST');

        foreach ($results as $result) {
            $shsync = Mage::getModel('sync/synced')
                ->load($result['code'], 'product_id');

            $data = array(
                'product_id' => $result['code'],
                'status' => $result['status'],
                'errors' => !empty($result['errors']) ? implode(', ', $result['errors']) : ''
            );

            if ($shsync->isObjectNew()) {
                $data ['date_add'] = date('Y-m-d H:i:s');
                $shsync->setData($data)
                    ->save();
            } else {
                $data ['date_upd'] = date('Y-m-d H:i:s');
                $shsync->addData($data)
                    ->setId($shsync['id_shareino_sync'])
                    ->save();
            }
        }

        $this->setResponse($results);
    }

    public function syncCategoryAction()
    {
        $category = Mage::getModel('catalog/category');
        $tree = $category->getTreeModel();
        $tree->load();

        $ids = $tree->getCollection()->getAllIds();

        $listCategory = [];
        if ($ids) {
            foreach ($ids as $id) {
                $category->load($id);
                $subcategories = $category->getChildrenCategories();

                $parent_id = 0;
                foreach ($subcategories as $subcategory) {
                    $parent_id = $subcategory->getId();
                }

                $listCategory[] = [
                    'id' => $category->getId(),
                    'parent_id' => $parent_id,
                    'name' => $category->getName()
                ];
            }
        }

        $data = Mage::helper('sync')->sendRequset('categories/sync', json_encode($listCategory), 'POST');
        $this->setResponse($data);
    }

}
