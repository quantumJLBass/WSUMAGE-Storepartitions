<?php
require_once(Mage::getModuleDir('controllers','Mage_Adminhtml').DS.'System'.DS.'StoreController.php');
class Wsu_Storepartitions_Adminhtml_System_StoreController extends Mage_Adminhtml_System_StoreController {

    /**
     * Init actions
     *
     * @return Mage_Adminhtml_Cms_PageController
     */
    protected function _initAction() {
        // load layout, set active menu and breadcrumbs
        $this->loadLayout()
            ->_setActiveMenu('system/store')
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('System'), Mage::helper('adminhtml')->__('System'))
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Manage Stores'), Mage::helper('adminhtml')->__('Manage Stores'))
        ;
        return $this;
    }

    public function quickAddAction() {

		$model      = Mage::getModel('core/website');
		Mage::register('store_data', $model);
		
		
		$this->_initAction()
			->_addContent($this->getLayout()->createBlock('adminhtml/system_store_quickadd'))
			->renderLayout();
        //$this->_forward('newStore');
    }
	
    public function quickAddSaveAction() {
		if ($this->getRequest()->isPost() && $postData = $this->getRequest()->getPost()) {
            if (empty($postData['root_cat'])) {
                $this->_redirect('*/*/');
                return;
            }
            $session = $this->_getSession();
		}

		
		
		
		$cDat = new Mage_Core_Model_Config();
        $SU_Helper = Mage::helper('storeutilities/utilities');
		$SP_Helper = Mage::helper('storepartitions');
		
		$newRootCat = $SU_Helper->make_category($postData['root_cat']);
		if($newRootCat>0){
			Mage::getSingleton('adminhtml/session')->addSuccess( $SP_Helper->__('Created the new root category of')." <b>". $postData['root_cat']."</b>" );
			
			$siteId = $SU_Helper->make_website(array(
							'code'=>$postData['website']['code'],
							'name'=>$postData['website']['name']
						));
			if( $siteId>0 ){
				Mage::getSingleton('adminhtml/session')->addSuccess( $postData['website']['name']."(id: ${siteId}) ".Mage::helper('storeutilities')->__('Web Site was created') );
				
				$storeGroupId = $SU_Helper->make_storeGroup( array(
						'name'=>$postData['storegroup']['name']
					),
					$postData['storegroup']['baseurl'],
					$siteId, $newRootCat
				 );
				if( $storeGroupId>0 ){
					Mage::getSingleton('adminhtml/session')->addSuccess( $postData['storegroup']['name']." [".$postData['storegroup']['baseurl']."](id: ${storeGroupId}) ".$SP_Helper->__(' site group was created') );
					
					$storeId = $SU_Helper->make_store( $siteId, $storeGroupId, array(
								'code'=>$postData['website']['code'],
								'name'=>"base default veiw "
								) );
					if( $storeId>0 ){
						
						Mage::getSingleton('adminhtml/session')->addSuccess( $SP_Helper->__('A base store view was created for the new store') );
						
						if(!empty($postData['store']['home_layout'])){
							$SU_Helper->createCmsPage($storeId,array(
								'title' => $postData['store']['title'],
								'identifier' => 'home',
								'content_heading' => $postData['store']['content_heading'],
								'is_active' => 1,
								'stores' => array($storeId),//available for all store views
								'content' => $postData['store']['home_layout']
							));
						}
						
						
						if($SP_Helper->shouldMapWebsites()){
							$map_file=Mage::getBaseDir().'/maps/nginx-mapping.conf';
							if(!file_exists($map_file)){
								file_put_contents($map_file, "map \$http_host \$magesite {\n#MAGE_CONTROLLED_MAPS-Storepartitions\n#END_OF_MAGE_CONTROLLED_MAPS-Storepartitions\n}");
							}

							$str=file_get_contents($map_file);
							
							if(strpos($str,'#END_OF_MAGE_CONTROLLED_MAPS-Storepartitions')===false){
								$str=str_replace("}","\n#MAGE_CONTROLLED_MAPS-Storepartitions\n#END_OF_MAGE_CONTROLLED_MAPS-Storepartitions\n}",$str);
							}
							$str=str_replace("#END_OF_MAGE_CONTROLLED_MAPS-Storepartitions","    ".$postData['storegroup']['baseurl']." ".$postData['website']['code'].";\n#END_OF_MAGE_CONTROLLED_MAPS-Storepartitions",$str);
							file_put_contents($map_file, $str);
							Mage::getSingleton('adminhtml/session')->addSuccess( $postData['storegroup']['baseurl']." ".$postData['website']['code']." ".Mage::helper('storeutilities')->__('was added to the nginx-mapping.conf file') );
						}
						
						/*
						$cDat->saveConfig('wsu_themecontrol_design/spine/spine_color', 'crimson', 'websites', $siteId);
						$cDat->saveConfig('wsu_themecontrol_design/spine/spine_tool_bar_color', 'lighter', 'websites', $siteId);
						$cDat->saveConfig('wsu_themecontrol_design/spine/spine_bleed', '0', 'websites', $siteId);
						$cDat->saveConfig('wsu_themecontrol_design/spine/max_width', '1188', 'websites', $siteId);
						$cDat->saveConfig('wsu_themecontrol_design/spine/fluid_width', 'hybrid', 'websites', $siteId);
						*/
						
					}else{Mage::getSingleton('adminhtml/session')->addError("failed to create the store view");}
				}else{Mage::getSingleton('adminhtml/session')->addError("failed to create the store group");}
			}else{Mage::getSingleton('adminhtml/session')->addError("failed to create the website");}
		}else{Mage::getSingleton('adminhtml/session')->addError("failed to create the root category");}

		$this->_redirect('*/*/');
		
    }
	


}

