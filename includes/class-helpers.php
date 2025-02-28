<?php
function wpdp_get_actors(){
    $actors = get_field('actor_filter','option');

    if(!$actors){
        return [];
    }

    $actor_list = [];

    foreach($actors as $actor){
        $filter = $actor['filter'];
        foreach($filter as $f){
            $actor_list[$f['actor_code'][0]] = $f['text'];
        }
    }
    return $actor_list;
}