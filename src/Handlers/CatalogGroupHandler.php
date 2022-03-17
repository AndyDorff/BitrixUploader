<?php


namespace Aniart\BitrixUploader\Handlers;


use AndyDorff\SherpaXML\Misc\ParseResult;
use Aniart\BitrixUploader\DTO\CatalogGroup;

class CatalogGroupHandler extends AbstractDataTransferObjectHandler
{
    public function handle(CatalogGroup $group, ParseResult $parseResult)
    {
        foreach($group->groups as $i => $subGroup){
            $this->handle($subGroup, $parseResult);
            if(!$i){
                $group->groups = [];
            }
            $group->groups[] = $subGroup->id;
        }

        $this->saveDto($group);

        $progressBar = $parseResult->payload['progress_bar'] ?? null;
        if($progressBar){
            $progressBar->setMessage($group->name);
            $progressBar->advance();
        }
    }

}