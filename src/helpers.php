<?php

if (function_exists('arrangeTaxonomyItemsOverride') === false) {
    function arrangeTaxonomyItemsOverride($archives)
    {
        foreach($archives as $archive){
            $itemList = (is_array($archive->items)) ? $archive->items : $archive->items->toArray();
            foreach($itemList as $item){
                taxonomyItemMergeToParent($itemList, $item);
            }
            $archive->items = $itemList;
        }
        return $archives;
    }

    //이것은 재귀적이다!
    function taxonomyItemMergeToParent(&$itemList, $item){
        if($item['parent_id']){
            $itemList[$item['id']] = array_merge($itemList[$item['parent_id']],$item);
            taxonomyItemMergeToParent($itemList,$itemList[$item['parent_id']]);
        }
    }
}
