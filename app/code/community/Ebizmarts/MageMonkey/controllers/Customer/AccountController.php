<?php

/**
 * MailChimp Customer Account controller
 */
class Ebizmarts_MageMonkey_Customer_AccountController extends Mage_Core_Controller_Front_Action
{

    /**
     * Action predispatch
     *
     * Check customer authentication for some actions
     */
    public function preDispatch()
    {
        parent::preDispatch();

        if (!$this->getRequest()->isDispatched()) {
            return;
        }

        if (!$this->_getCustomerSession()->authenticate($this)) {
            $this->setFlag('', 'no-dispatch', true);
        }
    }

    /**
     * Retrieve customer session model object
     *
     * @return Mage_Customer_Model_Session
     */
    protected function _getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

	public function indexAction()
	{
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');

        $this->getLayout()->getBlock('head')->setTitle($this->__('Newsletter Subscription'));
        $this->renderLayout();
	}

	public function saveadditionalAction()
	{
		if($this->getRequest()->isPost()){

			parse_str($this->getRequest()->getPost('state'), $odata);

			$curlists = (TRUE === array_key_exists('list', $odata)) ? $odata['list'] : array();
			$lists    = $this->getRequest()->getPost('list', array());

			$api       = Mage::getSingleton('monkey/api');
			$customer  = Mage::helper('customer')->getCustomer();
			$email     = $customer->getEmail();

			if( !empty($curlists) ){

				//Handle Unsubscribe and groups update actions
				foreach($curlists as $listId => $list){

					if(FALSE === array_key_exists($listId, $lists)){

						//Unsubscribe Email
						$api->listUnsubscribe($listId, $email);

					}else{

						$groupings = $lists[$listId];
						unset($groupings['subscribed']);
						$customer->setListGroups($groupings);
						$mergeVars = Mage::helper('monkey')->getMergeVars($customer);

						//Handle groups update
						$api->listUpdateMember($listId, $email, $mergeVars);

					}

				}

			}

			//Subscribe to new lists
			$subscribe = array_diff_key($lists, $curlists);
			if( !empty($subscribe) ){

				foreach($subscribe as $listId => $slist){

					$groupings = $lists[$listId];
					unset($groupings['subscribed']);
					$customer->setListGroups($groupings);
					$mergeVars = Mage::helper('monkey')->getMergeVars($customer);

					$api->listSubscribe($listId, $email, $mergeVars, 'html', false);

				}

			}

		}

		$this->_redirect('*/*/index');
	}

}