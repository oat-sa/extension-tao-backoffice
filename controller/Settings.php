<?php

namespace oat\taoBackOffice\controller;

class Settings extends \tao_actions_CommonModule
{
    use \oat\tao\model\http\HttpJsonResponseTrait;

    private const CONFIG = "globalUIConfig";
    private const EXTENSION_ID = "taoBackOffice";
    /**
     * @throws \common_ext_ExtensionException
     */
    public function featureVisibility()
    {
        /** @var \common_ext_ExtensionsManager $extensionManager */
        $extensionManager = $this->getServiceLocator()->getContainer()->get(\common_ext_ExtensionsManager::SERVICE_ID);
        $extension = $extensionManager->getExtensionById(self::EXTENSION_ID);

        if (!$extension->hasConfig(self::CONFIG)){
            return $this->setErrorJsonResponse("Config file doesn't found");
        }
        return $this->setSuccessJsonResponse($extension->getConfig(self::CONFIG));
    }
}