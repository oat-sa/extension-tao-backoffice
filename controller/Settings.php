<?php

namespace oat\taoBackOffice\controller;

class Settings extends \tao_actions_CommonModule
{
    use \oat\tao\model\http\HttpJsonResponseTrait;

    public function featureVisibility()
    {
        return $this->setSuccessJsonResponse([
            "item/multiColumn" => "show",
            "item/scrollableMultiColumn" => "show",
            "item/response/modalFeedback" => "hide",
            "item/interaction/*/shufflingChoices" => "hide",
            "item/customInteraction/*" => "hide",
            "item/customInteraction/audioPciInteraction" => "show",
            "test/item/timeLimits" => "hide"
        ]);
    }
}